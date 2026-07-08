<?php

namespace App\Contracts;

interface ExchangeRateProviderInterface
{
    public function getRates(string $base = 'USD'): ?array;
}
