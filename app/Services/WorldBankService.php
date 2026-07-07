<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorldBankService
{
    public function getCountryData(string $isoCode): ?array
    {
        $cacheKey = "worldbank.country.{$isoCode}";

        return Cache::remember($cacheKey, config('worldbank.cache_ttl'), function () use ($isoCode) {
            $info = $this->fetchCountryInfo($isoCode);
            if (! $info) {
                return null;
            }

            $indicators = $this->fetchIndicators($isoCode);

            return array_merge($info, $indicators);
        });
    }

    public function batchIndicators(string $isoCode): array
    {
        $result = [];
        foreach (config('worldbank.indicators') as $key => $code) {
            $result[$key] = $this->fetchIndicator($isoCode, $code);
        }

        return $result;
    }

    public function getCountryInfo(string $isoCode): ?array
    {
        return $this->fetchCountryInfo($isoCode);
    }

    private function fetchCountryInfo(string $isoCode): ?array
    {
        try {
            $response = Http::timeout(config('worldbank.timeout'))
                ->get(config('worldbank.base_url')."/country/{$isoCode}", [
                    'format' => 'json',
                ])->json();
        } catch (\Exception $e) {
            Log::warning("WorldBank API (info) failed for {$isoCode}: {$e->getMessage()}");

            return null;
        }

        if (empty($response[1][0])) {
            return null;
        }

        $data = $response[1][0];

        return [
            'name' => $data['name'] ?? null,
            'region' => $data['region']['value'] ?? null,
            'capital_city' => $data['capitalCity'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ];
    }

    private function fetchIndicators(string $isoCode): array
    {
        $indicators = config('worldbank.indicators');

        $result = [];
        foreach ($indicators as $key => $code) {
            $result[$key] = $this->fetchIndicator($isoCode, $code);
        }

        return $result;
    }

    private function fetchIndicator(string $isoCode, string $indicatorCode): ?float
    {
        try {
            $response = Http::timeout(config('worldbank.timeout'))
                ->get(config('worldbank.base_url')."/country/{$isoCode}/indicator/{$indicatorCode}", [
                    'format' => 'json',
                    'per_page' => 1,
                ])->json();
        } catch (\Exception $e) {
            Log::warning("WorldBank API (indicator) failed for {$isoCode}/{$indicatorCode}: {$e->getMessage()}");

            return null;
        }

        if (empty($response[1][0])) {
            return null;
        }

        $value = $response[1][0]['value'] ?? null;

        if ($value === null) {
            return null;
        }

        $numeric = (float) $value;

        if (in_array($indicatorCode, array_values(config('worldbank.indicators')))) {
            $codes = config('worldbank.normalize');
            $indicatorKeys = array_flip(config('worldbank.indicators'));
            $key = $indicatorKeys[$indicatorCode] ?? null;
            if ($key && in_array($key, $codes, true)) {
                $numeric = round($numeric / 1e9, 2);
            } else {
                $numeric = round($numeric, 2);
            }
        }

        return $numeric;
    }
}
