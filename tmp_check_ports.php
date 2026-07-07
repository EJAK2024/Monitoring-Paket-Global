<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Port;

// Check port types
$types = Port::select('port_type')->whereNotNull('port_type')->distinct()->orderBy('port_type')->pluck('port_type');
echo "Port types: " . $types->implode(', ') . "\n";
echo "Total ports: " . Port::count() . "\n\n";

// Check major port names
$names = ['Port of Shanghai','Port of Singapore','Rotterdam','Port of Los Angeles','Busan','Tokyo','Port of Hamburg','Antwerp','Port of Tanjung Priok','Port of Sydney','Santos','Buenos Aires','Cape Town','Mumbai','Port of New York','Dubai','Colombo','Piraeus','Long Beach','Norfolk','Miami','Southampton','Port Hedland','Surabaya','Port of Jakarta','Port of Belawan','Port of Melbourne','Port of Rotterdam','Port of Tokyo','Port of Busan','Port of Antwerp'];

// Also search for any port containing these keywords
$keywords = ['Singapore', 'Shanghai', 'Los Angeles', 'New York', 'Rotterdam', 'Jakarta', 'Sydney', 'Santos', 'Mumbai', 'Dubai', 'Colombo', 'Cape Town', 'Buenos Aires'];
$ports = Port::whereIn('name', $names)->get();
echo count($ports) . " matching ports found:\n";
foreach ($ports as $p) {
    echo "  - {$p->name} ({$p->country}) [{$p->port_type}] @ {$p->latitude}, {$p->longitude}\n";
}

// Keyword search
echo "\nKeyword search:\n";
foreach ($keywords as $kw) {
    $results = Port::where('name', 'like', "%{$kw}%")->get();
    foreach ($results as $p) {
        echo "  - {$p->name} ({$p->country}) [{$p->port_type}] @ {$p->latitude}, {$p->longitude}\n";
    }
}

// List all port names
echo "\nAll port names:\n";
foreach (Port::orderBy('name')->pluck('name') as $n) {
    echo "  - {$n}\n";
}
