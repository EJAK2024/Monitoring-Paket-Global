<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\CurrencyTrendService;
use App\Services\RiskService;
use App\Services\WorldBankService;
use Illuminate\Http\Request;

class VizController extends Controller
{
    public function gdp(Request $request, WorldBankService $worldBank)
    {
        $country = $this->country($request);
        if (! $country || ! $country->iso_code) {
            return response()->json(['country' => null, 'series' => []]);
        }

        $series = $worldBank->indicatorSeries($country->iso_code, 'gdp', (int) $request->years ?: 10);

        return response()->json(['country' => $country->name, 'series' => $series]);
    }

    public function inflation(Request $request, WorldBankService $worldBank)
    {
        $country = $this->country($request);
        if (! $country || ! $country->iso_code) {
            return response()->json(['country' => null, 'series' => []]);
        }

        $series = $worldBank->indicatorSeries($country->iso_code, 'inflation', (int) $request->years ?: 10);

        return response()->json(['country' => $country->name, 'series' => $series]);
    }

    public function currency(Request $request, CurrencyTrendService $currency)
    {
        $currencyCode = strtoupper($request->currency ?? $request->get('currency_code') ?? '');
        if (! $currencyCode) {
            $country = $this->country($request);
            $currencyCode = $country?->currency_code ? strtoupper($country->currency_code) : '';
        }

        if (! $currencyCode) {
            return response()->json(['currency' => null, 'series' => [], 'change_pct' => 0]);
        }

        $series = $currency->series($currencyCode, (int) $request->days ?: 90);

        return response()->json([
            'currency' => $currencyCode,
            'series' => $series,
            'change_pct' => $currency->changePct($series),
        ]);
    }

    public function risk(Request $request, RiskService $risk)
    {
        $country = $this->country($request);
        if (! $country) {
            return response()->json(['country' => null, 'series' => []]);
        }

        $series = $risk->historicalSeries($country, (int) $request->months ?: 12);

        return response()->json(['country' => $country->name, 'series' => $series]);
    }

    protected function country(Request $request): ?Country
    {
        if ($request->filled('country_id')) {
            return Country::find($request->country_id);
        }

        if ($request->filled('country')) {
            return Country::where('name', $request->country)->first();
        }

        return null;
    }
}
