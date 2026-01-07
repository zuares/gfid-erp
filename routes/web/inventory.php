<?php

use App\Http\Controllers\Api\StockApiController;
use App\Http\Controllers\Inventory\ExternalTransferController;
use App\Http\Controllers\Inventory\InventoryAdjustmentController;
use App\Http\Controllers\Inventory\InventoryStockController;
use App\Http\Controllers\Inventory\PrdDispatchCorrectionController;
use App\Http\Controllers\Inventory\RtsStockRequestController;
use App\Http\Controllers\Inventory\RtsStockRequestProcessController;
use App\Http\Controllers\Inventory\StockCardController;
use App\Http\Controllers\Inventory\StockOpnameController;
use App\Http\Controllers\Inventory\TransferController;

// ✅ NEW (OPSI 1: PRD DISPATCH CORRECTION)
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| INVENTORY + STOCK REQUEST ROUTES
|--------------------------------------------------------------------------
| Notes:
| - Inventory internal: owner, admin, operating
| - RTS Stock Requests: owner, admin
| - PRD Stock Requests process: owner, admin, operating (sesuai middleware inventory internal)
| - API stock: owner, admin, operating
|--------------------------------------------------------------------------
 */

// ======================================================================
// INVENTORY MAIN (owner + admin + operating)
// ======================================================================
Route::middleware(['web', 'auth', 'role:owner,admin,operating'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | INVENTORY NAMESPACE: /inventory/*
    |--------------------------------------------------------------------------
     */
    Route::prefix('inventory')->name('inventory.')->group(function () {

        // ================== STOCK CARD ==================
        Route::get('stock-card', [StockCardController::class, 'index'])->name('stock_card.index');
        Route::get('stock-card/export', [StockCardController::class, 'export'])->name('stock_card.export');

        // ================== INTERNAL TRANSFERS ==================
        Route::resource('transfers', TransferController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->names('transfers');

        // ================== EXTERNAL TRANSFERS ==================
        Route::prefix('external-transfers')->name('external_transfers.')->group(function () {
            Route::get('/', [ExternalTransferController::class, 'index'])->name('index');
            Route::get('/create', [ExternalTransferController::class, 'create'])->name('create');
            Route::post('/', [ExternalTransferController::class, 'store'])->name('store');
            Route::get('/{externalTransfer}', [ExternalTransferController::class, 'show'])->name('show');
        });

        // ================== STOCKS (ITEM & LOT) ==================
        Route::prefix('stocks')->name('stocks.')->group(function () {
            Route::get('/items', [InventoryStockController::class, 'items'])->name('items');
            Route::get('/lots', [InventoryStockController::class, 'lots'])->name('lots');

            Route::get('/{item}/locations', [InventoryStockController::class, 'itemLocations'])
                ->name('item_locations');

            Route::get('/items-legacy', [InventoryStockController::class, 'itemsLegacy'])
                ->name('items_legacy');
        });

        // ================== STOCK OPNAME ==================
        Route::prefix('stock-opnames')->name('stock_opnames.')->group(function () {
            Route::get('/', [StockOpnameController::class, 'index'])->name('index');
            Route::get('/create', [StockOpnameController::class, 'create'])->name('create');
            Route::post('/', [StockOpnameController::class, 'store'])->name('store');

            Route::get('/{stockOpname}', [StockOpnameController::class, 'show'])->name('show');
            Route::get('/{stockOpname}/edit', [StockOpnameController::class, 'edit'])->name('edit');
            Route::put('/{stockOpname}', [StockOpnameController::class, 'update'])->name('update');

            Route::post('/{stockOpname}/finalize', [StockOpnameController::class, 'finalize'])->name('finalize');

            Route::post('/{stockOpname}/lines', [StockOpnameController::class, 'addLine'])->name('lines.store');
            Route::delete('/{stockOpname}/lines/{line}', [StockOpnameController::class, 'deleteLine'])
                ->name('lines.destroy');

            Route::post('/{stockOpname}/reset-lines', [StockOpnameController::class, 'resetLines'])
                ->name('reset_lines');

            Route::post('/{stockOpname}/reset-all-lines', [StockOpnameController::class, 'resetAllLines'])
                ->name('reset_all_lines');

            Route::post('/{stockOpname}/reopen', [StockOpnameController::class, 'reopen'])
                ->name('reopen');
        });

        // ================== INVENTORY ADJUSTMENTS ==================
        Route::prefix('adjustments')->name('adjustments.')->group(function () {

            // INDEX
            Route::get('/', [InventoryAdjustmentController::class, 'index'])->name('index');

            // MANUAL ADJUSTMENT
            Route::get('/manual/create', [InventoryAdjustmentController::class, 'createManual'])
                ->name('manual.create');

            Route::post('/manual', [InventoryAdjustmentController::class, 'storeManual'])
                ->name('manual.store');

            // AJAX ITEMS
            Route::get('/items', [InventoryAdjustmentController::class, 'itemsForWarehouse'])
                ->name('items_for_warehouse');

            // DETAIL DOKUMEN
            Route::get('/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'show'])
                ->name('show');

            Route::post('/{inventoryAdjustment}/approve', [InventoryAdjustmentController::class, 'approve'])
                ->name('approve');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | PRD STOCK REQUESTS (Proses permintaan stok di gudang produksi)
    | URL: /prd/stock-requests/*
    | Name: prd.stock-requests.*
    |--------------------------------------------------------------------------
     */
    Route::prefix('prd/stock-requests')->name('prd.stock-requests.')->group(function () {

        // list
        Route::get('/', [RtsStockRequestProcessController::class, 'index'])->name('index');

        // detail
        Route::get('/{stockRequest}', [RtsStockRequestProcessController::class, 'show'])->name('show');

        // process screen
        Route::get('/{stockRequest}/process', [RtsStockRequestProcessController::class, 'edit'])->name('edit');

        // confirm dispatch (PRD -> TRANSIT)
        Route::post('/{stockRequest}/process/confirm', [RtsStockRequestProcessController::class, 'confirm'])
            ->name('confirm');

        // ==============================
        // ✅ NEW: PRD Dispatch Correction
        // ==============================
        Route::get('/{stockRequest}/dispatch-corrections/create', [PrdDispatchCorrectionController::class, 'create'])
            ->name('dispatch_corrections.create');

        Route::post('/{stockRequest}/dispatch-corrections', [PrdDispatchCorrectionController::class, 'store'])
            ->name('dispatch_corrections.store');

        Route::get('/dispatch-corrections/{correction}', [PrdDispatchCorrectionController::class, 'show'])
            ->name('dispatch_corrections.show');
    });
});

// ======================================================================
// RTS STOCK REQUESTS (owner + admin)
// ======================================================================
Route::middleware(['web', 'auth', 'role:owner,admin'])->group(function () {

    Route::prefix('rts/stock-requests')->name('rts.stock-requests.')->group(function () {

        Route::get('/', [RtsStockRequestController::class, 'index'])->name('index');

        // ✅ QUICK TODAY harus di atas {stockRequest}
        Route::get('/today', [RtsStockRequestController::class, 'quickToday'])->name('today');

        Route::get('/create', [RtsStockRequestController::class, 'create'])->name('create');
        Route::post('/', [RtsStockRequestController::class, 'store'])->name('store');

        Route::get('/{stockRequest}', [RtsStockRequestController::class, 'show'])->name('show');

        // RTS receive flow
        Route::get('/{stockRequest}/confirm', [RtsStockRequestController::class, 'confirmReceive'])->name('confirm');
        Route::post('/{stockRequest}/finalize', [RtsStockRequestController::class, 'finalize'])->name('finalize');

        // RTS direct pickup
        Route::post('/{stockRequest}/direct-pickup', [RtsStockRequestController::class, 'directPickup'])
            ->name('direct-pickup');
    });
});

// ======================================================================
// STOCK API (owner + admin + operating)
// ======================================================================
Route::middleware(['web', 'auth', 'role:owner,admin,operating'])->group(function () {

    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/stock/available', [StockApiController::class, 'available'])->name('stock.available');
        Route::get('/stock/summary', [StockApiController::class, 'summary'])->name('stock.summary');
    });
});
