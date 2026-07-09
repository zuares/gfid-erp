<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::whereHas('channel', fn($q) => $q->where('code', 'shopee'))->where('name', 'Insight Corps')->first();
$service = app(App\Services\Channels\ChannelManager::class);
$driver = $service->driver($store);

$sn = '260708AASDTF6V'; 
$detailRes = $driver->getOrderDetail($store, [$sn]);

print_r($detailRes);
