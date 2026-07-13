<?php

namespace Database\Seeders;

use App\Models\Port;
use Illuminate\Database\Seeder;

class PortSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/world_ports.json');

        if (! file_exists($jsonPath)) {
            $this->command->error('world_ports.json not found at '.$jsonPath);

            return;
        }

        $ports = json_decode(file_get_contents($jsonPath), true);

        if (empty($ports)) {
            $this->command->error('world_ports.json is empty or invalid');

            return;
        }

        $existing = Port::pluck('name')->toArray();
        $inserted = 0;

        foreach ($ports as $port) {
            if (in_array($port['name'], $existing)) {
                continue;
            }
            Port::create($port);
            $existing[] = $port['name'];
            $inserted++;
        }

        $this->command->info("Imported {$inserted} new ports (skipped ".(count($ports) - $inserted).' duplicates)');
    }
}
