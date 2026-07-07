<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class GNewsService
{
    public function fetch(string $keyword = 'logistics trade shipping economy', int $max = 10): array
    {
        $apiKey = config('services.gnews.key');

        if (! $apiKey) {
            return [];
        }

        $response = Http::get('https://gnews.io/api/v4/search', [
            'q' => $keyword,
            'lang' => 'en',
            'max' => $max,
            'apikey' => $apiKey,
        ]);

        if ($response->failed()) {
            return [];
        }

        $articles = $response->json()['articles'] ?? [];

        return collect($articles)->map(function ($article) {
            return [
                'title' => $article['title'] ?? '',
                'description' => $article['description'] ?? '',
                'source' => $article['source']['name'] ?? null,
                'url' => $article['url'] ?? '',
                'published_at' => isset($article['publishedAt']) ? Carbon::parse($article['publishedAt'])->toDateTimeString() : null,
            ];
        })->toArray();
    }
}
