<?php

namespace App\Contracts;

interface WeatherServiceInterface
{
    public function getWeather(string $city): ?array;

    public function enrich(array $current): array;

    public function weatherRisk(int $weatherCode, float $temperature, float $wind, int $visibility): int;

    public function stormRisk(int $weatherCode, float $wind, float $precip): int;

    public function stormLevel(int $risk): string;
}
