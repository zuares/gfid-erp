<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(\App\Services\MarketplaceIssueService::class);
$rows = $service->bookingIssueRows();
echo "Found " . count($rows) . " booking issues.\n";
if (count($rows) > 0) {
    echo json_encode(array_slice($rows, 0, 2), JSON_PRETTY_PRINT);
}
