<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$kilats = \App\Models\MarketplaceBooking::latest('id')->limit(10)->get(['id', 'booking_sn', 'order_sn', 'items']);
foreach ($kilats as $k) {
    echo $k->booking_sn . " | items count: " . (is_array($k->items) ? count($k->items) : 0) . "\n";
}
