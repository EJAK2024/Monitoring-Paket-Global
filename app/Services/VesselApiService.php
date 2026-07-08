<?php

namespace App\Services;

use App\Contracts\VesselTrackingInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VesselApiService implements VesselTrackingInterface
{
    protected string $apiKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.vesselapi.key');
        $this->baseUrl = 'https://api.vesselapi.com/v1';
    }

    public function searchVessels(string $keyword, int $limit = 10): array
    {
        if (! $this->apiKey) {
            return [];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                ])
                ->get($this->baseUrl.'/search/vessels', [
                    'filter.name' => $keyword,
                    'pagination.limit' => $limit,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['vessels'] ?? [];
            }

            Log::warning('VesselAPI search failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::warning('VesselAPI search exception: '.$e->getMessage());
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
                    'Authorization' => 'Bearer '.$this->apiKey,
                ])
                ->get($this->baseUrl.'/vessel/'.$mmsi.'/position', [
                    'filter.idType' => 'mmsi',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $vp = $data['vesselPosition'] ?? null;

                if ($vp && isset($vp['latitude']) && isset($vp['longitude'])) {
                    return $vp;
                }
            }

            if ($response->status() === 404) {
                return null;
            }

            Log::warning('VesselAPI position failed', [
                'mmsi' => $mmsi,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::warning('VesselAPI position exception: '.$e->getMessage());
        }

        return null;
    }

    public function getMultiplePositions(array $mmsiList): array
    {
        $results = [];
        foreach ($mmsiList as $mmsi) {
            $pos = $this->getVesselPosition($mmsi);
            if ($pos) {
                $results[] = $pos;
            }
        }

        return $results;
    }

    public function isKeyValid(): bool
    {
        if (! $this->apiKey) {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                ])
                ->get($this->baseUrl.'/search/vessels', [
                    'filter.name' => 'MAERSK',
                    'pagination.limit' => 1,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('VesselAPI key validation failed: '.$e->getMessage());
        }

        return false;
    }
}
