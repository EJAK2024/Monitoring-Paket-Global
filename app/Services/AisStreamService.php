<?php

namespace App\Services;

use App\Contracts\VesselTrackingInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use WebSocket\Client as WebSocketClient;
use WebSocket\TimeoutException;

class AisStreamService implements VesselTrackingInterface
{
    protected string $apiKey;

    protected string $wsUrl = 'wss://stream.aisstream.io/v0/stream';

    protected string $positionsCacheKey = 'aisstream.live_vessels';

    protected string $validCacheKey = 'aisstream.key_valid';

    protected array $knownVessels = [
        ['mmsi' => '219018914', 'imo' => '9612983', 'name' => "M\u00e6rsk Mc-Kinney M\u00f8ller", 'type' => 'Container Ship'],
        ['mmsi' => '477712700', 'imo' => '9776171', 'name' => 'OOCL Hong Kong', 'type' => 'Container Ship'],
        ['mmsi' => '477731500', 'imo' => '9776145', 'name' => 'OOCL Germany', 'type' => 'Container Ship'],
        ['mmsi' => '371356000', 'imo' => '9746504', 'name' => 'MSC Irina', 'type' => 'Container Ship'],
        ['mmsi' => '431746000', 'imo' => '9839657', 'name' => 'Ever Ace', 'type' => 'Container Ship'],
        ['mmsi' => '353136000', 'imo' => '9811000', 'name' => 'CMA CGM Antoine de Saint Exup\u00e9ry', 'type' => 'Container Ship'],
        ['mmsi' => '440047000', 'imo' => '9793039', 'name' => 'HMM Algeciras', 'type' => 'Container Ship'],
        ['mmsi' => '374156000', 'imo' => '9788143', 'name' => 'HMM Oslo', 'type' => 'Container Ship'],
        ['mmsi' => '477882900', 'imo' => '9795715', 'name' => 'COSCO Shipping Universe', 'type' => 'Container Ship'],
        ['mmsi' => '636018962', 'imo' => '9839293', 'name' => 'HMM Copenhagen', 'type' => 'Container Ship'],
        ['mmsi' => '477712800', 'imo' => '9776183', 'name' => 'OOCL Indonesia', 'type' => 'Container Ship'],
        ['mmsi' => '371771000', 'imo' => '9705495', 'name' => 'MSC Zoe', 'type' => 'Container Ship'],
        ['mmsi' => '563094700', 'imo' => '9811000', 'name' => 'Ever Given', 'type' => 'Container Ship'],
        ['mmsi' => '219039000', 'imo' => '9783575', 'name' => "M\u00e6rsk Chennai", 'type' => 'Container Ship'],
        ['mmsi' => '477581700', 'imo' => '9663593', 'name' => 'MSC Diana', 'type' => 'Container Ship'],
        ['mmsi' => '219031000', 'imo' => '9783563', 'name' => "M\u00e6rsk Eviden", 'type' => 'Container Ship'],
    ];

    public function __construct()
    {
        $this->apiKey = config('services.aisstream.key', '');
    }

    public function searchVessels(string $keyword, int $limit = 10): array
    {
        $lower = strtolower($keyword);
        $results = collect($this->knownVessels)
            ->filter(fn ($v) => str_contains(strtolower($v['name']), $lower))
            ->take($limit)
            ->values()
            ->toArray();

        if (empty($results)) {
            return collect($this->knownVessels)->take($limit)->values()->toArray();
        }

        return $results;
    }

    public function getVesselPosition(string $mmsi): ?array
    {
        $positions = $this->getAllCachedPositions();

        foreach ($positions as $pos) {
            if (($pos['mmsi'] ?? '') === $mmsi) {
                return $pos;
            }
        }

        return $this->fetchSingleVesselPosition($mmsi);
    }

    protected function fetchSingleVesselPosition(string $mmsi): ?array
    {
        if (! $this->apiKey) {
            return null;
        }

        try {
            $client = new WebSocketClient($this->wsUrl, ['timeout' => 10]);
            $client->text(json_encode([
                'APIKey' => $this->apiKey,
                'BoundingBoxes' => [[[-90, -180], [90, 180]]],
                'FilterMessageTypes' => ['PositionReport'],
            ]));

            $startTime = microtime(true);
            $maxDuration = 8.0;

            while ((microtime(true) - $startTime) < $maxDuration) {
                try {
                    $message = $client->receive();
                    $data = json_decode($message, true);

                    if (! $data || isset($data['error'])) {
                        break;
                    }

                    if (($data['MessageType'] ?? '') !== 'PositionReport') {
                        continue;
                    }

                    $report = $data['Message']['PositionReport'] ?? [];
                    $meta = $data['MetaData'] ?? [];
                    $msgMmsi = (string) ($report['UserID'] ?? '');

                    if ($msgMmsi === $mmsi) {
                        $client->close();

                        return [
                            'mmsi' => $mmsi,
                            'imo' => '',
                            'vessel_name' => isset($meta['ShipName']) ? trim($meta['ShipName']) : ('MMSI '.$mmsi),
                            'latitude' => (float) ($report['Latitude'] ?? 0),
                            'longitude' => (float) ($report['Longitude'] ?? 0),
                            'sog' => (float) ($report['Sog'] ?? 0),
                            'cog' => (float) ($report['Cog'] ?? 0),
                            'heading' => (float) ($report['TrueHeading'] ?? 0),
                            'nav_status' => $this->navstatToString((int) ($report['NavigationalStatus'] ?? 15)),
                            'destination' => '',
                            'eta' => '',
                            'last_port' => '',
                            'data_source' => 'AIS',
                        ];
                    }
                } catch (TimeoutException $e) {
                    continue;
                } catch (\Exception $e) {
                    break;
                }
            }

            $client->close();
        } catch (\Exception $e) {
            Log::warning("AISStream single vessel fetch failed for {$mmsi}: ".$e->getMessage());
        }

        return null;
    }

