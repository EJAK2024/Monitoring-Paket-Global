<?php

namespace App\Contracts;

use App\Models\Country;

interface RiskEngineInterface
{
    public function calculate(Country $country): array;

    public function historicalSeries(Country $country, int $months = 12): array;
}
