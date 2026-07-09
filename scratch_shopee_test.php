<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::find(4);
$channel = app(App\Services\Channels\ChannelManager::class)->driver($store);
$reflection = new \ReflectionClass(get_class($channel));
$method = $reflection->getMethod('get');
$method->setAccessible(true);

$res = $method->invokeArgs($channel, [$store, '/api/v2/order/get_shipment_list', [
    'page_size' => 10,
]]);
echo json_encode($res, JSON_PRETTY_PRINT);
