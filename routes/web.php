<?php
use App\Http\Controllers\Production\ProductionActivityController;
use App\Http\Controllers\Production\ProductionIssueController;
use App\Http\Controllers\Production\ProductionOrderController;
use App\Http\Controllers\Production\ProductionReceiptController;

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
require __DIR__ . '/web/sales.php';
require __DIR__ . '/web/accounting.php';
require __DIR__ . '/web/shipments.php';
require __DIR__ . '/web/imports.php';

Route::prefix('production')->group(function () {
    Route::get('orders', [ProductionOrderController::class, 'index'])
        ->name('production.orders.index');

    Route::get('orders/{order}', [ProductionOrderController::class, 'show'])
        ->name('production.orders.show');

    Route::post('issues/{issue}/post', [ProductionIssueController::class, 'post'])
        ->name('production.issues.post');

    Route::post('receipts/{receipt}/post', [ProductionReceiptController::class, 'post'])
        ->name('production.receipts.post');

    Route::get('orders/{order}/issues/create', [ProductionIssueController::class, 'create'])
        ->name('production.issues.create');

    Route::post('orders/{order}/issues', [ProductionIssueController::class, 'store'])
        ->name('production.issues.store');

    Route::get('orders/{order}/receipts/create', [ProductionReceiptController::class, 'create'])
        ->name('production.receipts.create');

    Route::post('orders/{order}/receipts', [ProductionReceiptController::class, 'store'])
        ->name('production.receipts.store');

    Route::post('orders/{order}/activities', [ProductionActivityController::class, 'store'])
        ->name('production.activities.store');

    Route::delete('activities/{activity}', [ProductionActivityController::class, 'destroy'])
        ->name('production.activities.destroy');

});
