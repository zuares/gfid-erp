<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = App\Models\MarketplaceOrder::find(1531);
$store = $order->store;

$channel = app(App\Services\Channels\Shopee\ShopeeChannel::class);
$reflection = new ReflectionClass($channel);
$method = $reflection->getMethod('get');
$method->setAccessible(true);
$result = $method->invoke($channel, $store, '/api/v2/payment/get_escrow_detail', ['order_sn' => $order->channel_order_id]);
echo json_encode($result);
