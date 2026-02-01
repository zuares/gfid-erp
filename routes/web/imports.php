<?php

use App\Http\Controllers\Api\MarketplaceShipmentController;
use App\Http\Controllers\Imports\MarketplaceImportController;
use App\Http\Controllers\Imports\MarketplaceIncomeImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->prefix('imports')->name('imports.')->group(function () {

    // =========================
    // Marketplace Shipments
    // =========================
    Route::prefix('marketplace')->name('marketplace.')->group(function () {

        // ✅ API JSON (HARUS di atas /{shipment})
        Route::get('/data', [MarketplaceShipmentController::class, 'index'])
            ->name('data');

        // pages + actions
        Route::get('/', [MarketplaceImportController::class, 'index'])->name('index');
        Route::get('/export', [MarketplaceImportController::class, 'export'])->name('export');
        Route::get('/create', [MarketplaceImportController::class, 'create'])->name('create');

        Route::get('/draft', [MarketplaceImportController::class, 'draft'])->name('draft');

        Route::post('/preview', [MarketplaceImportController::class, 'preview'])->name('preview');
        Route::post('/commit', [MarketplaceImportController::class, 'commit'])->name('commit');
        Route::post('/cancel', [MarketplaceImportController::class, 'cancel'])->name('cancel');

        // ✅ taruh paling bawah + batasi hanya angka biar gak nyangkut kata "data"
        Route::get('/{shipment}', [MarketplaceImportController::class, 'show'])
            ->whereNumber('shipment')
            ->name('show');

        // ✅ NEW: resume draft -> buka preview dari session
    });

    // =========================
    // Marketplace Income
    // =========================
    Route::prefix('marketplace-income')->name('marketplace_income.')->group(function () {
        Route::get('/', [MarketplaceIncomeImportController::class, 'create'])->name('create');
        Route::post('/preview', [MarketplaceIncomeImportController::class, 'preview'])->name('preview');
        Route::post('/commit', [MarketplaceIncomeImportController::class, 'commit'])->name('commit');
        Route::post('/cancel', [MarketplaceIncomeImportController::class, 'cancel'])->name('cancel');
    });

});
