
<?php

use App\Http\Controllers\Production\CuttingJobController;
use App\Http\Controllers\Production\QcCuttingController;

Route::middleware(['auth'])->group(function () {
    Route::prefix('production/cutting-jobs')
        ->name('production.cutting_jobs.')
        ->group(function () {

            Route::get('/', [CuttingJobController::class, 'index'])
                ->name('index');

            Route::get('/create', [CuttingJobController::class, 'create'])
                ->name('create');

            Route::post('/', [CuttingJobController::class, 'store'])
                ->name('store');

            Route::get('/{cuttingJob}', [CuttingJobController::class, 'show'])
                ->name('show');

            // >>> EDIT & UPDATE HASIL CUTTING <<<
            Route::get('/{cuttingJob}/edit', [CuttingJobController::class, 'edit'])
                ->name('edit');

            Route::put('/{cuttingJob}', [CuttingJobController::class, 'update'])
                ->name('update');
        });
});

Route::prefix('production/qc')
    ->middleware(['auth'])
    ->name('production.qc.')
    ->group(function () {

        // QC Cutting
        Route::prefix('cutting')
            ->name('cutting.')
            ->group(function () {

                // List QC Cutting (optional)
                Route::get('/', [QcCuttingController::class, 'index'])
                    ->name('index');

                // Edit QC untuk 1 cutting_job_bundle
                Route::get('/{bundle}/edit', [QcCuttingController::class, 'edit'])
                    ->name('edit');

                // Update QC
                Route::put('/{bundle}', [QcCuttingController::class, 'update'])
                    ->name('update');
            });
    });
