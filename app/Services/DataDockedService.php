<?php

namespace App\Services;

use App\Contracts\VesselTrackingInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataDockedService implements VesselTrackingInterface
{
    protected string $apiKey;

    protected string $baseUrl = 'https://datadocked.com/api/vessels_operations';

    public function __construct()
    {
        $this->apiKey = config('services.datadocked.key', '');
    }

    public function searchVessels(string $keyword, int $limit = 10): array
    {
        if (! $this->apiKey) {
            return [];
        }

        $name = str_replace(' ', '_', $keyword);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                ])
                ->get($this->baseUrl.'/vessels-by-vessel-name', [
                    'name' => $name,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['items'] ?? [];

                return collect($items)->take($limit)->map(fn ($v) => [
                    'mmsi' => $v['mmsi'] ?? '',
                    'imo' => $v['imo'] ?? '',
                    'name' => $v['name'] ?? '',
                    'country' => $v['country'] ?? '',
                    'type' => $v['type'] ?? '',
                    'callsign' => $v['callsign'] ?? '',
                ])->toArray();
            }

            Log::warning('DataDocked search failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::warning('DataDocked search exception: '.$e->getMessage());
        }

        return [];
    }

    public function getVesselPosition(string $mmsi): ?array
    {
        if (! $this->apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                ])
                ->get($this->baseUrl.'/get-vessel-location', [
                    'imo_or_mmsi' => $mmsi,
                ]);

            if ($response->successful()) {
                $detail = $response->json('detail');

                if ($detail && isset($detail['latitude']) && isset($detail['longitude'])) {
                    return [
                        'mmsi' => $detail['mmsi'] ?? $mmsi,
                        'imo' => $detail['imo'] ?? '',
                        'vessel_name' => $detail['name'] ?? '',
                        'latitude' => (float) $detail['latitude'],
                        'longitude' => (float) $detail['longitude'],
                        'sog' => (float) ($detail['speed'] ?? 0),
                        'cog' => (float) ($detail['course'] ?? 0),
                        'heading' => (float) ($detail['heading'] ?? 0),
                        'nav_status' => $detail['navigationalStatus'] ?? '',
                        'destination' => $detail['destination'] ?? '',
                        'eta' => $detail['etaUtc'] ?? '',
                        'last_port' => $detail['lastPort'] ?? '',
                        'data_source' => $detail['dataSource'] ?? '',
                    ];
                }
            }

            if ($response->status() === 404) {
                return null;
            }

            Log::warning('DataDocked position failed', [
                'mmsi' => $mmsi,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::warning('DataDocked position exception: '.$e->getMessage());
        }

        return null;
    }

    public function getMultiplePositions(array $mmsiList): array
    {
        if (! $this->apiKey || empty($mmsiList)) {
            return [];
        }

        $ids = implode(',', $mmsiList);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                ])
                ->get($this->baseUrl.'/get-vessels-location-bulk-search', [
                    'imo_or_mmsi' => $ids,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $results = $data['results'] ?? [];

                return collect($results)->map(fn ($r) => [
                    'mmsi' => $r['mmsi'] ?? '',
                    'imo' => $r['imo'] ?? '',
                    'vessel_name' => $r['name'] ?? '',
                    'latitude' => (float) ($r['latitude'] ?? 0),
                    'longitude' => (float) ($r['longitude'] ?? 0),
                    'sog' => (float) ($r['speed'] ?? 0),
                    'cog' => (float) ($r['course'] ?? 0),
                    'heading' => (float) ($r['heading'] ?? 0),
                    'nav_status' => $r['navigationalStatus'] ?? '',
                    'destination' => $r['destination'] ?? '',
                    'eta' => $r['etaUtc'] ?? '',
                    'last_port' => $r['lastPort'] ?? '',
                    'data_source' => $r['dataSource'] ?? '',
                ])->filter(fn ($v) => $v['latitude'] && $v['longitude'])->values()->toArray();
            }

            Log::warning('DataDocked bulk search failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::warning('DataDocked bulk search exception: '.$e->getMessage());
        }

        return [];
    }

    public function isKeyValid(): bool
    {
        if (! $this->apiKey) {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                ])
                ->get($this->baseUrl.'/my-credits');

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('DataDocked key validation failed: '.$e->getMessage());
        }

        return false;
    }
}
