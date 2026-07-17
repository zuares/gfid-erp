<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = \App\Models\MarketplaceOrder::where('created_at', '>=', now()->subDays(1))
    ->get(['id', 'channel_order_id', 'booking_sn']);
foreach ($orders as $order) {
    if (strpos($order->channel_order_id, 'AASC') !== false || strpos($order->booking_sn, 'AASC') !== false) {
        echo $order->channel_order_id . " | " . $order->booking_sn . "\n";
    }
}
