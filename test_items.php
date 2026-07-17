<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$b = \App\Models\MarketplaceBooking::where('booking_sn', '260717AASCMHOZXR5GM')->first();
if ($b) {
    echo "Raw items: " . $b->getRawOriginal('items') . "\n";
    echo "Decoded type: " . gettype($b->items) . "\n";
    print_r($b->items);
} else {
    echo "Not found";
}
