<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = \App\Models\MarketplaceOrder::where('booking_sn', '!=', null)
    ->with('items')
    ->limit(1)
    ->get();
echo json_encode($orders->toArray(), JSON_PRETTY_PRINT);
