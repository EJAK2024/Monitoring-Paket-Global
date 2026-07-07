<?php

namespace App\Console\Commands;

use App\Models\Port;
use Illuminate\Console\Command;

class ImportWorldPortIndex extends Command
{
    protected $signature = 'wpi:import {--file= : Path to JSON file}';

    protected $description = 'Import World Port Index JSON dataset into the ports table';

    public function handle(): int
    {
        $path = $this->option('file') ?? database_path('data/world_ports.json');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $json = file_get_contents($path);
        $ports = json_decode($json, true);

        if (empty($ports)) {
            $this->error('Invalid or empty JSON file.');

            return self::FAILURE;
        }

        $this->info('Importing '.count($ports).' ports from World Port Index...');
        $bar = $this->output->createProgressBar(count($ports));
        $bar->start();

        $imported = 0;
        $skipped = 0;

        foreach ($ports as $data) {
            if (empty($data['name']) || empty($data['latitude']) || empty($data['longitude'])) {
                $skipped++;

                $bar->advance();

                continue;
            }

            $existing = Port::where('name', $data['name'])
                ->where('country', $data['country'] ?? '')
                ->first();

            if ($existing) {
                $existing->update([
                    'country_code' => $data['country_code'] ?? $existing->country_code,
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'port_type' => $data['port_type'] ?? $existing->port_type,
                ]);
            } else {
                Port::create([
                    'name' => $data['name'],
                    'country' => $data['country'] ?? 'Unknown',
                    'country_code' => $data['country_code'] ?? null,
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'port_type' => $data['port_type'] ?? null,
                ]);
            }

            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! {$imported} ports imported, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
