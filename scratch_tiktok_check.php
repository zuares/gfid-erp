<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))->first();
$service = app(App\Services\Channels\ChannelManager::class);
$driver = $service->driver($store);

$res = $driver->getOrders($store, now()->subDays(15)->timestamp, now()->timestamp, 50, '');
print_r($res);
