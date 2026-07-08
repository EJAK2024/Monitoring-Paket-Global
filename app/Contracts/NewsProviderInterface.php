<?php

namespace App\Contracts;

interface NewsProviderInterface
{
    public function fetch(string $keyword = 'logistics trade shipping economy', int $max = 10): array;
}
