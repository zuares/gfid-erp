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
