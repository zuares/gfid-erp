<?php

use App\Http\Controllers\Api\StockApiController;
use App\Http\Controllers\Inventory\ExternalTransferController;
use App\Http\Controllers\Inventory\InventoryAdjustmentController;
use App\Http\Controllers\Inventory\InventoryStockController;
use App\Http\Controllers\Inventory\RtsStockRequestController;
use App\Http\Controllers\Inventory\RtsStockRequestProcessController;
use App\Http\Controllers\Inventory\StockCardController;
use App\Http\Controllers\Inventory\StockOpnameController;
use App\Http\Controllers\Inventory\TransferController;

// ======================================================================
// Semua route ERP yang butuh login
// ======================================================================
Route::middleware(['web', 'auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | INVENTORY NAMESPACE
    |--------------------------------------------------------------------------
     */
    Route::prefix('inventory')
        ->name('inventory.')
        ->group(function () {

            // ================== STOCK CARD ==================
            Route::get('stock-card', [StockCardController::class, 'index'])
                ->name('stock_card.index');

            Route::get('stock-card/export', [StockCardController::class, 'export'])
                ->name('stock_card.export');

            // ================== INTERNAL TRANSFERS ==================
            Route::resource('transfers', TransferController::class)
                ->only(['index', 'create', 'store', 'show'])
                ->names('transfers');

            // ================== EXTERNAL TRANSFERS ==================
            Route::prefix('external-transfers')
                ->name('external_transfers.')
                ->group(function () {
                    Route::get('/', [ExternalTransferController::class, 'index'])->name('index');
                    Route::get('/create', [ExternalTransferController::class, 'create'])->name('create');
                    Route::post('/', [ExternalTransferController::class, 'store'])->name('store');
                    Route::get('/{externalTransfer}', [ExternalTransferController::class, 'show'])->name('show');
                });

            // ================== STOCKS (ITEM & LOT) ==================
            Route::prefix('stocks')
                ->name('stocks.')
                ->group(function () {
                    Route::get('/items', [InventoryStockController::class, 'items'])->name('items');
                    Route::get('/lots', [InventoryStockController::class, 'lots'])->name('lots');
                    Route::get('{item}/locations', [InventoryStockController::class, 'itemLocations'])
                        ->name('item_locations');
                });

            // ================== STOCK OPNAME ==================
            Route::prefix('stock-opnames')
                ->name('stock_opnames.')
                ->group(function () {
                    Route::get('/', [StockOpnameController::class, 'index'])->name('index');
                    Route::get('/create', [StockOpnameController::class, 'create'])->name('create');
                    Route::post('/', [StockOpnameController::class, 'store'])->name('store');
                    Route::get('/{stockOpname}', [StockOpnameController::class, 'show'])->name('show');
                    Route::get('/{stockOpname}/edit', [StockOpnameController::class, 'edit'])->name('edit');
                    Route::put('/{stockOpname}', [StockOpnameController::class, 'update'])->name('update');
                    Route::post('/{stockOpname}/finalize', [StockOpnameController::class, 'finalize'])
                        ->name('finalize');
                });

            // ================== INVENTORY ADJUSTMENTS ==================
            Route::prefix('adjustments')
                ->name('adjustments.')
                ->group(function () {

                    // INDEX
                    Route::get('/', [InventoryAdjustmentController::class, 'index'])->name('index');

                    // MANUAL ADJUSTMENT (harus sebelum {inventoryAdjustment})
                    Route::get('/manual/create', [InventoryAdjustmentController::class, 'createManual'])
                        ->name('manual.create');

                    Route::post('/manual', [InventoryAdjustmentController::class, 'storeManual'])
                        ->name('manual.store');

                    // AJAX ITEMS (harus sebelum {inventoryAdjustment})
                    Route::get('/items', [InventoryAdjustmentController::class, 'itemsForWarehouse'])
                        ->name('items_for_warehouse');

                    // DETAIL DOKUMEN (PALING BAWAH)
                    Route::get('/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'show'])
                        ->name('show');
                });
        });

    /*
    |--------------------------------------------------------------------------
    | RTS & PRD STOCK REQUESTS
    |--------------------------------------------------------------------------
     */
    Route::prefix('rts/stock-requests')
        ->name('rts.stock-requests.')
        ->group(function () {
            Route::get('/', [RtsStockRequestController::class, 'index'])->name('index');
            Route::get('/create', [RtsStockRequestController::class, 'create'])->name('create');
            Route::post('/', [RtsStockRequestController::class, 'store'])->name('store');
            Route::get('/{stockRequest}', [RtsStockRequestController::class, 'show'])->name('show');
        });

    Route::prefix('prd/stock-requests')
        ->name('prd.stock-requests.')
        ->group(function () {
            Route::get('/', [RtsStockRequestProcessController::class, 'index'])->name('index');
            Route::get('/{stockRequest}/process', [RtsStockRequestProcessController::class, 'edit'])->name('edit');
            Route::post('/{stockRequest}/process', [RtsStockRequestProcessController::class, 'update'])->name('update');
            Route::get('/{stockRequest}', [RtsStockRequestProcessController::class, 'show'])->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | API untuk dipakai di Blade (stok available + summary)
    |--------------------------------------------------------------------------
     */
    Route::prefix('api')
        ->name('api.')
        ->group(function () {
            Route::get('/stock/available', [StockApiController::class, 'available'])
                ->name('stock.available'); // route('api.stock.available')

            Route::get('/stock/summary', [StockApiController::class, 'summary'])
                ->name('stock.summary'); // route('api.stock.summary')
        });
});
