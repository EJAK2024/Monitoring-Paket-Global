<?php

namespace App\Services;

use App\Contracts\CurrencyTrendProviderInterface;
use App\Contracts\EconomicDataProviderInterface;
use App\Contracts\NewsProviderInterface;
use App\Contracts\RiskEngineInterface;
use App\Contracts\SentimentAnalyzerInterface;
use App\Contracts\WeatherServiceInterface;
use App\Models\Country;
use App\Models\NewsCache;
use Illuminate\Support\Facades\Log;

class RiskService implements RiskEngineInterface
{
    public function __construct(
        protected WeatherServiceInterface $weather,
        protected EconomicDataProviderInterface $worldBank,
        protected NewsProviderInterface $gnews,
        protected SentimentAnalyzerInterface $sentiment,
        protected CurrencyTrendProviderInterface $currencyTrend,
    ) {}

    public function calculate(Country $country): array
    {
        $weatherScore = $this->weatherScore($country);
        $inflationScore = $this->inflationScore($country);
        $fxScore = $this->fxScore($country);
        $newsResult = $this->newsScore($country);

        $total = (int) round(
            $weatherScore['score'] * 0.25 +
            $inflationScore['score'] * 0.25 +
            $fxScore['score'] * 0.25 +
            $newsResult['score'] * 0.25
        );

        $level = $total <= 30 ? 'low' : ($total <= 60 ? 'medium' : 'high');

        return [
            'country_id' => $country->id,
            'country' => $country->only(['id', 'name', 'iso_code']),
            'weather_risk' => $weatherScore['score'],
            'storm_risk' => $weatherScore['storm_risk'],
            'inflation_risk' => $inflationScore['score'],
            'news_sentiment_risk' => $newsResult['score'],
            'currency_risk' => $fxScore['score'],
            'total_score' => $total,
            'risk_level' => $level,
            'inflation' => $inflationScore['rate'],
            'news' => $newsResult['breakdown'],
            'fx' => $fxScore['details'],
        ];
    }

    public function historicalSeries(Country $country, int $months = 12): array
    {
        $weatherScore = $this->weatherScore($country);
        $inflationScore = $this->inflationScore($country);
        $newsResult = $this->newsScore($country);

        $currency = strtoupper($country->currency_code ?? '');
        $series = [];

        if ($currency && $currency !== 'USD') {
            $fx = $this->currencyTrend->series($currency, $months * 30);
            foreach ($fx as $point) {
                $seriesRates = array_column($fx, 'rate');
                $cv = $this->calcCV($seriesRates);
                $change = $this->currencyTrend->changePct($fx);
                $fxScore = $this->fxScoreFromCV($cv, $change);

                $total = (int) round(
                    $weatherScore['score'] * 0.25 +
                    $inflationScore['score'] * 0.25 +
                    $fxScore * 0.25 +
                    $newsResult['score'] * 0.25
                );

                $series[] = [
                    'date' => $point['date'],
                    'total_score' => $total,
                    'weather_risk' => $weatherScore['score'],
                    'inflation_risk' => $inflationScore['score'],
                    'news_sentiment_risk' => $newsResult['score'],
                    'currency_risk' => $fxScore,
                ];
            }
        }

        if (empty($series)) {
            $series[] = [
                'date' => now()->format('Y-m-d'),
                'total_score' => (int) round(
                    $weatherScore['score'] * 0.25 +
                    $inflationScore['score'] * 0.25 +
                    $newsResult['score'] * 0.25
                ),
                'weather_risk' => $weatherScore['score'],
                'inflation_risk' => $inflationScore['score'],
                'news_sentiment_risk' => $newsResult['score'],
                'currency_risk' => 0,
            ];
        }

        return $series;
    }

    protected function weatherScore(Country $country): array
    {
        $weather = $this->weather->getWeather($country->name) ?? [];

        return [
            'score' => (int) ($weather['weather_risk'] ?? 5),
            'storm_risk' => (int) ($weather['storm_risk'] ?? 0),
        ];
    }

    protected function inflationScore(Country $country): array
    {
        $rate = $country->inflation ?? $this->fetchInflation($country);

        $score = match (true) {
            $rate === null => 0,
            $rate < 2 => 10,
            $rate <= 4 => 25,
            $rate <= 6 => 50,
            $rate <= 8 => 75,
            default => 90,
        };

        $trendAdjustment = $this->inflationTrend($country, $rate);
        $score = (int) min(100, max(0, $score + $trendAdjustment));

        return ['score' => $score, 'rate' => $rate];
    }