    public function getMultiplePositions(array $mmsiList): array
    {
        if (empty($mmsiList)) {
            return [];
        }

        $all = $this->getAllCachedPositions();
        $mmsiSet = array_flip($mmsiList);

        return array_values(array_filter($all, fn ($p) => isset($mmsiSet[$p['mmsi'] ?? ''])));
    }

    public function getAllCachedPositions(): array
    {
        return Cache::get($this->positionsCacheKey, []);
    }

    public function isKeyValid(): bool
    {
        if (! $this->apiKey) {
            return false;
        }

        return Cache::remember($this->validCacheKey, 3600, function () {
            return $this->checkKeyRemote();
        });
    }

    public function refreshPositions(): int
    {
        if (! $this->apiKey) {
            return 0;
        }

        $valid = $this->isKeyValid();
        if (! $valid) {
            return 0;
        }

        $positions = $this->fetchFromStream();

        Cache::put($this->positionsCacheKey, $positions, now()->addMinutes(2));

        return count($positions);
    }

    protected function getWsOptions(): array
    {
        return [
            'timeout' => 5,
        ];
    }

    protected function checkKeyRemote(): bool
    {
        try {
            $client = new WebSocketClient($this->wsUrl, $this->getWsOptions());
            $client->text(json_encode([
                'APIKey' => $this->apiKey,
                'BoundingBoxes' => [[[-90, -180], [90, 180]]],
                'FilterMessageTypes' => ['PositionReport'],
            ]));

            $start = microtime(true);

            while (microtime(true) - $start < 3) {
                try {
                    $response = $client->receive();
                    $data = json_decode($response, true);

                    if (isset($data['error'])) {
                        $client->close();

                        return false;
                    }

                    if (($data['MessageType'] ?? '') === 'PositionReport') {
                        $client->close();

                        return true;
                    }
                } catch (TimeoutException $e) {
                    break;
                }
            }

            $client->close();

            return true;
        } catch (\Exception $e) {
            Log::warning('AISStream key check failed: '.$e->getMessage());
        }

        return false;
    }

    protected function fetchFromStream(): array
    {
        $positions = [];
        $mmsiSeen = [];

        try {
            $client = new WebSocketClient($this->wsUrl, $this->getWsOptions());
            $client->text(json_encode([
                'APIKey' => $this->apiKey,
                'BoundingBoxes' => $this->getShippingBoundingBoxes(),
                'FilterMessageTypes' => ['PositionReport'],
            ]));

            $startTime = microtime(true);
            $maxDuration = 8.0;
            $maxMessages = 100;

            while (count($positions) < $maxMessages && (microtime(true) - $startTime) < $maxDuration) {
                try {
                    $message = $client->receive();
                    $data = json_decode($message, true);

                    if (! $data || isset($data['error'])) {
                        break;
                    }

                    if (($data['MessageType'] ?? '') !== 'PositionReport') {
                        continue;
                    }

                    $report = $data['Message']['PositionReport'] ?? [];
                    $meta = $data['MetaData'] ?? [];
                    $mmsi = (string) ($report['UserID'] ?? '');

                    if (! $mmsi || isset($mmsiSeen[$mmsi])) {
                        continue;
                    }

                    $mmsiSeen[$mmsi] = true;

                    $positions[] = [
                        'mmsi' => $mmsi,
                        'imo' => '',
                        'vessel_name' => isset($meta['ShipName']) ? trim($meta['ShipName']) : ('MMSI '.$mmsi),
                        'latitude' => (float) ($report['Latitude'] ?? $meta['latitude'] ?? 0),
                        'longitude' => (float) ($report['Longitude'] ?? $meta['longitude'] ?? 0),
                        'sog' => (float) ($report['Sog'] ?? 0),
                        'cog' => (float) ($report['Cog'] ?? 0),
                        'heading' => (float) ($report['TrueHeading'] ?? 0),
                        'nav_status' => $this->navstatToString((int) ($report['NavigationalStatus'] ?? 15)),
                        'destination' => '',
                        'eta' => '',
                        'last_port' => '',
                        'data_source' => 'AIS',
                    ];
                } catch (TimeoutException $e) {
                    continue;
                } catch (\Exception $e) {
                    Log::warning('AISStream receive error: '.$e->getMessage());
                    break;
                }
            }

            $client->close();
        } catch (\Exception $e) {
            Log::warning('AISStream fetch failed: '.$e->getMessage());
        }

        return $positions;
    }

    protected function getShippingBoundingBoxes(): array
    {
        return [
            [[35, -10], [60, 10]],
            [[25, -80], [45, -60]],
            [[10, 100], [30, 115]],
            [[30, 120], [40, 145]],
            [[30, -130], [50, -115]],
            [[10, 40], [30, 70]],
            [[-40, -60], [-20, -40]],
            [[-40, 140], [-10, 155]],
            [[30, -10], [45, 30]],
            [[-10, 35], [10, 55]],
        ];
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
            default => 'Unknown',
        };
    }
}
