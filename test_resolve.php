<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sku = 'TTB-WHT-XXL'; // example sku
$variant = \App\Models\MarketplaceItemVariant::where('sku', $sku)->first();
if ($variant && $variant->internal_item_id) {
    echo "Found via MarketplaceItemVariant: " . $variant->internal_item_id . "\n";
} else {
    $item = \App\Models\Item::where('code', $sku)->first();
    if ($item) {
        echo "Found via Item: " . $item->id . "\n";
    } else {
        echo "Not found\n";
    }
}
