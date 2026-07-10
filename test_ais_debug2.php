<?php

use Illuminate\Contracts\Console\Kernel;
use WebSocket\Client;
use WebSocket\TimeoutException;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$key = config('services.aisstream.key');
echo 'Key: '.substr($key, 0, 10).'...'.PHP_EOL;

try {
    $client = new Client('wss://stream.aisstream.io/v0/stream', ['timeout' => 5]);
    echo 'Connected!'.PHP_EOL;
    $client->text(json_encode([
        'APIKey' => $key,
        'BoundingBoxes' => [[[-90, -180], [90, 180]]],
        'FilterMessageTypes' => ['PositionReport'],
    ]));
    echo 'Subscription sent!'.PHP_EOL;

    $start = microtime(true);
    while (microtime(true) - $start < 5) {
        try {
            $response = $client->receive();
            $data = json_decode($response, true);
            if (isset($data['error'])) {
                echo 'ERROR: '.$data['error'].PHP_EOL;
                break;
            }
            if (($data['MessageType'] ?? '') === 'PositionReport') {
                echo 'Got PositionReport!'.PHP_EOL;
                echo json_encode($data, JSON_PRETTY_PRINT).PHP_EOL;
                break;
            }
            echo 'Other message: '.($data['MessageType'] ?? 'unknown').PHP_EOL;
        } catch (TimeoutException $e) {
            echo 'Timeout after '.(microtime(true) - $start).'s'.PHP_EOL;
            break;
        }
    }
    $client->close();
} catch (Exception $e) {
    echo 'Exception: '.$e->getMessage().PHP_EOL;
}
