<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\RiskScore;
use App\Services\RiskService;
use Illuminate\Http\Request;

class RiskController extends Controller
{
    public function index(Request $request, RiskService $risk)
    {
        $query = RiskScore::with('country');

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        $stored = $query->latest('calculated_at')->get();

        if ($stored->isNotEmpty()) {
            return response()->json($stored);
        }

        $countries = Country::when($request->filled('country_id'), function ($q) use ($request) {
            $q->where('id', $request->country_id);
        })->get();

        $risks = $countries->map(function (Country $country) use ($risk) {
            return $this->persist($country, $risk->calculate($country));
        });

        return response()->json($risks);
    }

    private function persist(Country $country, array $data): array
    {
        $existing = RiskScore::where('country_id', $country->id)
            ->whereDate('calculated_at', now()->toDateString())
            ->first();

        $payload = [
            'weather_risk' => $data['weather_risk'],
            'inflation_risk' => $data['inflation_risk'],
            'news_sentiment_risk' => $data['news_sentiment_risk'],
            'currency_risk' => $data['currency_risk'],
            'total_score' => $data['total_score'],
            'risk_level' => $data['risk_level'],
            'calculated_at' => now(),
        ];

        if ($existing) {
            $existing->update($payload);

            return $existing->toArray();
        }

        return RiskScore::create(array_merge(['country_id' => $country->id], $payload))->toArray();
    }
}
