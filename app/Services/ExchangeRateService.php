<?php

namespace App\Services;

use App\Contracts\ExchangeRateProviderInterface;
use Illuminate\Support\Facades\Http;

class ExchangeRateService implements ExchangeRateProviderInterface
{
    public function getRates(string $base = 'USD'): ?array
    {
        $apiKey = config('services.exchangerate.key');

        if (! $apiKey) {
            return $this->fallbackRates($base);
        }

        $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$base}");

        if ($response->failed()) {
            return $this->fallbackRates($base);
        }

        return $response->json()['conversion_rates'] ?? null;
    }

    private function fallbackRates(string $base): array
    {
        $path = database_path('data/fallback_rates.json');
        $common = file_exists($path) ? (json_decode(file_get_contents($path), true) ?? []) : [];

        if ($base === 'USD') {
            return $common;
        }

        $baseRate = $common[$base] ?? 1;
        $rates = [];
        foreach ($common as $currency => $rate) {
            $rates[$currency] = round($rate / $baseRate, 6);
        }

        return $rates;
    }
}
