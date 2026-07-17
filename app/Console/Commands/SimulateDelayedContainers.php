<?php

namespace App\Console\Commands;

use App\Models\Container;
use App\Models\ContainerTrackingEvent;
use App\Models\Vessel;
use Illuminate\Console\Command;

class SimulateDelayedContainers extends Command
{
    protected $signature = 'simulate:delayed-containers';

    protected $description = 'Create 5 simulated delayed containers for testing alerts';

    public function handle(): int
    {
        $delayed = [
            [
                'container_id' => 'SIMU1000001',
                'size' => '40ft',
                'type' => 'dry',
                'current_location' => 'Port of Tanjung Priok',
                'origin' => 'Jakarta, Indonesia',
                'destination' => 'Sydney, Australia',
                'shipper' => 'Jakarta Logistics Supply',
                'consignee' => 'Aus Trade Imports Pty Ltd',
                'weight_kg' => 19500.00,
                'seal_number' => 'ML-SIM001',
                'remarks' => 'Customs documentation incomplete',
            ],
            [
                'container_id' => 'SIMU1000002',
                'size' => '20ft',
                'type' => 'reefer',
                'current_location' => 'Port of Colombo',
                'origin' => 'Mumbai, India',
                'destination' => 'Rotterdam, Netherlands',
                'shipper' => 'Mumbai Textile Exports',
                'consignee' => 'Euro Fashion GmbH',
                'weight_kg' => 12800.00,
                'seal_number' => 'ML-SIM002',
                'remarks' => 'Vessel engine malfunction - schedule disrupted',
            ],
            [
                'container_id' => 'SIMU1000003',
                'size' => '40HC',
                'type' => 'dry',
                'current_location' => 'Port of Shanghai',
                'origin' => 'Shanghai, China',
                'destination' => 'Hamburg, Germany',
                'shipper' => 'Shanghai Electronics Co.',
                'consignee' => 'Euro Distributors BV',
                'weight_kg' => 22000.00,
                'seal_number' => 'ML-SIM003',
                'remarks' => 'Port congestion - berth unavailable',
            ],
            [
                'container_id' => 'SIMU1000004',
                'size' => '20ft',
                'type' => 'tank',
                'current_location' => 'Strait of Malacca',
                'origin' => 'Kuala Lumpur, Malaysia',
                'destination' => 'Los Angeles, USA',
                'shipper' => 'Kuala Lumpur Palm Oil Supply',
                'consignee' => 'US Chemicals Importers',
                'weight_kg' => 16500.00,
                'seal_number' => 'ML-SIM004',
                'remarks' => 'Heavy storm warning - vessel holding position',
            ],
            [
                'container_id' => 'SIMU1000005',
                'size' => '40ft',
                'type' => 'dry',
                'current_location' => 'Port of Ho Chi Minh',
                'origin' => 'Ho Chi Minh, Vietnam',
                'destination' => 'Tokyo, Japan',
                'shipper' => 'Ho Chi Minh Garment Factory',
                'consignee' => 'Tokyo Manufacturing Co.',
                'weight_kg' => 14300.00,
                'seal_number' => 'ML-SIM005',
                'remarks' => 'Labor strike at port terminal',
            ],
        ];

        $count = 0;

        foreach ($delayed as $data) {
            if (Container::where('container_id', $data['container_id'])->exists()) {
                $this->warn("Container {$data['container_id']} already exists, skipping.");
                continue;
            }

            $remarks = $data['remarks'];
            unset($data['remarks']);

            $vessel = Vessel::inRandomOrder()->first();

            $container = Container::create(array_merge($data, [
                'status' => 'delayed',
                'vessel_id' => $vessel?->id,
                'last_scanned_at' => now()->subHours(rand(2, 24)),
                'estimated_arrival' => now()->addDays(rand(5, 15)),
            ]));

            ContainerTrackingEvent::create([
                'container_id' => $container->id,
                'event_type' => 'loaded',
                'location' => str_replace(['Port of ', 'Port of '], '', $data['origin']),
                'occurred_at' => now()->subDays(rand(7, 14)),
            ]);

            ContainerTrackingEvent::create([
                'container_id' => $container->id,
                'event_type' => 'departed',
                'location' => $data['origin'],
                'occurred_at' => now()->subDays(rand(5, 10)),
            ]);

            ContainerTrackingEvent::create([
                'container_id' => $container->id,
                'event_type' => 'delayed',
                'location' => $data['current_location'],
                'remarks' => $remarks,
                'occurred_at' => now()->subDays(rand(1, 3)),
            ]);

            $this->info("Created delayed container: {$data['container_id']}");
            $count++;
        }

        $this->info("Done! {$count} delayed container(s) created.");

        return Command::SUCCESS;
    }
}
