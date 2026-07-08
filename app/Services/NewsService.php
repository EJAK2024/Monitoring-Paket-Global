<?php

namespace App\Services;

use App\Contracts\NewsProviderInterface;
use App\Contracts\SentimentAnalyzerInterface;
use App\Models\Country;
use App\Models\NewsCache;

class NewsService
{
    public function __construct(
        protected NewsProviderInterface $gnews,
        protected SentimentAnalyzerInterface $sentiment,
    ) {}

    public function search(?int $countryId, ?string $keyword)
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

    public function refreshCacheIfStale(?int $countryId, string $keyword, bool $force = false): void
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

        $gnews = $this->gnews->fetch($searchKeyword);

        if (empty($gnews)) {
            return;
        }

        $existingUrls = NewsCache::when($countryId, fn ($q) => $q->where('country_id', $countryId))
            ->pluck('url')
            ->toArray();

        foreach ($gnews as $article) {
            if (in_array($article['url'], $existingUrls)) {
                continue;
            }

            $result = $this->sentiment->analyze(
                ($article['title'] ?? '').' '.($article['description'] ?? '')
            );
            $article['sentiment'] = $result['sentiment'];
            $article['country_id'] = $countryId;
            NewsCache::create($article);
        }
    }
}
