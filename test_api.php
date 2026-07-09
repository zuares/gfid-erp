<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture()); // Boot

$request = Illuminate\Http\Request::create(
    '/marketplace/settings/preview-pdf', 'POST',
    ['marketplace_footer_alignment' => 'C']
);
$controller = app(\App\Http\Controllers\MarketplaceController::class);
$response = $controller->previewSettingsPdf($request);
file_put_contents('test.pdf', $response->getContent());
echo "Saved test.pdf, size: " . filesize('test.pdf') . "\n";
