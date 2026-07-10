<?php

namespace App\Services;

use App\Contracts\VesselTrackingInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VesselFinderService implements VesselTrackingInterface
{
    protected string $apiKey;

    protected string $baseUrl = 'https://api.vesselfinder.com';

    protected array $knownVessels = [
        ['mmsi' => '219018914', 'imo' => '9612983', 'name' => 'Mærsk Mc-Kinney Møller', 'type' => 'Container Ship'],
        ['mmsi' => '477712700', 'imo' => '9776171', 'name' => 'OOCL Hong Kong', 'type' => 'Container Ship'],
        ['mmsi' => '477731500', 'imo' => '9776145', 'name' => 'OOCL Germany', 'type' => 'Container Ship'],
        ['mmsi' => '371356000', 'imo' => '9746504', 'name' => 'MSC Irina', 'type' => 'Container Ship'],
        ['mmsi' => '431746000', 'imo' => '9839657', 'name' => 'Ever Ace', 'type' => 'Container Ship'],
        ['mmsi' => '353136000', 'imo' => '9811000', 'name' => 'CMA CGM Antoine de Saint Exupéry', 'type' => 'Container Ship'],
        ['mmsi' => '440047000', 'imo' => '9793039', 'name' => 'HMM Algeciras', 'type' => 'Container Ship'],
        ['mmsi' => '374156000', 'imo' => '9788143', 'name' => 'HMM Oslo', 'type' => 'Container Ship'],
        ['mmsi' => '477882900', 'imo' => '9795715', 'name' => 'COSCO Shipping Universe', 'type' => 'Container Ship'],
        ['mmsi' => '636018962', 'imo' => '9839293', 'name' => 'HMM Copenhagen', 'type' => 'Container Ship'],
        ['mmsi' => '477712800', 'imo' => '9776183', 'name' => 'OOCL Indonesia', 'type' => 'Container Ship'],
        ['mmsi' => '371771000', 'imo' => '9705495', 'name' => 'MSC Zoe', 'type' => 'Container Ship'],
        ['mmsi' => '563094700', 'imo' => '9811000', 'name' => 'Ever Given', 'type' => 'Container Ship'],
        ['mmsi' => '219039000', 'imo' => '9783575', 'name' => 'Mærsk Chennai', 'type' => 'Container Ship'],
        ['mmsi' => '477581700', 'imo' => '9663593', 'name' => 'MSC Diana', 'type' => 'Container Ship'],
        ['mmsi' => '219031000', 'imo' => '9783563', 'name' => 'Mærsk Eviden', 'type' => 'Container Ship'],
    ];

    public function __construct()
    {
        $this->apiKey = config('services.vesselfinder.key', '');
    }

    public function searchVessels(string $keyword, int $limit = 10): array
    {
        $lower = strtolower($keyword);
        $results = collect($this->knownVessels)
            ->filter(fn ($v) => str_contains(strtolower($v['name']), $lower))
            ->take($limit)
            ->map(fn ($v) => [
                'mmsi' => $v['mmsi'],
                'imo' => $v['imo'] ?? '',
                'name' => $v['name'],
                'country' => $v['country'] ?? '',
                'type' => $v['type'] ?? '',
                'callsign' => $v['callsign'] ?? '',
            ])
            ->values()
            ->toArray();

        if (empty($results)) {
            return collect($this->knownVessels)->take($limit)->map(fn ($v) => [
                'mmsi' => $v['mmsi'],
                'imo' => $v['imo'] ?? '',
                'name' => $v['name'],
                'country' => $v['country'] ?? '',
                'type' => $v['type'] ?? '',
                'callsign' => $v['callsign'] ?? '',
            ])->toArray();
        }

        return $results;
    }

    public function getVesselPosition(string $mmsi): ?array
    {
        if (! $this->apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->get($this->baseUrl.'/vessels', [
                    'userkey' => $this->apiKey,
                    'mmsi' => $mmsi,
                ]);

            if ($this->hasError($response)) {
                Log::warning('VesselFinder position API error', [
                    'mmsi' => $mmsi,
                    'error' => $response->header('X-API-ERROR') ?: ($response->json()['error'] ?? 'unknown'),
                ]);

                return null;
            }

            $data = $response->json();
            $ais = $data[0]['AIS'] ?? $data[0] ?? null;

            if ($ais && isset($ais['LATITUDE']) && isset($ais['LONGITUDE'])) {
                $navstat = $ais['NAVSTAT'] ?? null;
                $navStatusText = is_numeric($navstat) ? $this->navstatToString((int) $navstat) : ($navstat ?? '');

                return [
                    'mmsi' => $ais['MMSI'] ?? $mmsi,
                    'imo' => $ais['IMO'] ?? '',
                    'vessel_name' => $ais['SHIPNAME'] ?? $ais['NAME'] ?? '',
                    'latitude' => (float) $ais['LATITUDE'],
                    'longitude' => (float) $ais['LONGITUDE'],
                    'sog' => (float) ($ais['SPEED'] ?? 0),
                    'cog' => (float) ($ais['COURSE'] ?? 0),
                    'heading' => (float) ($ais['HEADING'] ?? 0),
                    'nav_status' => $navStatusText,
                    'destination' => $ais['DESTINATION'] ?? '',
                    'eta' => $ais['ETA'] ?? $ais['ETA_AIS'] ?? '',
                    'last_port' => '',
                    'data_source' => $ais['SRC'] ?? 'TER',
                ];
            }

            Log::warning('VesselFinder position failed', [
                'mmsi' => $mmsi,
                'status' => $response->status(),
            ]);
        } catch (\Exception $e) {
            Log::warning('VesselFinder position exception: '.$e->getMessage());
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
                ->get($this->baseUrl.'/vessels', [
                    'userkey' => $this->apiKey,
                    'mmsi' => $ids,
                ]);

            if ($this->hasError($response)) {
                Log::warning('VesselFinder bulk position API error', [
                    'error' => $response->header('X-API-ERROR') ?: ($response->json()['error'] ?? 'unknown'),
                ]);

                return [];
            }

            $data = $response->json();

            return collect($data)->map(function ($item) {
                $ais = $item['AIS'] ?? $item;

                if (! isset($ais['LATITUDE']) || ! isset($ais['LONGITUDE'])) {
                    return null;
                }

                $navstat = $ais['NAVSTAT'] ?? null;
                $navStatusText = is_numeric($navstat) ? $this->navstatToString((int) $navstat) : ($navstat ?? '');

                return [
                    'mmsi' => $ais['MMSI'] ?? '',
                    'imo' => $ais['IMO'] ?? '',
                    'vessel_name' => $ais['SHIPNAME'] ?? $ais['NAME'] ?? 'Unknown',
                    'latitude' => (float) $ais['LATITUDE'],
                    'longitude' => (float) $ais['LONGITUDE'],
                    'sog' => (float) ($ais['SPEED'] ?? 0),
                    'cog' => (float) ($ais['COURSE'] ?? 0),
                    'heading' => (float) ($ais['HEADING'] ?? 0),
                    'nav_status' => $navStatusText,
                    'destination' => $ais['DESTINATION'] ?? '',
                    'eta' => $ais['ETA'] ?? $ais['ETA_AIS'] ?? '',
                    'last_port' => '',
                    'data_source' => $ais['SRC'] ?? 'TER',
                ];
            })->filter()->values()->toArray();
        } catch (\Exception $e) {
            Log::warning('VesselFinder bulk position exception: '.$e->getMessage());
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
                ->get($this->baseUrl.'/vessels', [
                    'userkey' => $this->apiKey,
                    'mmsi' => '219018914',
                ]);

            return ! $this->hasError($response);
        } catch (\Exception $e) {
            Log::warning('VesselFinder key validation failed: '.$e->getMessage());
        }

        return false;
    }

    protected function hasError($response): bool
    {
        if (! $response->successful()) {
            return true;
        }

        if ($response->header('X-API-ERROR')) {
            return true;
        }

        $body = $response->json();
        if (isset($body['error'])) {
            return true;
        }

        return false;
    }

    protected function navstatToString(int $code): string
    {
        return match ($code) {
            0 => 'Underway',
            1 => 'At anchor',
            2 => 'Not under command',
            3 => 'Restricted manoeuvrability',
            4 => 'Constrained by draught',
            5 => 'Moored',
            6 => 'Aground',
            7 => 'Fishing',
            8 => 'Sailing',
            9 => 'Reserved for future amendment',
            10 => 'Reserved for future amendment',
            11 => 'Reserved for future amendment',
            12 => 'Reserved for future amendment',
            13 => 'Reserved for future amendment',
            14 => 'AIS-SART',
            15 => 'Not defined',
            default => 'Unknown',
        };
    }
}
