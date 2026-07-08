<?php

namespace App\Contracts;

interface EconomicDataProviderInterface
{
    public function getCountryData(string $isoCode): ?array;

    public function batchIndicators(string $isoCode): array;

    public function getCountryInfo(string $isoCode): ?array;

    public function indicatorSeries(string $isoCode, string $key, int $years = 10): array;
}
