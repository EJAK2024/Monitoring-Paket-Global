<?php

namespace App\Services;

use App\Models\Container;
use App\Models\ContainerTrackingEvent;
use App\Models\Port;
use App\Models\Vessel;
use Illuminate\Support\Collection;

class ContainerTrackingService
{
    public function search(string $keyword): Collection
    {
        return Container::with('vessel')
            ->where('container_id', 'like', "%{$keyword}%")
            ->orWhere('shipper', 'like', "%{$keyword}%")
            ->orWhere('consignee', 'like', "%{$keyword}%")
            ->orWhere('origin', 'like', "%{$keyword}%")
            ->orWhere('destination', 'like', "%{$keyword}%")
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function detail(string $containerId): ?Container
    {
        return Container::with(['vessel', 'trackingEvents' => function ($q) {
            $q->orderBy('occurred_at', 'desc');
        }])->where('container_id', $containerId)->first();
    }

    public function timeline(string $containerId): Collection
    {
        $container = Container::where('container_id', $containerId)->first();

        if (! $container) {
            return collect();
        }

        return ContainerTrackingEvent::with('vessel')
            ->where('container_id', $container->id)
            ->orderBy('occurred_at', 'desc')
            ->get();
    }

    public function recordEvent(
        Container $container,
        string $eventType,
        ?string $location = null,
        ?Vessel $vessel = null,
        ?string $remarks = null,
    ): ContainerTrackingEvent {
        $event = ContainerTrackingEvent::create([
            'container_id' => $container->id,
            'event_type' => $eventType,
            'location' => $location,
            'vessel_id' => $vessel?->id,
            'remarks' => $remarks,
            'occurred_at' => now(),
        ]);

        $container->update([
            'status' => $this->mapEventToStatus($eventType),
            'current_location' => $location ?? $container->current_location,
            'vessel_id' => $vessel?->id ?? $container->vessel_id,
            'last_scanned_at' => now(),
        ]);

        return $event;
    }

    public function estimateArrival(Container $container): ?string
    {
        if (! $container->vessel || ! $container->destination) {
            return null;
        }

        $vessel = $container->vessel;
        $speed = $vessel->speed > 0 ? $vessel->speed : 20;

        $port = Port::where('name', 'like', "%{$container->destination}%")->first();

        if (! $port || ! $vessel->latitude || ! $vessel->longitude) {
            return null;
        }

        $distance = $this->haversine(
            $vessel->latitude, $vessel->longitude,
            $port->latitude, $port->longitude
        );

        $hours = $distance / ($speed * 1.852);
        $arrival = now()->addHours((int) ceil($hours));

        return $arrival->format('Y-m-d H:i');
    }

    public function statusStats(): array
    {
        $statuses = ['in_transit', 'at_port', 'customs', 'delivered', 'empty', 'delayed'];
        $stats = [];

        foreach ($statuses as $s) {
            $stats[$s] = Container::where('status', $s)->count();
        }

        $stats['total'] = array_sum($stats);
        $stats['in_transit_pct'] = $stats['total'] > 0
            ? round($stats['in_transit'] / $stats['total'] * 100)
            : 0;

        return $stats;
    }

    private function mapEventToStatus(string $eventType): string
    {
        return match ($eventType) {
            'loaded', 'departed' => 'in_transit',
            'arrived', 'discharged' => 'at_port',
            'customs_cleared', 'customs_hold' => 'customs',
            'gate_out', 'delivered' => 'delivered',
            'returned' => 'empty',
            'delayed' => 'delayed',
            default => 'at_port',
        };
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
