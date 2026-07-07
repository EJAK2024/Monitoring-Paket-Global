<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RestCountriesService
{
    public function getAll(): array
    {
        $response = Http::get('https://restcountries.com/v3.1/all');

        if ($response->failed()) {
            return [];
        }

        return collect($response->json())->map(function ($country) {
            return [
                'name' => $country['name']['common'] ?? null,
                'iso_code' => $country['cca2'] ?? null,
                'iso_code_3' => $country['cca3'] ?? null,
                'currency_code' => array_key_first($country['currencies'] ?? []),
                'region' => $country['region'] ?? null,
                'language' => array_values($country['languages'] ?? [])[0] ?? null,
                'population' => $country['population'] ?? null,
            ];
        })->toArray();
    }
}
