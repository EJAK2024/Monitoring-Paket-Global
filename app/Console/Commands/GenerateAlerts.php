<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Illuminate\Console\Command;

class GenerateAlerts extends Command
{
    protected $signature = 'alerts:generate';

    protected $description = 'Check risk thresholds and generate alerts';

    public function handle(AlertService $alertService): int
    {
        $this->info('Checking risk thresholds...');

        $count = $alertService->generateAll();

        $this->info("Generated {$count} new alert(s).");

        return Command::SUCCESS;
    }
}
