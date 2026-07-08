<?php

namespace App\Services;

use App\Contracts\PortRepositoryInterface;
use App\Models\Port;
use Illuminate\Support\Collection;

class WorldPortIndexService implements PortRepositoryInterface
{
    public function getAll(): Collection
    {
        return Port::all();
    }

    public function search(?string $term): Collection
    {
        if (! $term) {
            return Port::all();
        }

        return Port::where('name', 'like', "%{$term}%")
            ->orWhere('country', 'like', "%{$term}%")
            ->orWhere('country_code', $term)
            ->get();
    }

    public function getByCountry(string $country): Collection
    {
        return Port::where('country', $country)
            ->orWhere('country_code', $country)
            ->get();
    }

    public function getByCountryCode(string $code): Collection
    {
        return Port::where('country_code', strtoupper($code))->get();
    }

    public function countByCountry(): Collection
    {
        return Port::selectRaw('country, country_code, COUNT(*) as total')
            ->groupBy('country', 'country_code')
            ->orderByDesc('total')
            ->get();
    }

    public function getPortsInBounds(float $south, float $west, float $north, float $east): Collection
    {
        return Port::whereBetween('latitude', [$south, $north])
            ->whereBetween('longitude', [$west, $east])
            ->get();
    }

    public function getPortTypes(): Collection
    {
        return Port::select('port_type')
            ->whereNotNull('port_type')
            ->distinct()
            ->orderBy('port_type')
            ->get()
            ->pluck('port_type');
    }
}
