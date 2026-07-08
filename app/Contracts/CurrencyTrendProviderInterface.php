<?php

namespace App\Contracts;

interface CurrencyTrendProviderInterface
{
    public function series(string $currency, int $days = 90, string $base = 'usd'): array;

    public function changePct(array $series): float;
}
