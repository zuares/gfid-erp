<?php

use Illuminate\Support\Facades\Route;

// Redirect root ke dashboard (optional)
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Grouping per domain
require __DIR__ . '/web/auth.php';
require __DIR__ . '/web/dashboard.php';
require __DIR__ . '/web/purchasing.php';
require __DIR__ . '/web/inventory.php';
require __DIR__ . '/web/production.php';
require __DIR__ . '/web/payroll.php';
require __DIR__ . '/web/costing.php';
require __DIR__ . '/web/marketplace.php';
require __DIR__ . '/web/master.php';
require __DIR__ . '/web/shipment.php';
require __DIR__ . '/web/sales.php';
