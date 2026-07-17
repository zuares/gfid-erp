<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$b = \App\Models\MarketplaceBooking::where('booking_sn', '260717AASCMHOZXR5GM')->first();
if ($b) {
    echo "order_sn: " . $b->order_sn . "\n";
    $order = \App\Models\MarketplaceOrder::where('channel_order_id', $b->order_sn)->with('items')->first();
    if ($order) {
        echo "Found order: " . $order->id . " with " . $order->items->count() . " items.\n";
    }
} else {
    echo "Booking not found\n";
}
