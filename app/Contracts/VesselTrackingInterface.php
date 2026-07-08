<?php

namespace App\Contracts;

interface VesselTrackingInterface
{
    public function searchVessels(string $keyword, int $limit = 10): array;

    public function getVesselPosition(string $mmsi): ?array;

    public function getMultiplePositions(array $mmsiList): array;

    public function isKeyValid(): bool;
}
