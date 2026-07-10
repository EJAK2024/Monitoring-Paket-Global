<?php

namespace App\Console\Commands;

use App\Services\AisStreamService;
use Illuminate\Console\Command;

class AisStreamRefresh extends Command
{
    protected $signature = 'aisstream:refresh';

    protected $description = 'Fetch live vessel positions from AISStream.io and cache them';

    public function handle(AisStreamService $service): int
    {
        $this->info('Connecting to AISStream.io...');

        $count = $service->refreshPositions();

        if ($count === 0 && ! $service->isKeyValid()) {
            $this->warn('AISStream API key is not valid. Check AISSTREAM_API_KEY in .env');

            return Command::FAILURE;
        }

        $this->info("Fetched {$count} live vessel positions and cached");

        return Command::SUCCESS;
    }
}
