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

$res2 = $method->invokeArgs($channel, [$store, '/api/v2/order/get_booking_list', [
    'time_range_field' => 'update_time',
    'time_from' => strtotime('-15 days'),
    'time_to' => time(),
    'page_size' => 100,
]]);
echo json_encode($res2, JSON_PRETTY_PRINT);
