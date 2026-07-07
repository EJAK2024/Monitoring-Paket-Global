<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ExchangeRateService
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
        $common = [
            'USD' => 1.0, 'EUR' => 0.92, 'GBP' => 0.79,
            'JPY' => 149.5, 'CNY' => 7.24, 'IDR' => 15700,
            'AUD' => 1.54, 'SGD' => 1.35, 'MYR' => 4.72,
        ];

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
