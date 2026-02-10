<?php

use App\Http\Controllers\Api\MarketplaceShipmentController;
use App\Http\Controllers\Imports\MarketplaceImportController;
use App\Http\Controllers\Imports\MarketplaceIncomeImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->prefix('imports')
    ->name('imports.')
    ->group(function () {

        // =========================
        // Marketplace Shipments (Import Wizard)
        // =========================
        Route::prefix('marketplace')->name('marketplace.')->group(function () {

            // API JSON (datatable) — keep di atas /{...}
            Route::get('data', [MarketplaceShipmentController::class, 'index'])->name('data');

            // pages
            Route::get('/', [MarketplaceImportController::class, 'index'])->name('index');
            Route::get('create', [MarketplaceImportController::class, 'create'])->name('create');
            Route::get('export', [MarketplaceImportController::class, 'export'])->name('export');

            // draft/session helpers
            Route::get('draft', [MarketplaceImportController::class, 'draft'])->name('draft');

            // actions
            Route::post('preview', [MarketplaceImportController::class, 'preview'])->name('preview');
            Route::post('commit', [MarketplaceImportController::class, 'commit'])->name('commit');
            Route::post('cancel', [MarketplaceImportController::class, 'cancel'])->name('cancel');

            // detail import/draft (opsional)
            Route::get('{import}', [MarketplaceImportController::class, 'show'])
                ->whereNumber('import')
                ->name('show');
        });

        // =========================
        // Marketplace Income (Import Wizard)
        // =========================
        Route::prefix('marketplace-income')->name('marketplace_income.')->group(function () {
            Route::get('/', [MarketplaceIncomeImportController::class, 'index'])->name('index');
            Route::get('/create', [MarketplaceIncomeImportController::class, 'create'])->name('create');
            Route::post('/preview', [MarketplaceIncomeImportController::class, 'preview'])->name('preview');
            Route::post('/commit', [MarketplaceIncomeImportController::class, 'commit'])->name('commit');
            Route::post('/cancel', [MarketplaceIncomeImportController::class, 'cancel'])->name('cancel');
            Route::get('/draft', [MarketplaceIncomeImportController::class, 'draft'])->name('draft');

            // ✅ show batch & show order(items)
            Route::get('/batches/{batch}', [MarketplaceIncomeImportController::class, 'showBatch'])->name('show');
            Route::get('/orders/{income}', [MarketplaceIncomeImportController::class, 'showOrder'])->name('order.show');

            Route::post('/batches/{batch}/apply', [MarketplaceIncomeImportController::class, 'apply'])->name('apply');
        });

    });
