<?php

namespace Database\Seeders;

use App\Models\Port;
use Illuminate\Database\Seeder;

class PortSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/world_ports.json');

        if (!file_exists($jsonPath)) {
            $this->command->error('world_ports.json not found at ' . $jsonPath);
            return;
        }

        $ports = json_decode(file_get_contents($jsonPath), true);

        if (empty($ports)) {
            $this->command->error('world_ports.json is empty or invalid');
            return;
        }

        foreach ($ports as $port) {
            Port::create($port);
        }

        $this->command->info('Imported ' . count($ports) . ' ports from World Port Index');
    }
}
