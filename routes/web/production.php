<?php

use App\Http\Controllers\Inventory\InventoryAdjustmentController;
use App\Http\Controllers\Production\CuttingJobController;
use App\Http\Controllers\Production\FinishingJobController;
use App\Http\Controllers\Production\FinishingRepairController;
use App\Http\Controllers\Production\PackingJobController;
use App\Http\Controllers\Production\ProductionDashboardController;
use App\Http\Controllers\Production\ProductionPriorityController;
use App\Http\Controllers\Production\ProductionReportController;
use App\Http\Controllers\Production\QcController;
use App\Http\Controllers\Production\SewingPickupController;
use App\Http\Controllers\Production\SewingRejectReturnController;
use App\Http\Controllers\Production\SewingReturnController;

/*
|--------------------------------------------------------------------------
| PRODUCTION (Owner + Operating)
|--------------------------------------------------------------------------
 */
Route::middleware(['web', 'auth', 'access:production'])
    ->prefix('production')
    ->name('production.')
    ->group(function () {

        /*
    |--------------------------------------------------------------------------
    | CUTTING JOBS
    |--------------------------------------------------------------------------
     */
        Route::prefix('cutting-jobs')->name('cutting_jobs.')->group(function () {
            Route::get('/', [CuttingJobController::class, 'index'])->name('index');
            Route::get('/create', [CuttingJobController::class, 'create'])->name('create');
            Route::post('/', [CuttingJobController::class, 'store'])->name('store');

            Route::get('/{cuttingJob}', [CuttingJobController::class, 'show'])->name('show');
            Route::get('/{cuttingJob}/edit', [CuttingJobController::class, 'edit'])->name('edit');
            Route::put('/{cuttingJob}', [CuttingJobController::class, 'update'])->name('update');

            Route::post('/{cuttingJob}/send-to-qc', [CuttingJobController::class, 'sendToQc'])
                ->name('send_to_qc');

            Route::post('/{cuttingJob}/void', [CuttingJobController::class, 'void'])
                ->name('void');
        });

        /*
    |--------------------------------------------------------------------------
    | QC
    |--------------------------------------------------------------------------
     */
        Route::get('/qc', [QcController::class, 'index'])->name('qc.index');

        Route::prefix('qc')->name('qc.')->group(function () {
            Route::get('/cutting/{cuttingJob}/edit', [QcController::class, 'editCutting'])
                ->name('cutting.edit');

            Route::put('/cutting/{cuttingJob}', [QcController::class, 'updateCutting'])
                ->name('cutting.update');

            Route::post('/cutting/{cuttingJob}/quick-ok', [QcController::class, 'quickOkCutting'])
                ->name('cutting.quick_ok');

            Route::post('/cutting/{cuttingJob}/cancel', [QcController::class, 'cancelCutting'])
                ->middleware('role:owner')
                ->name('cutting.cancel');

            Route::post('/cutting/{cuttingJob}/revert-to-cutting', [QcController::class, 'revertCuttingToCutting'])
                ->middleware('role:owner')
                ->name('cutting.revert_to_cutting');

            Route::post('/cutting/{cuttingJob}/bundles/{bundle}/adjust', [QcController::class, 'adjustCuttingBundle'])
                ->middleware('role:owner')
                ->name('cutting.bundle_adjust');
        });

        /*
    |--------------------------------------------------------------------------
    | SEWING (Pickups + Returns)
    |--------------------------------------------------------------------------
     */
        Route::prefix('sewing')->name('sewing.')->group(function () {

            // ===== Sewing Pickups =====
            Route::prefix('pickups')->name('pickups.')->group(function () {
                Route::get('/', [SewingPickupController::class, 'index'])->name('index');
                Route::get('/bundles-ready', [SewingPickupController::class, 'bundlesReady'])->name('bundles_ready');

                Route::get('/create', [SewingPickupController::class, 'create'])->name('create');
                Route::post('/', [SewingPickupController::class, 'store'])->name('store');

                Route::get('/{pickup}/supplies', [SewingPickupController::class, 'editSupplies'])->name('supplies.edit');
                Route::put('/{pickup}/supplies', [SewingPickupController::class, 'updateSupplies'])->name('supplies.update');
                Route::match(['post', 'patch'], '/lines/{line}/supplies', [SewingPickupController::class, 'updateLineSupplies'])
                    ->whereNumber('line')
                    ->name('lines.supplies.update');

                Route::get('/{pickup}', [SewingPickupController::class, 'show'])->name('show');
                Route::get('/{pickup}/print', [SewingPickupController::class, 'print'])->name('print');
                Route::get('/{pickup}/edit', [SewingPickupController::class, 'edit'])->name('edit');
                Route::put('/{pickup}', [SewingPickupController::class, 'update'])->name('update');

                Route::delete('/{pickup}', [SewingPickupController::class, 'destroy'])->name('destroy');
                Route::post('/{pickup}/void', [SewingPickupController::class, 'void'])->name('void');
                Route::post('/{pickup}/lines/{line}/void', [SewingPickupController::class, 'voidLine'])
                    ->name('lines.void');
            });

            // ===== Sewing Returns =====
            Route::prefix('returns')->name('returns.')->group(function () {
                Route::get('/', [SewingReturnController::class, 'index'])->name('index');

                // ❌ create & store DIPINDAH ke group role:owner,admin,operating (lihat bawah)

                // ✅ penting: biar "create" gak ketangkep /{return}
                Route::get('/{return}', [SewingReturnController::class, 'show'])
                    ->whereNumber('return')
                    ->name('show');

                Route::get('/{return}/print', [SewingReturnController::class, 'print'])
                    ->whereNumber('return')
                    ->name('print');

                Route::get('/{return}/barcode', [SewingReturnController::class, 'barcode'])
                    ->whereNumber('return')
                    ->name('barcode');

                Route::post('/{return}/void', [SewingReturnController::class, 'void'])
                    ->whereNumber('return')
                    ->name('void');

                Route::delete('/{return}', [SewingReturnController::class, 'destroy'])
                    ->whereNumber('return')
                    ->name('destroy');
            });

            Route::get('/reject-returns', [SewingRejectReturnController::class, 'index'])
                ->name('reject_returns.index');
        });

        /*
    |--------------------------------------------------------------------------
    | FINISHING JOBS
    |--------------------------------------------------------------------------
     */
        Route::get('finishing_jobs/bundle-row', [FinishingJobController::class, 'bundle_row'])
            ->name('finishing_jobs.bundle_row');

        Route::get('finishing_jobs/bundle-info', [FinishingJobController::class, 'bundle_info'])
            ->name('finishing_jobs.bundle_info');

        Route::get('finishing_jobs/bundles-ready', [FinishingJobController::class, 'readyBundles'])
            ->name('finishing_jobs.bundles_ready');

        Route::post('finishing_jobs/{finishing_job}/post', [FinishingJobController::class, 'post'])
            ->name('finishing_jobs.post');

        Route::post('finishing_jobs/{finishing_job}/unpost', [FinishingJobController::class, 'unpost'])
            ->name('finishing_jobs.unpost');

        Route::post('finishing_jobs/{finishingJob}/force-post', [FinishingJobController::class, 'forcePost'])
            ->name('finishing_jobs.force_post');

        Route::resource('finishing_jobs', FinishingJobController::class)->except(['destroy']);

        Route::resource('finishing-repairs', FinishingRepairController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->names('finishing_repairs');

        /*
    |--------------------------------------------------------------------------
    | PACKING
    |--------------------------------------------------------------------------
     */
        Route::get('packing/ready-items', [PackingJobController::class, 'readyItems'])
            ->name('packing_jobs.ready_items');

        Route::resource('packing_jobs', PackingJobController::class)->except(['destroy']);

        Route::post('packing_jobs/{packing_job}/post', [PackingJobController::class, 'post'])
            ->name('packing_jobs.post');

        Route::post('packing_jobs/{packing_job}/unpost', [PackingJobController::class, 'unpost'])
            ->name('packing_jobs.unpost');

        /*
    |--------------------------------------------------------------------------
    | PRODUCTION DASHBOARD (konsolidasi semua report)
    |--------------------------------------------------------------------------
     */
        Route::get('dashboard', [ProductionDashboardController::class, 'index'])
            ->name('dashboard');

        // API lazy-load per tab + filter AJAX
        Route::get('dashboard/data', [ProductionDashboardController::class, 'data'])
            ->name('dashboard.data');

        // Slip upah borongan per operator (siap cetak)
        Route::get('dashboard/slip', [ProductionDashboardController::class, 'slip'])
            ->name('dashboard.slip');

        /*
    |--------------------------------------------------------------------------
    | PRIORITAS PRODUKSI (skor prioritas per SKU)
    |--------------------------------------------------------------------------
     */
        Route::get('priority', [ProductionPriorityController::class, 'index'])
            ->name('priority.index');

        /*
    |--------------------------------------------------------------------------
    | LAPORAN PRODUKSI (rekap throughput + export CSV/XLSX)
    |--------------------------------------------------------------------------
     */
        Route::get('reports', [ProductionReportController::class, 'index'])
            ->name('reports.index');
        Route::get('reports/export', [ProductionReportController::class, 'export'])
            ->name('reports.export');
    });

/*
|--------------------------------------------------------------------------
| PRODUCTION (Owner + Admin + Operating) — Sewing Return CREATE + STORE
|--------------------------------------------------------------------------
| ✅ ini yang membuka akses admin ke:
|   GET  /production/sewing/returns/create
|   POST /production/sewing/returns
 */
Route::middleware(['web', 'auth', 'access:production'])
    ->prefix('production')
    ->name('production.')
    ->group(function () {

        Route::prefix('sewing')->name('sewing.')->group(function () {
            Route::prefix('returns')->name('returns.')->group(function () {
                Route::get('/create', [SewingReturnController::class, 'create'])->name('create');
                Route::post('/', [SewingReturnController::class, 'store'])->name('store');
            });
        });

    });

/*
|--------------------------------------------------------------------------
| PRODUCTION (Owner) — CUTTING OVERPRODUCTION (Inventory Adjustments)
|--------------------------------------------------------------------------
 */
Route::middleware(['web', 'auth', 'access:production', 'role:owner'])
    ->prefix('production/cutting-overproduction')
    ->name('production.cutting_overproduction.')
    ->group(function () {

        Route::get('/create', [InventoryAdjustmentController::class, 'cuttingOverproductionCreate'])
            ->name('create');

        Route::post('/', [InventoryAdjustmentController::class, 'cuttingOverproductionStore'])
            ->name('store');

        Route::post('/{adjustment}/post', [InventoryAdjustmentController::class, 'post'])
            ->name('post');

        Route::post('/{adjustment}/void', [InventoryAdjustmentController::class, 'void'])
            ->name('void');
    });
