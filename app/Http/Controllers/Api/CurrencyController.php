<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ExchangeRateProviderInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(
        protected ExchangeRateProviderInterface $exchangeRates,
    ) {}

    public function index(Request $request)
    {
        $base = $request->base ?? 'USD';
        $rates = $this->exchangeRates->getRates($base);

        return response()->json($rates);
    }
}
