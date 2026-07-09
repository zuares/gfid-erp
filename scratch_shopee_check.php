<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::whereHas('channel', fn($q) => $q->where('code', 'shopee'))->first();
$service = app(App\Services\Channels\ChannelManager::class);
$driver = $service->driver($store);

$params = [
    'time_range_field' => 'create_time',
    'time_from' => now()->subDays(15)->timestamp,
    'time_to' => now()->timestamp,
    'page_size' => 50,
    'order_status' => 'READY_TO_SHIP',
];

$method = new ReflectionMethod(get_class($driver), 'get');
$method->setAccessible(true);
$res = $method->invoke($driver, $store, '/api/v2/order/get_order_list', $params);

$ordersList = data_get($res, 'response.order_list', []);
echo "Total READY_TO_SHIP with order_status param: " . count($ordersList) . "\n";
