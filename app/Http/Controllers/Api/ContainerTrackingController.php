<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Container;
use App\Services\ContainerTrackingService;
use Illuminate\Http\Request;

class ContainerTrackingController extends Controller
{
    public function __construct(
        protected ContainerTrackingService $service,
    ) {}

    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2']);

        $containers = $this->service->search($request->q);

        return response()->json($containers);
    }

    public function show(string $containerId)
    {
        $container = $this->service->detail($containerId);

        if (! $container) {
            return response()->json(['message' => 'Container not found'], 404);
        }

        return response()->json($container);
    }

    public function timeline(string $containerId)
    {
        $events = $this->service->timeline($containerId);

        if ($events->isEmpty()) {
            return response()->json(['message' => 'Container not found'], 404);
        }

        return response()->json($events);
    }

    public function stats()
    {
        return response()->json($this->service->statusStats());
    }

    public function index(Request $request)
    {
        $query = Container::with('vessel')->orderBy('updated_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('size')) {
            $query->where('size', $request->size);
        }

        $perPage = min((int) $request->per_page ?: 20, 100);

        return response()->json($query->paginate($perPage));
    }
}
