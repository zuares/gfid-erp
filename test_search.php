<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/purchasing/purchase-returns/search-by-item', 'GET', ['q' => 'S3RDM']);
$controller = app(\App\Http\Controllers\Purchasing\PurchaseReturnController::class);

try {
    $response = $controller->searchByItem($request);
    echo $response->getContent();
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
