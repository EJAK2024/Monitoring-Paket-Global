<?php

namespace App\Services;

use App\Contracts\CountryDataProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RestCountriesService implements CountryDataProviderInterface
{
    public function getAll(): array
    {
        $apiKey = config('services.restcountries.key');

        if ($apiKey) {
            $data = $this->fetchFromV5($apiKey);
            if (! empty($data)) {
                return $data;
            }
            Log::warning('REST Countries v5 API failed, using fallback list.');
        }

        return $this->getFallbackList();
    }

    private function fetchFromV5(string $apiKey): array
    {
        try {
            $all = [];
            $limit = 100;
            $offset = 0;

            do {
                $response = Http::timeout(30)
                    ->withToken($apiKey)
                    ->get('https://api.restcountries.com/countries/v5', [
                        'limit' => $limit,
                        'offset' => $offset,
                        'response_fields' => 'names.common,codes.alpha_2,codes.alpha_3,currencies,region,languages,population',
                    ]);

                if ($response->failed()) {
                    Log::warning('REST Countries v5 API failed at offset '.$offset.': '.$response->status());
                    break;
                }

                $body = $response->json();
                $objects = $body['data']['objects'] ?? [];

                if (empty($objects)) {
                    break;
                }

                $mapped = collect($objects)->map(function ($c) {
                    $currencies = $c['currencies'] ?? [];
                    $languages = $c['languages'] ?? [];

                    return [
                        'name' => $c['names']['common'] ?? null,
                        'iso_code' => $c['codes']['alpha_2'] ?? null,
                        'iso_code_3' => $c['codes']['alpha_3'] ?? null,
                        'currency_code' => is_array($currencies) && isset($currencies[0]['code']) ? $currencies[0]['code'] : null,
                        'region' => $c['region'] ?? null,
                        'language' => is_array($languages) && isset($languages[0]['bcp47']) ? $languages[0]['bcp47'] : null,
                        'population' => $c['population'] ?? null,
                    ];
                })->reject(fn ($c) => empty($c['iso_code']) || empty($c['name']))
                    ->values()
                    ->toArray();

                $all = array_merge($all, $mapped);
                $offset += $limit;
            } while (count($objects) === $limit);

            return $all;
        } catch (\Exception $e) {
            Log::warning('REST Countries v5 exception: '.$e->getMessage());

            return [];
        }
    }

    public function getFallbackList(): array
    {
        $path = database_path('data/fallback_countries.json');
        if (! file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }
}
