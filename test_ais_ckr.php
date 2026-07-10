<?php

use App\Services\AisStreamService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$svc = new AisStreamService;
$ref = new ReflectionMethod($svc, 'checkKeyRemote');
$ref->setAccessible(true);
$result = $ref->invoke($svc);
echo 'checkKeyRemote result: '.($result ? 'true' : 'false').PHP_EOL;
