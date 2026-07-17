<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = \App\Models\MarketplaceOrder::with('items')->where('channel_order_id', '260717AASCMH0')->first();
if ($order) {
    echo "Found Order: " . $order->id . " with " . $order->items->count() . " items.\n";
} else {
    echo "Order not found.\n";
}

$order = \App\Models\MarketplaceOrder::with('items')->where('booking_sn', '260717AASCMH0')->first();
if ($order) {
    echo "Found Order (via booking_sn): " . $order->id . " with " . $order->items->count() . " items.\n";
} else {
    echo "Order not found (via booking_sn).\n";
}
