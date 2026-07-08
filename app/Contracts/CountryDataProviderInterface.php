<?php

namespace App\Contracts;

interface CountryDataProviderInterface
{
    public function getAll(): array;

    public function getFallbackList(): array;
}
