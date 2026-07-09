<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::whereHas('channel', fn($q) => $q->where('code', 'shopee'))->where('name', 'Insight Corps')->first();
$service = app(App\Services\Channels\ChannelManager::class);
$driver = $service->driver($store);

$params = [
    'time_range_field' => 'create_time',
    'time_from' => now()->subDays(15)->timestamp,
    'time_to' => now()->timestamp,
    'page_size' => 50,
];

$method = new ReflectionMethod(get_class($driver), 'get');
$method->setAccessible(true);
$res = $method->invoke($driver, $store, '/api/v2/order/get_order_list', $params);

$ordersList = data_get($res, 'response.order_list', []);
$sns = array_column($ordersList, 'order_sn');

$unpaidCount = 0;
$inCancelCount = 0;

if (!empty($sns)) {
    $detailRes = $driver->getOrderDetail($store, $sns);
    $details = data_get($detailRes, 'response.order_list', []);
    foreach ($details as $d) {
        if ($d['order_status'] === 'UNPAID') {
            $unpaidCount++;
            echo "UNPAID Order: {$d['order_sn']} - COD: " . ($d['cod'] ? 'Yes' : 'No') . "\n";
        }
        if ($d['order_status'] === 'IN_CANCEL') {
            $inCancelCount++;
            echo "IN_CANCEL Order: {$d['order_sn']}\n";
        }
    }
}
echo "Total UNPAID: {$unpaidCount}\n";
echo "Total IN_CANCEL: {$inCancelCount}\n";
