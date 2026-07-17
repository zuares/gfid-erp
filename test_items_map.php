<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$skus = ['TTB-WHT-XXL'];
$items = \App\Models\Item::whereIn('code', $skus)->with('category:id,code,name')->get()->keyBy('code');

echo json_encode($items->toArray(), JSON_PRETTY_PRINT);
