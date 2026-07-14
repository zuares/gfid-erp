<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/purchasing/purchase-returns/9', 'PUT', [
    'date' => '2026-07-15',
    'resolution_type' => 'replacement',
    'lines' => [
        0 => [
            'purchase_receipt_line_id' => 15,
            'qty' => '1',
            'reason_code' => 'defect',
        ]
    ]
]);
$controller = app(\App\Http\Controllers\Purchasing\PurchaseReturnController::class);

try {
    $purchase_return = \App\Models\PurchaseReturn::find(9);
    $response = $controller->update($request, $purchase_return);
    echo "Response status: " . $response->getStatusCode() . "\n";
    if ($response->isRedirect()) {
        echo "Redirected to: " . $response->getTargetUrl() . "\n";
        $err = session('error');
        if ($err) echo "Error in session: $err\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
