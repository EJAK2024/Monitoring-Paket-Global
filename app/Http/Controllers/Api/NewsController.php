<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NewsService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __construct(
        protected NewsService $news,
    ) {}

    public function index(Request $request)
    {
        $countryId = $request->filled('country_id') ? (int) $request->country_id : null;
        $keyword = $request->keyword ?? 'logistics trade shipping economy';

        $this->news->refreshCacheIfStale($countryId, $keyword);

        $results = $this->news->search($countryId, $request->keyword);

        if ($results->isEmpty() && $request->filled('keyword')) {
            $this->news->refreshCacheIfStale($countryId, $keyword, true);
            $results = $this->news->search($countryId, $request->keyword);
        }

        return response()->json($results);
    }
}
