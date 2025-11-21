<?php

use App\Http\Controllers\Inventory\StockCardController;
use App\Http\Controllers\Inventory\TransferController;

Route::middleware(['web', 'auth'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('stock-card', [StockCardController::class, 'index'])->name('stock_card.index');

    // 🔥 Transfer stok antar gudang
    Route::resource('transfers', TransferController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->names('transfers');
});
