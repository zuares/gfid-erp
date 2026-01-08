<?php

use App\Http\Controllers\Production\CuttingJobController;
use App\Http\Controllers\Production\FinishingJobController;
use App\Http\Controllers\Production\PackingJobController;
use App\Http\Controllers\Production\ProductionReportController;
use App\Http\Controllers\Production\QcController;
use App\Http\Controllers\Production\SewingPickupController;
use App\Http\Controllers\Production\SewingReportController;
use App\Http\Controllers\Production\SewingReturnController;
use App\Http\Controllers\Production\WipFinAdjustmentController;

Route::middleware(['web', 'auth', 'role:owner,operating'])
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
        });

        /*
    |--------------------------------------------------------------------------
    | QC
    |--------------------------------------------------------------------------
     */
        Route::get('/qc', [QcController::class, 'index'])->name('qc.index');

        Route::prefix('qc')->name('qc.')->group(function () {
            Route::get('/cutting/{cuttingJob}/edit', [QcController::class, 'editCutting'])->name('cutting.edit');
            Route::put('/cutting/{cuttingJob}', [QcController::class, 'updateCutting'])->name('cutting.update');
            Route::post('/cutting/{cuttingJob}/cancel', [QcController::class, 'cancelCutting'])
                ->name('cutting.cancel');
        });

        /*
    |--------------------------------------------------------------------------
    | SEWING (Pickups + Returns + Progress Adjustments + Reports)
    |--------------------------------------------------------------------------
     */
        Route::prefix('sewing')->name('sewing.')->group(function () {

            // ===== Sewing Pickups =====
            Route::prefix('pickups')->name('pickups.')->group(function () {
                Route::get('/', [SewingPickupController::class, 'index'])->name('index');
                Route::get('/bundles-ready', [SewingPickupController::class, 'bundlesReady'])->name('bundles_ready');

                Route::get('/create', [SewingPickupController::class, 'create'])->name('create');
                Route::post('/', [SewingPickupController::class, 'store'])->name('store');

                Route::get('/{pickup}', [SewingPickupController::class, 'show'])->name('show');
                Route::get('/{pickup}/edit', [SewingPickupController::class, 'edit'])->name('edit');
                Route::put('/{pickup}', [SewingPickupController::class, 'update'])->name('update');
                Route::delete('/{pickup}', [SewingPickupController::class, 'destroy'])->name('destroy');
                Route::post('{pickup}/void', [SewingPickupController::class, 'void'])->name('void');
                Route::post('/{pickup}/lines/{line}/void', [SewingPickupController::class, 'voidLine'])
                    ->name('lines.void');
            });

            // ===== Sewing Returns =====
            Route::prefix('returns')->name('returns.')->group(function () {
                Route::get('/', [SewingReturnController::class, 'index'])->name('index');
                Route::get('/create', [SewingReturnController::class, 'create'])->name('create');
                Route::post('/', [SewingReturnController::class, 'store'])->name('store');

                Route::get('/{return}', [SewingReturnController::class, 'show'])->name('show');
                Route::delete('/{return}', [SewingReturnController::class, 'destroy'])->name('destroy');
            });

            // ===== Sewing Reports =====
            // Route::prefix('reports')->name('reports.')->group(function () {
            //     Route::get('/operators', [SewingReportController::class, 'operatorSummary'])->name('operators');
            //     Route::get('/outstanding', [SewingReportController::class, 'outstanding'])->name('outstanding');
            //     Route::get('/aging-wip-sew', [SewingReportController::class, 'agingWipSew'])->name('aging_wip_sew');
            //     Route::get('/productivity', [SewingReportController::class, 'productivity'])->name('productivity');
            //     Route::get('/partial-pickup', [SewingReportController::class, 'partialPickup'])->name('partial_pickup');
            //     Route::get('/reject-analysis', [SewingReportController::class, 'rejectAnalysis'])->name('reject_analysis');
            //     Route::get('/dashboard', [SewingReportController::class, 'dailyDashboard'])->name('dashboard');
            //     Route::get('/lead-time', [SewingReportController::class, 'leadTime'])->name('lead_time');
            //     Route::get('/operator-behavior', [SewingReportController::class, 'operatorBehavior'])->name('operator_behavior');
            // });
        });

        /*
    |--------------------------------------------------------------------------
    | FINISHING JOBS + REPORTS
    |--------------------------------------------------------------------------
     */
        // ajax helpers
        Route::get('finishing_jobs/bundle-row', [FinishingJobController::class, 'bundle_row'])
            ->name('finishing_jobs.bundle_row');
        Route::get('finishing_jobs/bundle-info', [FinishingJobController::class, 'bundle_info'])
            ->name('finishing_jobs.bundle_info');
        Route::get('finishing_jobs/bundles-ready', [FinishingJobController::class, 'readyBundles'])
            ->name('finishing_jobs.bundles_ready');

        // actions
        Route::post('finishing_jobs/{finishing_job}/post', [FinishingJobController::class, 'post'])
            ->name('finishing_jobs.post');
        Route::post('finishing_jobs/{finishing_job}/unpost', [FinishingJobController::class, 'unpost'])
            ->name('finishing_jobs.unpost');
        Route::post('finishing_jobs/{finishingJob}/force-post', [FinishingJobController::class, 'forcePost'])
            ->name('finishing_jobs.force_post');

        Route::resource('finishing_jobs', FinishingJobController::class)->except(['destroy']);

        Route::get('finishing_jobs/report/per-item', [FinishingJobController::class, 'reportPerItem'])
            ->name('finishing_jobs.report_per_item');
        Route::get('finishing_jobs/report/per-item/{item}', [FinishingJobController::class, 'reportPerItemDetail'])
            ->name('finishing_jobs.report_per_item_detail');

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
    | PRODUCTION REPORTS (GLOBAL)
    |--------------------------------------------------------------------------
     */
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('daily-production', [ProductionReportController::class, 'dailyProduction'])->name('daily_production');
            Route::get('reject-detail', [ProductionReportController::class, 'rejectDetail'])->name('reject_detail');
            Route::get('wip-sewing-age', [ProductionReportController::class, 'wipSewingAge'])->name('wip_sewing_age');
            Route::get('sewing-per-item', [ProductionReportController::class, 'sewingPerItem'])->name('sewing_per_item');
            Route::get('finishing-jobs', [ProductionReportController::class, 'finishingJobs'])->name('finishing_jobs');
            Route::get('flow-dashboard', [ProductionReportController::class, 'productionFlowDashboard'])->name('production_flow_dashboard');
        });

        /*
    |--------------------------------------------------------------------------
    | SEWING REPORTS (named as production.reports.*)
    |--------------------------------------------------------------------------
     */
        Route::prefix('sewing/reports')->name('reports.')->group(function () {
            Route::get('/operators', [SewingReportController::class, 'operatorSummary'])->name('operators');
            Route::get('/outstanding', [SewingReportController::class, 'outstanding'])->name('outstanding');
            Route::get('/aging-wip-sew', [SewingReportController::class, 'agingWipSew'])->name('aging_wip_sew');
            Route::get('/productivity', [SewingReportController::class, 'productivity'])->name('productivity');
            Route::get('/partial-pickup', [SewingReportController::class, 'partialPickup'])->name('partial_pickup');
            Route::get('/reject-analysis', [SewingReportController::class, 'rejectAnalysis'])->name('reject_analysis');
            Route::get('/dashboard', [SewingReportController::class, 'dailyDashboard'])->name('dashboard');
            Route::get('/lead-time', [SewingReportController::class, 'leadTime'])->name('lead_time');
            Route::get('/operator-behavior', [SewingReportController::class, 'operatorBehavior'])->name('operator_behavior');
        });

    });

Route::middleware(['web', 'auth', 'role:owner,admin,operating'])->group(function () {
    Route::prefix('production')->as('production.')->group(function () {
        Route::resource('wip-fin-adjustments', WipFinAdjustmentController::class)
            ->except(['destroy']);

        Route::post('wip-fin-adjustments/{adjustment}/post', [WipFinAdjustmentController::class, 'post'])
            ->name('wip-fin-adjustments.post');

        Route::post('wip-fin-adjustments/{adjustment}/void', [WipFinAdjustmentController::class, 'void'])
            ->name('wip-fin-adjustments.void');
    });
});
