<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface PortRepositoryInterface
{
    public function getAll(): Collection;

    public function search(?string $term): Collection;

    public function getByCountry(string $country): Collection;

    public function getByCountryCode(string $code): Collection;

    public function countByCountry(): Collection;

    public function getPortsInBounds(float $south, float $west, float $north, float $east): Collection;

    public function getPortTypes(): Collection;
}
