<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\NewsCache;
use App\Services\GNewsService;
use App\Services\SentimentService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $countryId = $request->filled('country_id') ? (int) $request->country_id : null;
        $keyword = $request->keyword ?? 'logistics trade shipping economy';

        $this->refreshCacheIfStale($countryId, $keyword);

        $results = $this->searchNews($countryId, $request->keyword);

        if ($results->isEmpty() && $request->filled('keyword')) {
            $this->refreshCacheIfStale($countryId, $keyword, true);
            $results = $this->searchNews($countryId, $request->keyword);
        }

        return response()->json($results);
    }

    private function searchNews(?int $countryId, ?string $keyword)
    {
        $query = NewsCache::query();

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        if ($keyword) {
            $words = preg_split('/\s+/', trim($keyword));
            $query->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('title', 'like', "%{$word}%")
                        ->orWhere('description', 'like', "%{$word}%");
                }
            });
        }

        return $query->latest('published_at')->paginate(20);
    }

    private function refreshCacheIfStale(?int $countryId, string $keyword, bool $force = false): void
    {
        $apiKey = config('services.gnews.key');
        if (! $apiKey) {
            return;
        }

        if (! $force) {
            $lastRefresh = NewsCache::when($countryId, fn ($q) => $q->where('country_id', $countryId))
                ->latest('updated_at')
                ->value('updated_at');

            if ($lastRefresh && $lastRefresh->diffInMinutes(now()) < 15) {
                return;
            }
        }

        $searchKeyword = $keyword;
        if ($countryId) {
            $country = Country::find($countryId);
            if ($country) {
                $searchKeyword = $country->name.' '.$keyword;
            }
        }

        $gnews = app(GNewsService::class)->fetch($searchKeyword);

        if (empty($gnews)) {
            return;
        }

        $sentiment = app(SentimentService::class);

        $existingUrls = NewsCache::when($countryId, fn ($q) => $q->where('country_id', $countryId))
            ->pluck('url')
            ->toArray();

        foreach ($gnews as $article) {
            if (in_array($article['url'], $existingUrls)) {
                continue;
            }

            $result = $sentiment->analyze(
                ($article['title'] ?? '').' '.($article['description'] ?? '')
            );
            $article['sentiment'] = $result['sentiment'];
            $article['country_id'] = $countryId;
            NewsCache::create($article);
        }
    }
}
