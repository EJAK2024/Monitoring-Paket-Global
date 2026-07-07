<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\RiskScore;
use Illuminate\Http\Request;

class RiskController extends Controller
{
    public function index(Request $request)
    {
        $query = RiskScore::with('country');

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        $risks = $query->latest('calculated_at')->get();

        if ($risks->isEmpty()) {
            $countries = Country::when($request->filled('country_id'), function ($q) use ($request) {
                $q->where('id', $request->country_id);
            })->get();

            $risks = $countries->map(function ($country) {
                return $this->calculateRisk($country);
            });
        }

        return response()->json($risks);
    }

    private function calculateRisk(Country $country): array
    {
        $weatherRisk = $country->weather_risk ?? 0;
        $inflationRisk = min(($country->inflation ?? 0) * 5, 100);
        $newsRisk = 0;
        $currencyRisk = 0;

        $total = round(
            $weatherRisk * 0.3 +
            $inflationRisk * 0.2 +
            $newsRisk * 0.4 +
            $currencyRisk * 0.1
        );

        $level = $total <= 30 ? 'low' : ($total <= 60 ? 'medium' : 'high');

        return [
            'country' => $country->only(['id', 'name', 'iso_code']),
            'weather_risk' => $weatherRisk,
            'inflation_risk' => $inflationRisk,
            'news_sentiment_risk' => $newsRisk,
            'currency_risk' => $currencyRisk,
            'total_score' => $total,
            'risk_level' => $level,
        ];
    }
}
