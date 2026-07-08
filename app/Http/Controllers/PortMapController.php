<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Services\VesselApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PortMapController extends Controller
{
    public function index(VesselApiService $vesselApi): View
    {
        $routeDefs = [
            ['name' => 'Asia-Europe Express', 'type' => 'container', 'ships' => 6, 'ports' => ['Port of Shanghai', 'Port of Singapore', 'Port of Colombo', 'Port of Dubai', 'Port of Rotterdam']],
            ['name' => 'Transpacific Eastbound', 'type' => 'container', 'ships' => 5, 'ports' => ['Port of Shanghai', 'Port of Busan', 'Port of Los Angeles', 'Port of Long Beach']],
            ['name' => 'Transpacific Westbound', 'type' => 'container', 'ships' => 4, 'ports' => ['Port of Vancouver', 'Port of Seattle', 'Port of Oakland', 'Port of Tokyo', 'Port of Shanghai']],
            ['name' => 'Europe Short Sea', 'type' => 'container', 'ships' => 4, 'ports' => ['Port of Rotterdam', 'Port of Hamburg', 'Port of Antwerp', 'Port of Southampton']],
            ['name' => 'SE Asia-Oceania', 'type' => 'container', 'ships' => 3, 'ports' => ['Port of Singapore', 'Port of Tanjung Priok', 'Port of Sydney', 'Port of Melbourne']],
            ['name' => 'South-South Trade', 'type' => 'container', 'ships' => 4, 'ports' => ['Port of Santos', 'Port of Buenos Aires', 'Port of Cape Town', 'Port of Mumbai']],
            ['name' => 'Transatlantic', 'type' => 'container', 'ships' => 4, 'ports' => ['Port of Rotterdam', 'Port of New York', 'Port of Savannah', 'Port of Miami']],
            ['name' => 'Asia-Mediterranean', 'type' => 'container', 'ships' => 5, 'ports' => ['Port of Shanghai', 'Port of Singapore', 'Port of Colombo', 'Port of Piraeus', 'Port of Rotterdam']],
            ['name' => 'Intra-Asia', 'type' => 'container', 'ships' => 4, 'ports' => ['Port of Tokyo', 'Port of Busan', 'Port of Shanghai', 'Port of Hong Kong', 'Port of Singapore']],
            ['name' => 'Middle East-Asia', 'type' => 'tanker', 'ships' => 4, 'ports' => ['Port of Dubai', 'Port of Jebel Ali', 'Port of Mumbai', 'Port of Singapore', 'Port of Shanghai']],
            ['name' => 'Africa Feeder', 'type' => 'bulk', 'ships' => 3, 'ports' => ['Port of Durban', 'Port of Mombasa', 'Port of Dar es Salaam', 'Port of Cape Town']],
            ['name' => 'South America East Coast', 'type' => 'container', 'ships' => 3, 'ports' => ['Port of Santos', 'Port of Rio de Janeiro', 'Port of Buenos Aires']],
            ['name' => 'North America West Coast', 'type' => 'container', 'ships' => 3, 'ports' => ['Port of Los Angeles', 'Port of Oakland', 'Port of Seattle', 'Port of Vancouver']],
            ['name' => 'Caribbean-Central America', 'type' => 'container', 'ships' => 3, 'ports' => ['Port of Miami', 'Port of Colon', 'Port of Cartagena', 'Port of Veracruz']],
            ['name' => 'Mediterranean Feeder', 'type' => 'container', 'ships' => 3, 'ports' => ['Port of Piraeus', 'Port of Genoa', 'Port of Barcelona', 'Port of Valencia']],
            ['name' => 'Indonesia Domestic', 'type' => 'bulk', 'ships' => 3, 'ports' => ['Port of Tanjung Priok', 'Port of Tanjung Perak', 'Port of Makassar', 'Port of Belawan']],
        ];

        $allNames = collect($routeDefs)->pluck('ports')->flatten()->unique();
        $portMap = Port::whereIn('name', $allNames)->get()->keyBy('name');

        $routes = [];
        foreach ($routeDefs as $def) {
            $waypoints = [];
            foreach ($def['ports'] as $name) {
                if ($p = $portMap->get($name)) {
                    $waypoints[] = [(float) $p->latitude, (float) $p->longitude];
                }
            }
            if (count($waypoints) >= 2) {
                $isIntra = in_array($def['name'], ['Intra-Asia', 'Indonesia Domestic']);
                $routes[] = [
                    'name' => $def['name'],
                    'type' => $def['type'],
                    'waypoints' => $waypoints,
                    'ships' => $def['ships'],
                    'speed' => $isIntra ? 0.035 : 0.025,
                ];
            }
        }

        $liveVessels = [];
        $apiStatus = 'inactive';

        try {
            if ($vesselApi->isKeyValid()) {
                $apiStatus = 'active';
                $mmsiList = Cache::remember('vesselapi.tracked_mmsi', 3600, function () use ($vesselApi) {
                    $vessels = $vesselApi->searchVessels('MAERSK', 15);

                    return collect($vessels)->pluck('mmsi')->take(10)->toArray();
                });

                if (! empty($mmsiList)) {
                    $positions = $vesselApi->getMultiplePositions($mmsiList);
                    $liveVessels = collect($positions)->map(function ($pos) {
                        return [
                            'mmsi' => $pos['mmsi'] ?? '',
                            'name' => $pos['vessel_name'] ?? 'Unknown',
                            'latitude' => $pos['latitude'] ?? null,
                            'longitude' => $pos['longitude'] ?? null,
                            'speed' => $pos['sog'] ?? 0,
                            'heading' => $pos['cog'] ?? ($pos['heading'] ?? 0),
                            'destination' => '',
                            'status' => $pos['nav_status'] ?? 0,
                        ];
                    })->filter(fn ($v) => $v['latitude'] && $v['longitude'])->values()->toArray();
                }
            } else {
                $apiStatus = 'invalid_key';
            }
        } catch (\Exception $e) {
            $apiStatus = 'error';
            Log::warning('VesselAPI fetch failed, using simulation: '.$e->getMessage());
        }

        return view('portmap.index', [
            'portTypes' => Cache::remember('portmap.types', 3600, fn () => Port::whereNotNull('port_type')->distinct()->orderBy('port_type')->pluck('port_type')
            ),
            'routes' => $routes,
            'liveVessels' => $liveVessels,
            'usingLiveData' => ! empty($liveVessels),
            'apiStatus' => $apiStatus,
        ]);
    }

    public function ports(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $search = $request->get('search');
        $name = $request->get('name');
        $country = $request->get('country');

        $cacheKey = 'portmap.ports.'.md5(serialize(compact('type', 'search', 'name', 'country')));

        $data = Cache::remember($cacheKey, 300, function () use ($type, $search, $name, $country) {
            $q = Port::query();

            if ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            }

            if ($name) {
                $q->where('name', 'like', "%{$name}%");
            }

            if ($country) {
                $q->where('country', 'like', "%{$country}%");
            }

            if ($type) {
                $q->where('port_type', $type);
            }

            return $q->get()->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'country' => $p->country,
                'latitude' => (float) $p->latitude,
                'longitude' => (float) $p->longitude,
                'port_type' => $p->port_type,
            ]);
        });

        return response()->json($data);
    }

    public function vessels(VesselApiService $vesselApi): JsonResponse
    {
        $liveVessels = [];

        try {
            if ($vesselApi->isKeyValid()) {
                $mmsiList = Cache::remember('vesselapi.tracked_mmsi', 3600, function () use ($vesselApi) {
                    $vessels = $vesselApi->searchVessels('MAERSK', 15);

                    return collect($vessels)->pluck('mmsi')->take(10)->toArray();
                });

                if (! empty($mmsiList)) {
                    $positions = $vesselApi->getMultiplePositions($mmsiList);
                    $liveVessels = collect($positions)->map(function ($pos) {
                        return [
                            'mmsi' => $pos['mmsi'] ?? '',
                            'name' => $pos['vessel_name'] ?? 'Unknown',
                            'latitude' => $pos['latitude'] ?? null,
                            'longitude' => $pos['longitude'] ?? null,
                            'speed' => $pos['sog'] ?? 0,
                            'heading' => $pos['cog'] ?? ($pos['heading'] ?? 0),
                            'destination' => '',
                            'status' => $pos['nav_status'] ?? 0,
                        ];
                    })->filter(fn ($v) => $v['latitude'] && $v['longitude'])->values()->toArray();
                }
            }
        } catch (\Exception $e) {
            Log::warning('VesselAPI vessels endpoint failed: '.$e->getMessage());
        }

        return response()->json([
            'live' => $liveVessels,
            'using_live_data' => ! empty($liveVessels),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
