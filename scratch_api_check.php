<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = App\Models\MarketplaceOrder::all();
echo "Total orders: " . $orders->count() . "\n";
echo "READY_TO_SHIP: " . $orders->where('order_status', 'READY_TO_SHIP')->count() . "\n";
echo "UNPAID: " . $orders->where('order_status', 'UNPAID')->count() . "\n";

foreach($orders->where('order_status', 'UNPAID') as $o) {
    echo "ID: {$o->id}, status: {$o->status}, order_status: {$o->order_status}, total_amount: {$o->total_amount}, total_paid_customer: {$o->total_paid_customer}\n";
}
