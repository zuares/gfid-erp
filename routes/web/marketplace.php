<?php

use App\Http\Controllers\Marketplace\MarketplaceOrderController;
use App\Http\Controllers\Marketplace\Reports\PayoutDashboardController;
use App\Http\Controllers\Marketplace\ShopeeImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        Route::resource('orders', MarketplaceOrderController::class)
            ->only(['index', 'show', 'create', 'store']);
    });

Route::middleware(['web', 'auth'])->group(function () {

    Route::prefix('marketplace')
        ->name('marketplace.')
        ->group(function () {

            Route::prefix('shopee')
                ->name('shopee.')
                ->group(function () {

                    // ===============================
                    // IMPORT ORDERS
                    // ===============================
                    Route::get('import-orders', [ShopeeImportController::class, 'index'])->name('import_orders.index');

                    Route::get('import-orders/form', [ShopeeImportController::class, 'ordersForm'])->name('import_orders.form');
                    Route::post('import-orders/preview', [ShopeeImportController::class, 'importOrdersPreview'])->name('import_orders.preview');
                    Route::post('import-orders/confirm', [ShopeeImportController::class, 'importOrdersConfirm'])->name('import_orders.confirm');

                    // optional reset
                    Route::post('import-orders/reset', [ShopeeImportController::class, 'resetOrdersPreview'])->name('import_orders.reset');

                    // ===============================
                    // IMPORT INCOME
                    // ===============================
                    Route::get('import-income', [ShopeeImportController::class, 'incomeForm'])
                        ->name('import-income.form');

                    Route::post('import-income', [ShopeeImportController::class, 'importIncome'])
                        ->name('import-income.run');
                });
        });
});

Route::middleware(['web', 'auth'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('payout', [PayoutDashboardController::class, 'index'])->name('payout.index');
        });
    });
