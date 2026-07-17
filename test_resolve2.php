<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sku = 'TTB-WHT-XXL';
$item = \App\Models\Item::where('code', $sku)->select('id','code','name')->first();
if ($item) {
    echo "Found via Item: " . $item->id . "\n";
} else {
    echo "Not found in Items table\n";
}
