<?php

use App\Http\Controllers\Api\StockApiController;
use App\Http\Controllers\Inventory\ExternalTransferController;
use App\Http\Controllers\Inventory\InventoryAdjustmentController;
use App\Http\Controllers\Inventory\InventoryIntelligenceController;
use App\Http\Controllers\Inventory\WarehouseIntelligenceController;
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
| - RTS receive langsung mengambil stok WH-PRD -> WH-RTS
|--------------------------------------------------------------------------
 */

// ======================================================================
// INVENTORY MAIN (owner + admin + operating)
// ======================================================================
Route::middleware(['web', 'auth', 'access:inventory'])->group(function () {

    Route::prefix('inventory')->name('inventory.')->group(function () {

        // ================== INVENTORY INTELLIGENCE (dashboard forecast) ==================
        Route::get('intelligence', [InventoryIntelligenceController::class, 'index'])
            ->name('intelligence');
        Route::get('intelligence/data', [InventoryIntelligenceController::class, 'data'])
            ->name('intelligence.data');
        Route::get('intelligence/slip', [InventoryIntelligenceController::class, 'slip'])
            ->name('intelligence.slip');
        Route::get('intelligence/export', [InventoryIntelligenceController::class, 'export'])
            ->name('intelligence.export');
        Route::post('intelligence/lead-time', [InventoryIntelligenceController::class, 'updateLeadTime'])
            ->middleware('role:owner,admin')
            ->name('intelligence.lead_time');
        // Reuse the warehouse intelligence flow so both pages append to the
        // user's active PR draft and continue to supplier allocation.
        Route::post('intelligence/request-pr-draft', [WarehouseIntelligenceController::class, 'requestPrDraft'])
            ->name('intelligence.request_pr_draft');

        // ================== WAREHOUSE INTELLIGENCE ==================
        Route::get('warehouse-intelligence', [WarehouseIntelligenceController::class, 'index'])
            ->name('warehouse_intelligence');
        Route::get('warehouse-intelligence/insights', [WarehouseIntelligenceController::class, 'insights'])
            ->name('warehouse_intelligence.insights');
        Route::get('warehouse-intelligence/data', [WarehouseIntelligenceController::class, 'tabData'])
            ->name('warehouse_intelligence.data');
        Route::post('warehouse-intelligence/limits', [WarehouseIntelligenceController::class, 'updateLimits'])
            ->name('warehouse_intelligence.limits');
        Route::post('warehouse-intelligence/request-draft', [WarehouseIntelligenceController::class, 'requestDraft'])
            ->name('warehouse_intelligence.request_draft');
        Route::post('warehouse-intelligence/request-pr-draft', [WarehouseIntelligenceController::class, 'requestPrDraft'])
            ->name('warehouse_intelligence.request_pr_draft');

        // ================== STOCK CARD ==================
        Route::get('stock-card', [StockCardController::class, 'index'])->name('stock_card.index');
        Route::get('stock-card/export', [StockCardController::class, 'export'])->name('stock_card.export');

        // ================== BARCODE LABEL GENERATOR (admin) ==================
        Route::middleware('role:admin')->prefix('barcodes')->name('barcodes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Inventory\BarcodeLabelController::class, 'create'])->name('create');
            Route::get('/print', [\App\Http\Controllers\Inventory\BarcodeLabelController::class, 'print'])->name('print');
        });

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
            Route::get('/items/available', [InventoryStockController::class, 'available'])->name('items.available');
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
            Route::post('/{stockOpname}/lines/{line}/unit-cost', [StockOpnameController::class, 'updateLineUnitCost'])->name('lines.unit_cost');
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
            Route::post('/items', [InventoryAdjustmentController::class, 'storeQuickItem'])->name('items.quick_store');

            Route::get('/{inventoryAdjustment}', [InventoryAdjustmentController::class, 'show'])->name('show');
            Route::post('/{inventoryAdjustment}/post', [InventoryAdjustmentController::class, 'post'])->name('post');
            Route::post('/{inventoryAdjustment}/approve', [InventoryAdjustmentController::class, 'approve'])->name('approve');
            Route::post('/{inventoryAdjustment}/void', [InventoryAdjustmentController::class, 'void'])->name('void');
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
Route::middleware(['web', 'auth', 'access:inventory', 'role:owner,admin'])->group(function () {
    Route::prefix('rts/stock-requests')->name('rts.stock-requests.')->group(function () {

        Route::get('/create', [RtsStockRequestController::class, 'create'])->name('create');
        Route::post('/', [RtsStockRequestController::class, 'store'])->name('store');
        Route::get('/{stockRequest}/edit', [RtsStockRequestController::class, 'edit'])->name('edit');
        Route::put('/{stockRequest}', [RtsStockRequestController::class, 'update'])->name('update');
        Route::delete('/{stockRequest}', [RtsStockRequestController::class, 'destroy'])->name('destroy');

        Route::get('/{stockRequest}/confirm', [RtsStockRequestController::class, 'confirmReceive'])
            ->whereNumber('stockRequest')
            ->name('confirm');

        Route::post('/{stockRequest}/finalize', [RtsStockRequestController::class, 'finalize'])
            ->whereNumber('stockRequest')
            ->name('finalize');
    });
});

// ✅ READ ONLY
Route::middleware(['web', 'auth', 'access:inventory'])->group(function () {
    Route::prefix('rts/stock-requests')->name('rts.stock-requests.')->group(function () {

        Route::get('/', [RtsStockRequestController::class, 'index'])->name('index');

        // ✅ Cetak barcode (qty label default = qty diterima/diminta, bisa disesuaikan)
        Route::get('/{stockRequest}/barcode', [RtsStockRequestController::class, 'barcode'])
            ->whereNumber('stockRequest')
            ->name('barcode');
        Route::get('/{stockRequest}/barcode-print', [\App\Http\Controllers\Inventory\BarcodeLabelController::class, 'print'])
            ->whereNumber('stockRequest')
            ->name('barcode_print');

        Route::get('/{stockRequest}', [RtsStockRequestController::class, 'show'])
            ->whereNumber('stockRequest')
            ->name('show');
    });
});

// ======================================================================
// RTS DIRECT RECEIVES (DADAKAN) - owner + admin
// - includes AJAX operator-wip for auto_sr
// ======================================================================
Route::middleware(['web', 'auth', 'access:inventory', 'role:owner,admin'])->group(function () {
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
Route::middleware(['web', 'auth', 'access:inventory'])->group(function () {
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/stock/available', [StockApiController::class, 'available'])->name('stock.available');
        Route::get('/stock/summary', [StockApiController::class, 'summary'])->name('stock.summary');
    });
});