    protected function inflationTrend(Country $country, ?float $currentRate): int
    {
        if ($currentRate === null || ! $country->iso_code) {
            return 0;
        }

        try {
            $series = $this->worldBank->indicatorSeries($country->iso_code, 'inflation', 2);
            $values = array_filter(array_column($series, 'value'));

            if (count($values) < 2) {
                return 0;
            }

            $prevRate = (float) end($values);
            $current = (float) $values[array_key_last($values)];

            if ($current > $prevRate * 1.05) {
                return 20;
            }
            if ($current < $prevRate * 0.95) {
                return -15;
            }

            return 0;
        } catch (\Exception $e) {
            Log::warning("RiskService inflation trend failed: {$e->getMessage()}");

            return 0;
        }
    }

    protected function fxScore(Country $country): array
    {
        $currency = strtoupper($country->currency_code ?? '');
        $defaultDetails = ['cv' => 0, 'change_pct' => 0, 'score' => 0];

        if (! $currency || $currency === 'USD') {
            return ['score' => 0, 'details' => $defaultDetails];
        }

        try {
            $series = $this->currencyTrend->series($currency, 30);
        } catch (\Exception $e) {
            Log::warning("RiskService FX series failed: {$e->getMessage()}");

            return ['score' => 15, 'details' => $defaultDetails];
        }

        if (count($series) < 2) {
            return ['score' => 15, 'details' => $defaultDetails];
        }

        $rates = array_column($series, 'rate');
        $cv = $this->calcCV($rates);
        $change = $this->currencyTrend->changePct($series);
        $score = $this->fxScoreFromCV($cv, $change);

        return ['score' => $score, 'details' => ['cv' => round($cv, 2), 'change_pct' => $change, 'score' => $score]];
    }

    protected function fxScoreFromCV(float $cv, float $changePct): int
    {
        $base = match (true) {
            $cv < 1 => 15,
            $cv <= 2 => 30,
            $cv <= 3 => 50,
            $cv <= 5 => 70,
            default => 85,
        };

        $direction = match (true) {
            $changePct < -5 => 15,
            $changePct > 5 => -10,
            default => 0,
        };

        return (int) min(100, max(0, $base + $direction));
    }

    protected function calcCV(array $rates): float
    {
        $count = count($rates);
        if ($count < 3) {
            return 0;
        }

        $mean = array_sum($rates) / $count;
        if ($mean == 0) {
            return 0;
        }

        $variance = 0;
        foreach ($rates as $rate) {
            $variance += ($rate - $mean) ** 2;
        }

        return (sqrt($variance / $count) / $mean) * 100;
    }

    protected function newsScore(Country $country): array
    {
        $default = [
            'score' => 0,
            'breakdown' => ['positive' => 0, 'negative' => 0, 'neutral' => 0, 'crisis' => 0, 'total' => 0],
        ];

        $articles = NewsCache::where('country_id', $country->id)->get();
        $fetched = [];

        if ($articles->isEmpty()) {
            $apiKey = config('services.gnews.key');
            if (! $apiKey) {
                return $default;
            }

            try {
                $fetched = $this->gnews->fetch($country->name, 15);
            } catch (\Exception $e) {
                Log::warning("RiskService news fetch failed: {$e->getMessage()}");

                return $default;
            }

            if (empty($fetched)) {
                return $default;
            }

            return $this->calculateNewsScore($fetched, true);
        }

        return $this->calculateNewsScore($articles, false);
    }

    protected function calculateNewsScore($articles, bool $isRaw): array
    {
        $weights = ['positive' => 20, 'neutral' => 50, 'negative' => 75, 'crisis' => 95];
        $counts = ['positive' => 0, 'negative' => 0, 'neutral' => 0, 'crisis' => 0];
        $total = 0;

        foreach ($articles as $a) {
            $title = $isRaw ? ($a['title'] ?? '') : ($a->title ?? '');
            $desc = $isRaw ? ($a['description'] ?? '') : ($a->description ?? '');
            $text = $title.' '.$desc;

            $result = $this->sentiment->analyze($text);
            $sentiment = $result['sentiment'];

            if (! isset($counts[$sentiment])) {
                $sentiment = 'neutral';
            }

            $counts[$sentiment]++;
            $total++;
        }

        if ($total === 0) {
            return [
                'score' => 0,
                'breakdown' => array_merge($counts, ['total' => 0]),
            ];
        }

        $weightedSum = 0;
        foreach ($counts as $type => $count) {
            $weightedSum += $count * ($weights[$type] ?? 50);
        }

        return [
            'score' => (int) round($weightedSum / $total),
            'breakdown' => array_merge($counts, ['total' => $total]),
        ];
    }

    protected function fetchInflation(Country $country): ?float
    {
        if (! $country->iso_code) {
            return null;
        }

        try {
            $data = $this->worldBank->getCountryData($country->iso_code);

            return $data['inflation'] ?? null;
        } catch (\Exception $e) {
            Log::warning("RiskService inflation fetch failed: {$e->getMessage()}");

            return null;
        }
    }
}
