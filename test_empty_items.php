<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = \App\Models\MarketplaceOrder::doesntHave('items')
    ->limit(10)
    ->get(['id', 'channel_order_id', 'booking_sn']);
echo json_encode($orders->toArray(), JSON_PRETTY_PRINT);
