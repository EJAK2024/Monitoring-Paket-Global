<?php

use App\Services\AisStreamService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$svc = new AisStreamService;
echo 'isKeyValid: '.($svc->isKeyValid() ? 'YES' : 'NO').PHP_EOL;
