<?php

use App\Http\Controllers\Api\StockApiController;
use App\Http\Controllers\Inventory\ExternalTransferController;
use App\Http\Controllers\Inventory\InventoryAdjustmentController;
use App\Http\Controllers\Inventory\InventoryStockController;
use App\Http\Controllers\Inventory\RtsDirectReceiveController;
use App\Http\Controllers\Inventory\RtsStockRequestController;
use App\Http\Controllers\Inventory\StockCardController;
use App\Http\Controllers\Inventory\StockOpnameController;
use App\Http\Controllers\Inventory\TransferController;
use App\Http\Controllers\Inventory\WipAdjustmentController;
use App\Http\Controllers\Inventory\WipCutReconcileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| INVENTORY + STOCK REQUEST ROUTES
|--------------------------------------------------------------------------
| - Inventory internal: owner, admin, operating
| - RTS Stock Requests:
|   - operating: READ ONLY (index/show)
|   - owner/admin: FULL (create/store/confirm/finalize)
| - RTS Direct Receives (Dadakan):
|   - owner/admin: index/create/store/show + operator-wip (ajax)
| - PRD process removed (no more PRD -> TRANSIT)
|--------------------------------------------------------------------------
 */

// ======================================================================
// INVENTORY MAIN (owner + admin + operating)
// ======================================================================
Route::middleware(['web', 'auth', 'role:owner,admin,operating'])->group(function () {

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

            // Sync HPP master → kolom items.hpp (owner only, cost-neutral)
            Route::post('/sync-hpp', [InventoryStockController::class, 'syncHpp'])
                ->middleware('role:owner')
                ->name('sync_hpp');

            Route::get('/{item}/locations', [InventoryStockController::class, 'itemLocations'])->name('item_locations');

            Route::get('/items-legacy', [InventoryStockController::class, 'itemsLegacy'])->name('items_legacy');
        });

        // ================== WIP-CUT RECONCILE (owner only) ==================
        Route::middleware('role:owner')->group(function () {
            Route::get('wip-cut-reconcile', [WipCutReconcileController::class, 'index'])
                ->name('wip_cut_reconcile.index');
            Route::get('wip-cut-reconcile/{item}', [WipCutReconcileController::class, 'show'])
                ->name('wip_cut_reconcile.show');
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
            Route::delete('/{stockOpname}/lines/{line}', [StockOpnameController::class, 'deleteLine'])->name('lines.destroy');

            Route::post('/{stockOpname}/reset-lines', [StockOpnameController::class, 'resetLines'])->name('reset_lines');
            Route::post('/{stockOpname}/reset-all-lines', [StockOpnameController::class, 'resetAllLines'])->name('reset_all_lines');

            Route::post('/{stockOpname}/reopen', [StockOpnameController::class, 'reopen'])->name('reopen');
            Route::post('/{stockOpname}/cancel', [\App\Http\Controllers\Inventory\StockOpnameController::class, 'cancel'])
                ->name('cancel');

        });

        // ================== INVENTORY ADJUSTMENTS ==================
        Route::prefix('adjustments')->name('adjustments.')->group(function () {
            Route::get('/', [InventoryAdjustmentController::class, 'index'])->name('index');

            Route::get('/manual/create', [InventoryAdjustmentController::class, 'createManual'])->name('manual.create');
            Route::post('/manual', [InventoryAdjustmentController::class, 'storeManual'])->name('manual.store');

            Route::get('/items', [InventoryAdjustmentController::class, 'itemsForWarehouse'])->name('items_for_warehouse');

            Route::get('/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'show'])->name('show');
            Route::post('/{inventoryAdjustment}/approve', [InventoryAdjustmentController::class, 'approve'])->name('approve');
        });

        // ================== WIP ADJUSTMENTS ==================
        Route::prefix('wip-adjustments')->name('wip_adjustments.')->group(function () {
            Route::get('/', [WipAdjustmentController::class, 'index'])->name('index');
            Route::get('/create', [WipAdjustmentController::class, 'create'])->name('create');
            Route::post('/', [WipAdjustmentController::class, 'store'])->name('store');

            Route::get('/ajax/items', [WipAdjustmentController::class, 'items'])->name('items');
            Route::get('/ajax/bundles', [WipAdjustmentController::class, 'bundles'])->name('bundles');

            Route::get('/{inventoryAdjustment}', [WipAdjustmentController::class, 'show'])->name('show');
            Route::post('/{inventoryAdjustment}/approve', [WipAdjustmentController::class, 'approve'])->name('approve');
        });
    });
});

// ======================================================================
// RTS STOCK REQUESTS (NO TODAY ROUTE)
// - WRITE: owner + admin
// - READ ONLY: owner + admin + operating
// ======================================================================

// ✅ WRITE ONLY (define dulu agar /create tidak ketabrak)
Route::middleware(['web', 'auth', 'role:owner,admin'])->group(function () {
    Route::prefix('rts/stock-requests')->name('rts.stock-requests.')->group(function () {

        Route::get('/create', [RtsStockRequestController::class, 'create'])->name('create');
        Route::post('/', [RtsStockRequestController::class, 'store'])->name('store');

        Route::get('/{stockRequest}/confirm', [RtsStockRequestController::class, 'confirmReceive'])
            ->whereNumber('stockRequest')
            ->name('confirm');

        Route::post('/{stockRequest}/finalize', [RtsStockRequestController::class, 'finalize'])
            ->whereNumber('stockRequest')
            ->name('finalize');
    });
});

// ✅ READ ONLY
Route::middleware(['web', 'auth', 'role:owner,admin,operating'])->group(function () {
    Route::prefix('rts/stock-requests')->name('rts.stock-requests.')->group(function () {

        Route::get('/', [RtsStockRequestController::class, 'index'])->name('index');

        Route::get('/{stockRequest}', [RtsStockRequestController::class, 'show'])
            ->whereNumber('stockRequest')
            ->name('show');
    });
});

// ======================================================================
// RTS DIRECT RECEIVES (DADAKAN) - owner + admin
// - includes AJAX operator-wip for auto_sr
// ======================================================================
Route::middleware(['web', 'auth', 'role:owner,admin'])->group(function () {
    Route::prefix('rts/direct-receives')->name('rts.direct-receives.')->group(function () {

        Route::get('/', [RtsDirectReceiveController::class, 'index'])->name('index');

        // ✅ AJAX: operator outstanding WIP-SEW
        Route::get('/operator-wip', [RtsDirectReceiveController::class, 'operatorWip'])
            ->name('operator_wip');

        // ✅ static route must be before {directReceive}
        Route::get('/create', [RtsDirectReceiveController::class, 'create'])->name('create');
        Route::post('/', [RtsDirectReceiveController::class, 'store'])->name('store');

        Route::get('/{directReceive}', [RtsDirectReceiveController::class, 'show'])
            ->whereNumber('directReceive')
            ->name('show');
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
