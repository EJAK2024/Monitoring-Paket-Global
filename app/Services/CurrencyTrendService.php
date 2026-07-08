<?php

namespace App\Services;

use App\Contracts\CurrencyTrendProviderInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Historical exchange-rate trends.
 *
 * Uses the free, key-less fawazahmed0 currency API (backed by jsDelivr) which
 * exposes daily snapshots for a very large set of currencies. This lets us build
 * real "currency trend" charts without a paid FX data plan.
 */
class CurrencyTrendService implements CurrencyTrendProviderInterface
{
    protected const BASE_URL = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@';

    /**
     * @return array{date: string, rate: float}[]
     */
    public function series(string $currency, int $days = 90, string $base = 'usd'): array
    {
        $currency = strtolower($currency);
        $base = strtolower($base);

        if ($currency === $base) {
            return [];
        }

        $cacheKey = "currency.trend.{$base}.{$currency}.{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($currency, $days, $base) {
            $end = now();
            $start = now()->subDays($days);

            $points = [];

            // Sample roughly weekly to keep request count reasonable.
            $step = max(1, (int) ceil($days / 24));
            for ($date = $start->copy(); $date->lte($end); $date->addDays($step)) {
                $point = $this->fetchOn($date, $base, $currency);
                if ($point !== null) {
                    $points[] = $point;
                }
            }

            // Always include today's latest rate.
            $latest = $this->fetchLatest($base, $currency);
            if ($latest !== null) {
                $points[] = $latest;
            }

            return $points;
        });
    }

    protected function fetchOn(Carbon $date, string $base, string $currency): ?array
    {
        $stamp = $date->format('Y-m-d');
        $url = self::BASE_URL."{$stamp}/v1/currencies/{$base}.json";

        try {
            $response = Http::timeout(10)->get($url);
            if ($response->failed()) {
                return null;
            }
            $rate = $response->json()[$base][$currency] ?? null;
            if (! is_numeric($rate)) {
                return null;
            }

            return ['date' => $stamp, 'rate' => (float) $rate];
        } catch (\Exception $e) {
            Log::warning("CurrencyTrend fetch failed on {$stamp}: {$e->getMessage()}");

            return null;
        }
    }

    protected function fetchLatest(string $base, string $currency): ?array
    {
        $url = self::BASE_URL.'latest/v1/currencies/'.$base.'.json';

        try {
            $response = Http::timeout(10)->get($url);
            if ($response->failed()) {
                return null;
            }
            $rate = $response->json()[$base][$currency] ?? null;
            if (! is_numeric($rate)) {
                return null;
            }

            return ['date' => now()->format('Y-m-d'), 'rate' => (float) $rate];
        } catch (\Exception $e) {
            Log::warning("CurrencyTrend latest fetch failed: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Percentage change between the oldest and newest sampled rate.
     */
    public function changePct(array $series): float
    {
        if (count($series) < 2) {
            return 0;
        }

        $first = $series[0]['rate'];
        $last = end($series)['rate'];

        if ($first == 0) {
            return 0;
        }

        return round(($last - $first) / $first * 100, 2);
    }
}
