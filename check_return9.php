<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ret = \App\Models\PurchaseReturn::with('lines')->find(9);
if (!$ret) {
    echo "Return ID 9 not found\n";
    exit;
}

echo "Code: {$ret->code}\n";
echo "Status: {$ret->status}\n";
echo "Voided: " . ($ret->voided_at ? "Yes" : "No") . "\n";
echo "Resolution Type: {$ret->resolution_type}\n";
echo "Replacement Status: {$ret->replacement_status}\n";
echo "\nLines:\n";
foreach ($ret->lines as $line) {
    echo "- ID: {$line->id}, Qty: {$line->qty}, Reason: {$line->reason_code}, Replacement Expected: {$line->replacement_qty_expected}, Replacement Received: {$line->replacement_qty_received}\n";
}
