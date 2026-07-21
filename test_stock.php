<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$s = app(App\Services\Inventory\InventoryIntelligenceService::class);
$rows = $s->rows([]);

echo "Total items: " . $rows->count() . "\n";
foreach($rows->take(10) as $r) {
    echo "ID: {$r->item_id}, SKU: {$r->sku}, Ready: {$r->ready}, PRD: {$r->wh_prd}\n";
}
