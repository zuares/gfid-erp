<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$req = \Illuminate\Http\Request::create('/api/marketplace/local-orders', 'GET');
$res = app(\App\Http\Controllers\MarketplaceController::class)->localOrders($req);
file_put_contents('out.json', json_encode($res->getData(), JSON_PRETTY_PRINT));
