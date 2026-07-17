<?php

namespace Database\Seeders;

use App\Models\Container;
use App\Models\ContainerTrackingEvent;
use App\Models\Vessel;
use Illuminate\Database\Seeder;

class ContainerSeeder extends Seeder
{
    public function run(): void
    {
        $containers = [
            [
                'container_id' => 'MSCU1234567',
                'size' => '40ft',
                'type' => 'dry',
                'status' => 'in_transit',
                'current_location' => 'South China Sea',
                'origin' => 'Shenzhen, China',
                'destination' => 'Rotterdam, Netherlands',
                'shipper' => 'Shenzhen Electronics Co., Ltd.',
                'consignee' => 'Euro Distributors BV',
                'weight_kg' => 18500.00,
                'seal_number' => 'ML-CN123456',
            ],
            [
                'container_id' => 'MAEU8765432',
                'size' => '20ft',
                'type' => 'reefer',
                'status' => 'at_port',
                'current_location' => 'Port Klang, Malaysia',
                'origin' => 'Bangkok, Thailand',
                'destination' => 'Los Angeles, USA',
                'shipper' => 'Bangkok Automotive Parts',
                'consignee' => 'US Auto Imports LLC',
                'weight_kg' => 12200.00,
                'seal_number' => 'ML-TH654321',
            ],
            [
                'container_id' => 'CMAU9988776',
                'size' => '40ft',
                'type' => 'dry',
                'status' => 'customs',
                'current_location' => 'Tanjung Priok, Jakarta',
                'origin' => 'Jakarta, Indonesia',
                'destination' => 'Singapore',
                'shipper' => 'Jakarta Logistics Supply',
                'consignee' => 'SG Trade Solutions Pte Ltd',
                'weight_kg' => 21000.00,
                'seal_number' => 'ML-ID998877',
            ],
            [
                'container_id' => 'COSU5544332',
                'size' => '40HC',
                'type' => 'dry',
                'status' => 'delivered',
                'current_location' => 'Tokyo, Japan',
                'origin' => 'Singapore',
                'destination' => 'Tokyo, Japan',
                'shipper' => 'Singapore Precision Engineering',
                'consignee' => 'Tokyo Manufacturing Co.',
                'weight_kg' => 15800.00,
                'seal_number' => 'ML-SG554433',
            ],
            [
                'container_id' => 'EGHU1122334',
                'size' => '20ft',
                'type' => 'tank',
                'status' => 'in_transit',
                'current_location' => 'Strait of Malacca',
                'origin' => 'Kuala Lumpur, Malaysia',
                'destination' => 'Rotterdam, Netherlands',
                'shipper' => 'Kuala Lumpur Palm Oil Supply',
                'consignee' => 'EuroChem BV',
                'weight_kg' => 16200.00,
                'seal_number' => 'ML-MY112233',
            ],
            [
                'container_id' => 'HLCU6677889',
                'size' => '40ft',
                'type' => 'reefer',
                'status' => 'in_transit',
                'current_location' => 'Indian Ocean',
                'origin' => 'Ho Chi Minh, Vietnam',
                'destination' => 'Hamburg, Germany',
                'shipper' => 'Ho Chi Minh Garment Factory',
                'consignee' => 'Euro Fashion GmbH',
                'weight_kg' => 14200.00,
                'seal_number' => 'ML-VN667788',
            ],
            [
                'container_id' => 'ONEU4455667',
                'size' => '40HC',
                'type' => 'dry',
                'status' => 'at_port',
                'current_location' => 'Port of Shanghai',
                'origin' => 'Mumbai, India',
                'destination' => 'Shanghai, China',
                'shipper' => 'Mumbai Textile Exports',
                'consignee' => 'Shanghai Garment Importers',
                'weight_kg' => 19500.00,
                'seal_number' => 'ML-IN445566',
            ],
            [
                'container_id' => 'ZIMU9988001',
                'size' => '20ft',
                'type' => 'dry',
                'status' => 'delayed',
                'current_location' => 'Port of Colombo',
                'origin' => 'Singapore',
                'destination' => 'Mombasa, Kenya',
                'shipper' => 'Singapore Precision Engineering',
                'consignee' => 'East Africa Logistics Ltd',
                'weight_kg' => 8800.00,
                'seal_number' => 'ML-SG998800',
            ],
        ];

        foreach ($containers as $data) {
            $vessel = Vessel::inRandomOrder()->first();

            Container::create(array_merge($data, [
                'vessel_id' => $vessel?->id,
                'last_scanned_at' => now()->subHours(rand(1, 48)),
                'estimated_arrival' => now()->addDays(rand(5, 20)),
            ]));
        }

        $events = [
            ['container_id' => 'MSCU1234567', 'events' => [
                ['event_type' => 'loaded', 'location' => 'Port of Shenzhen', 'occurred_at' => now()->subDays(10)],
                ['event_type' => 'departed', 'location' => 'Port of Shenzhen', 'occurred_at' => now()->subDays(9)],
                ['event_type' => 'arrived', 'location' => 'Port of Singapore', 'occurred_at' => now()->subDays(5)],
                ['event_type' => 'departed', 'location' => 'Port of Singapore', 'occurred_at' => now()->subDays(4)],
            ]],
            ['container_id' => 'MAEU8765432', 'events' => [
                ['event_type' => 'loaded', 'location' => 'Port of Bangkok', 'occurred_at' => now()->subDays(5)],
                ['event_type' => 'departed', 'location' => 'Port of Bangkok', 'occurred_at' => now()->subDays(4)],
                ['event_type' => 'arrived', 'location' => 'Port Klang', 'occurred_at' => now()->subDays(1)],
                ['event_type' => 'discharged', 'location' => 'Port Klang', 'occurred_at' => now()->subHours(12)],
            ]],
            ['container_id' => 'CMAU9988776', 'events' => [
                ['event_type' => 'loaded', 'location' => 'Tanjung Priok', 'occurred_at' => now()->subDays(3)],
                ['event_type' => 'customs_cleared', 'location' => 'Tanjung Priok', 'occurred_at' => now()->subHours(6)],
            ]],
            ['container_id' => 'COSU5544332', 'events' => [
                ['event_type' => 'loaded', 'location' => 'Port of Singapore', 'occurred_at' => now()->subDays(14)],
                ['event_type' => 'departed', 'location' => 'Port of Singapore', 'occurred_at' => now()->subDays(13)],
                ['event_type' => 'arrived', 'location' => 'Port of Tokyo', 'occurred_at' => now()->subDays(3)],
                ['event_type' => 'discharged', 'location' => 'Port of Tokyo', 'occurred_at' => now()->subDays(2)],
                ['event_type' => 'gate_out', 'location' => 'Port of Tokyo', 'occurred_at' => now()->subDays(1)],
                ['event_type' => 'delivered', 'location' => 'Tokyo, Japan', 'occurred_at' => now()->subHours(8)],
            ]],
            ['container_id' => 'EGHU1122334', 'events' => [
                ['event_type' => 'loaded', 'location' => 'Port Klang', 'occurred_at' => now()->subDays(7)],
                ['event_type' => 'departed', 'location' => 'Port Klang', 'occurred_at' => now()->subDays(6)],
            ]],
            ['container_id' => 'HLCU6677889', 'events' => [
                ['event_type' => 'loaded', 'location' => 'Port of Ho Chi Minh', 'occurred_at' => now()->subDays(8)],
                ['event_type' => 'departed', 'location' => 'Port of Ho Chi Minh', 'occurred_at' => now()->subDays(7)],
                ['event_type' => 'arrived', 'location' => 'Port of Singapore', 'occurred_at' => now()->subDays(4)],
                ['event_type' => 'departed', 'location' => 'Port of Singapore', 'occurred_at' => now()->subDays(3)],
            ]],
            ['container_id' => 'ONEU4455667', 'events' => [
                ['event_type' => 'loaded', 'location' => 'Port of Mumbai', 'occurred_at' => now()->subDays(6)],
                ['event_type' => 'departed', 'location' => 'Port of Mumbai', 'occurred_at' => now()->subDays(5)],
                ['event_type' => 'arrived', 'location' => 'Port of Shanghai', 'occurred_at' => now()->subHours(4)],
            ]],
            ['container_id' => 'ZIMU9988001', 'events' => [
                ['event_type' => 'loaded', 'location' => 'Port of Singapore', 'occurred_at' => now()->subDays(12)],
                ['event_type' => 'departed', 'location' => 'Port of Singapore', 'occurred_at' => now()->subDays(11)],
                ['event_type' => 'delayed', 'location' => 'Port of Colombo', 'remarks' => 'Weather delay - heavy monsoon', 'occurred_at' => now()->subDays(2)],
            ]],
        ];

        foreach ($events as $group) {
            $container = Container::where('container_id', $group['container_id'])->first();
            if (! $container) {
                continue;
            }

            foreach ($group['events'] as $ev) {
                $vessel = Vessel::inRandomOrder()->first();
                ContainerTrackingEvent::create([
                    'container_id' => $container->id,
                    'event_type' => $ev['event_type'],
                    'location' => $ev['location'],
                    'vessel_id' => $vessel?->id,
                    'occurred_at' => $ev['occurred_at'],
                    'remarks' => $ev['remarks'] ?? null,
                ]);
            }
        }
    }
}
