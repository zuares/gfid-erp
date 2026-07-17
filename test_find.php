<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = \App\Models\MarketplaceOrder::latest('id')->limit(10)->get(['id', 'channel_order_id', 'booking_sn']);
foreach ($orders as $order) {
    echo $order->channel_order_id . " | " . $order->booking_sn . " | items: " . $order->items()->count() . "\n";
}
