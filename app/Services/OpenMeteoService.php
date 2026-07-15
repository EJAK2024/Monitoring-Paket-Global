<?php

namespace App\Services;

use App\Contracts\WeatherServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenMeteoService implements WeatherServiceInterface
{
    private const FALLBACK_COORDS = [
        'Antarctica' => ['latitude' => -82.8628, 'longitude' => 135.0],
    ];

    public function getWeather(string $city): ?array
    {
        $cacheKey = 'weather.' . strtolower(str_replace(' ', '_', $city));
        return Cache::remember($cacheKey, 1800, function () use ($city) {
        $coords = self::FALLBACK_COORDS[$city] ?? null;

        if ($coords) {
            $lat = $coords['latitude'];
            $lon = $coords['longitude'];
        } else {
            try {
                $geo = Http::timeout(10)->get('https://geocoding-api.open-meteo.com/v1/search', [
                    'name' => $city,
                    'count' => 1,
                    'language' => 'en',
                    'format' => 'json',
                ])->json();
            } catch (\Exception $e) {
                Log::warning("OpenMeteo geocoding failed for {$city}: {$e->getMessage()}");

                return null;
            }

            if (empty($geo['results'][0])) {
                return null;
            }

            $lat = $geo['results'][0]['latitude'];
            $lon = $geo['results'][0]['longitude'];
        }

        try {
            $weather = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lon,
                'current' => 'temperature_2m,precipitation,wind_speed_10m,weather_code',
                'timezone' => 'auto',
            ])->json();
        } catch (\Exception $e) {
            Log::warning("OpenMeteo forecast failed for {$city} ({$lat},{$lon}): {$e->getMessage()}");

            return null;
        }

        $current = $weather['current'] ?? null;

        if (! $current) {
            return null;
        }

        return $this->enrich($current);
        });
    }

    public function enrich(array $current): array
    {
        $weatherCode = (int) ($current['weather_code'] ?? 0);
        $temp = (float) ($current['temperature_2m'] ?? 0);
        $wind = (float) ($current['wind_speed_10m'] ?? 0);
        $precip = (float) ($current['precipitation'] ?? 0);
        $visibility = $this->estimateVisibility($weatherCode);

        $current['weather_risk'] = $this->weatherRisk($weatherCode, $temp, $wind, $visibility);
        $current['storm_risk'] = $this->stormRisk($weatherCode, $wind, $precip);
        $current['storm_level'] = $this->stormLevel($current['storm_risk']);
        $current['visibility_estimate'] = $visibility;

        return $current;
    }

    public function weatherRisk(int $weatherCode, float $temperature, float $wind, int $visibility): int
    {
        $base = match (true) {
            $weatherCode >= 95 => 95,   // thunderstorm
            $weatherCode >= 80 => 85,   // storm
            $weatherCode >= 71 => 75,   // snow
            $weatherCode >= 61 => 70,   // heavy rain
            $weatherCode >= 51 => 50,   // light rain
            $weatherCode >= 45 => 40,   // fog/mist
            $weatherCode >= 3 => 25,   // cloudy
            default => 10,   // clear
        };

        $tempPenalty = match (true) {
            $temperature < 0 || $temperature > 40 => 15,
            default => 0,
        };

        $windPenalty = match (true) {
            $wind > 50 => 25,
            default => 0,
        };

        $visPenalty = match (true) {
            $visibility < 1000 => 35,
            $visibility < 5000 => 20,
            default => 0,
        };

        return (int) min(100, $base + $tempPenalty + $windPenalty + $visPenalty);
    }

    public function stormRisk(int $weatherCode, float $wind, float $precip): int
    {
        $stormCode = match (true) {
            $weatherCode >= 96 => 95,
            $weatherCode >= 95 => 85,
            $weatherCode >= 86 => 70,
            $weatherCode >= 80 => 55,
            $weatherCode >= 71 => 50,
            default => 0,
        };

        $windComponent = match (true) {
            $wind >= 70 => 90,
            $wind >= 50 => 70,
            $wind >= 38 => 50,
            $wind >= 25 => 25,
            default => 0,
        };

        return (int) min(100, max($stormCode, $windComponent));
    }

    public function stormLevel(int $risk): string
    {
        return $risk >= 60 ? 'High' : ($risk >= 30 ? 'Moderate' : 'Low');
    }

    private function estimateVisibility(int $weatherCode): int
    {
        return match (true) {
            $weatherCode >= 95 => 500,    // thunderstorm
            $weatherCode >= 80 => 1500,   // storm
            $weatherCode >= 71 => 2000,   // snow
            $weatherCode >= 61 => 5000,   // heavy rain
            $weatherCode >= 51 => 8000,   // light rain
            $weatherCode >= 45 => 500,    // fog/mist
            $weatherCode >= 3 => 10000,  // cloudy
            default => 15000,  // clear
        };
    }
}
