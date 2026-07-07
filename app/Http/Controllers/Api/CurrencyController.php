<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $base = $request->base ?? 'USD';
        $rates = app(ExchangeRateService::class)->getRates($base);

        return response()->json($rates);
    }
}
