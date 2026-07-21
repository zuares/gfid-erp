<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$item = App\Models\Item::where('type', 'finished_good')->first();
if (!$item) {
    echo "No finished goods found.\n";
    exit;
}

echo "Testing on item: {$item->code} (ID: {$item->id})\n";

$request = Illuminate\Http\Request::create('/inventory/warehouse-intelligence/limits', 'POST', [
    'item_id' => $item->id,
    'rts_min_display' => 10,
    'rts_max_display' => 50,
]);
// Fake validation bypass or run controller
$controller = new App\Http\Controllers\Inventory\WarehouseIntelligenceController(
    app(App\Services\Inventory\InventoryIntelligenceService::class)
);

try {
    $response = $controller->updateLimits($request);
    echo "Response: " . $response->getContent() . "\n";
    
    $item->refresh();
    echo "Updated min: {$item->rts_min_display}, max: {$item->rts_max_display}\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
