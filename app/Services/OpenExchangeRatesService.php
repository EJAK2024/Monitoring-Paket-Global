<?php

namespace App\Services;

use App\Contracts\CurrencyTrendProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenExchangeRatesService implements CurrencyTrendProviderInterface
{
    protected CurrencyTrendService $fallback;

    public function __construct()
    {
        $this->fallback = app(CurrencyTrendService::class);
    }

    public function series(string $currency, int $days = 90, string $base = 'usd'): array
    {
        $appId = config('services.openexchangerates.app_id');

        if (! $appId) {
            return $this->fallback->series($currency, $days, $base);
        }

        $points = [];
        $currency = strtolower($currency);
        $base = strtolower($base);

        try {
            $latest = Http::timeout(10)
                ->get('https://openexchangerates.org/api/latest.json', [
                    'app_id' => $appId,
                    'base' => $base,
                    'symbols' => strtoupper($currency),
                ])->json();

            $todayRate = $latest['rates'][strtoupper($currency)] ?? null;

            if ($todayRate !== null) {
                $points[] = ['date' => now()->format('Y-m-d'), 'rate' => (float) $todayRate];
            }
        } catch (\Exception $e) {
            Log::warning("OpenExchangeRates latest failed: {$e->getMessage()}");
        }

        $historical = $this->fallback->series($currency, $days, $base);
        $points = array_merge($historical, $points);

        return $points;
    }

    public function changePct(array $series): float
    {
        return $this->fallback->changePct($series);
    }

    public function coefficientOfVariation(array $series): float
    {
        if (count($series) < 3) {
            return 0;
        }

        $rates = array_column($series, 'rate');
        $mean = array_sum($rates) / count($rates);

        if ($mean == 0) {
            return 0;
        }

        $variance = 0;
        foreach ($rates as $rate) {
            $variance += ($rate - $mean) ** 2;
        }
        $variance /= count($rates);
        $stdDev = sqrt($variance);

        return ($stdDev / $mean) * 100;
    }

    public function directionPct(array $series): float
    {
        return $this->changePct($series);
    }
}
