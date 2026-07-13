<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$store = \App\Models\Store::whereHas('channel', function($q) { $q->where('code', 'shopee'); })->first();
$service = app(\App\Services\Channels\ChannelManager::class);
$driver = $service->driver($store);

$method = new \ReflectionMethod($driver, 'post');
$method->setAccessible(true);
$res = $method->invoke($driver, $store, '/api/v2/logistics/get_shipping_document_parameter', [
    'order_list' => [['order_sn' => 'dummy']]
]);
print_r($res);
