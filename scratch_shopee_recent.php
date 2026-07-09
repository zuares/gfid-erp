<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::whereHas('channel', fn($q) => $q->where('code', 'shopee'))->where('name', 'Insight Corps')->first();
$service = app(App\Services\Channels\ChannelManager::class);
$driver = $service->driver($store);

$timeTo = now()->timestamp;
$timeFrom = now()->subDays(2)->timestamp;
$cursor = '';
$hasMore = true;
$allSns = [];

while ($hasMore) {
    $res = $driver->getOrders($store, $timeFrom, $timeTo, 50, $cursor);
    $orders = data_get($res, 'response.order_list', []);
    foreach ($orders as $o) {
        $allSns[] = $o['order_sn'];
    }
    $nextCursor = data_get($res, 'response.next_cursor');
    if (data_get($res, 'response.more') && $nextCursor) {
        $cursor = $nextCursor;
    } else {
        $hasMore = false;
    }
}

echo "Total orders in last 2 days: " . count($allSns) . "\n";

foreach (array_chunk($allSns, 50) as $chunk) {
    $detailRes = $driver->getOrderDetail($store, $chunk);
    $details = data_get($detailRes, 'response.order_list', []);
    foreach ($details as $d) {
        echo str_pad($d['order_sn'], 16) . " | " . 
             str_pad($d['order_status'], 18) . " | " . 
             date('Y-m-d H:i:s', $d['create_time']) . " | " . 
             ($d['shipping_carrier'] ?? 'Unknown') . "\n";
    }
}
