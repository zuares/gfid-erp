<?php

use App\Http\Controllers\Production\CuttingJobController;
use App\Http\Controllers\Production\FinishingJobController;
use App\Http\Controllers\Production\PackingJobController;
use App\Http\Controllers\Production\QcController;
use App\Http\Controllers\Production\SewingPickupController;
use App\Http\Controllers\Production\SewingReturnController;

Route::middleware(['auth'])->group(function () {

    // ==========================
    // PRODUCTION ROOT GROUP
    // ==========================
    Route::prefix('production')->name('production.')->group(function () {

        // ==========================
        // CUTTING JOBS
        // ==========================
        Route::prefix('cutting-jobs')
            ->name('cutting_jobs.')
            ->group(function () {

                Route::get('/', [CuttingJobController::class, 'index'])
                    ->name('index');

                Route::get('/create', [CuttingJobController::class, 'create'])
                    ->name('create');

                Route::post('/', [CuttingJobController::class, 'store'])
                    ->name('store');

                Route::get('/{cuttingJob}', [CuttingJobController::class, 'show'])
                    ->name('show');

                Route::post('/{cuttingJob}/send-to-qc', [CuttingJobController::class, 'sendToQc'])
                    ->name('send_to_qc');

                Route::get('/{cuttingJob}/edit', [CuttingJobController::class, 'edit'])
                    ->name('edit');

                Route::put('/{cuttingJob}', [CuttingJobController::class, 'update'])
                    ->name('update');
            });

        // ==========================
        // QC (Cutting / Sewing / Packing overview)
        // ==========================

        // Index overview QC semua stage (cutting / sewing / packing)
        Route::get('/qc', [QcController::class, 'index'])
            ->name('qc.index');

        Route::prefix('qc')->name('qc.')->group(function () {

            // QC Cutting
            Route::get('/cutting/{cuttingJob}/edit', [QcController::class, 'editCutting'])
                ->name('cutting.edit');

            Route::put('/cutting/{cuttingJob}', [QcController::class, 'updateCutting'])
                ->name('cutting.update');

            // (nanti bisa tambah: qc.sewing.*, qc.packing.*, dll)
        });

        // ==========================
        // SEWING (Pickups + Returns)
        // ==========================
        Route::prefix('sewing')->group(function () {

            // ---- Sewing Pickups ----
            Route::prefix('pickups')
                ->name('sewing_pickups.')
                ->group(function () {

                    Route::get('/', [SewingPickupController::class, 'index'])
                        ->name('index');

                    Route::get('/bundles-ready', [SewingPickupController::class, 'bundlesReady'])
                        ->name('bundles_ready');

                    Route::get('/create', [SewingPickupController::class, 'create'])
                        ->name('create');

                    Route::post('/', [SewingPickupController::class, 'store'])
                        ->name('store');

                    Route::get('/{pickup}', [SewingPickupController::class, 'show'])
                        ->name('show');

                    Route::get('/{pickup}/edit', [SewingPickupController::class, 'edit'])
                        ->name('edit');

                    Route::put('/{pickup}', [SewingPickupController::class, 'update'])
                        ->name('update');

                    Route::delete('/{pickup}', [SewingPickupController::class, 'destroy'])
                        ->name('destroy');
                });

            // ---- Sewing Returns ----
            Route::prefix('returns')
                ->name('sewing_returns.')
                ->group(function () {

                    Route::get('/', [SewingReturnController::class, 'index'])
                        ->name('index');

                    Route::get('/create', [SewingReturnController::class, 'create'])
                        ->name('create');

                    Route::post('/', [SewingReturnController::class, 'store'])
                        ->name('store');

                    Route::get('/{return}', [SewingReturnController::class, 'show'])
                        ->name('show');

                    Route::delete('/{return}', [SewingReturnController::class, 'destroy'])
                        ->name('destroy');
                });

            // ---- Laporan Performa Operator Jahit ----
            Route::get('/report/operators', [SewingReturnController::class, 'operatorSummary'])
                ->name('sewing_returns.report_operators');

        });

        // ==========================
        // PRODUCTION REPORTS
        // ==========================

        // action khusus untuk posting & jalankan inventory
        Route::post('finishing_jobs/{finishing_job}/post', [FinishingJobController::class, 'post'])
            ->name('finishing_jobs.post');
        // 🔁 action untuk UNPOST (balikkan stok)
        Route::post('finishing_jobs/{finishing_job}/unpost', [FinishingJobController::class, 'unpost'])
            ->name('finishing_jobs.unpost');
        Route::get('finishing_jobs/bundles-ready', [FinishingJobController::class, 'readyBundles'])
            ->name('finishing_jobs.bundles_ready');
        Route::resource('finishing_jobs', FinishingJobController::class)
            ->except(['destroy']);
        // Report Finishing per Item (header)
        Route::get('finishing_jobs/report/per-item', [FinishingJobController::class, 'reportPerItem'])
            ->name('finishing_jobs.report_per_item');

        // 🔍 Drilldown: detail per item → list finishing job
        Route::get('finishing_jobs/report/per-item/{item}', [FinishingJobController::class, 'reportPerItemDetail'])
            ->name('finishing_jobs.report_per_item_detail');

    });
});

Route::prefix('production')
    ->name('production.')
    ->middleware(['auth'])
    ->group(function () {

        Route::get('packing/fg-ready', [PackingJobController::class, 'readyItems'])
            ->name('packing.fg_ready');
        Route::resource('packing_jobs', PackingJobController::class)
            ->except(['destroy']);

        Route::post('packing_jobs/{packing_job}/post', [PackingJobController::class, 'post'])
            ->name('packing_jobs.post');

        Route::post('packing_jobs/{packing_job}/unpost', [PackingJobController::class, 'unpost'])
            ->name('packing_jobs.unpost');

        // (opsional) daftar item FG ready to pack
        Route::get('packing/fg-ready', [PackingJobController::class, 'readyItems'])
            ->name('packing.fg_ready');
    });
