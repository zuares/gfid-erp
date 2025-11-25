
<?php

use App\Http\Controllers\Production\CuttingJobController;
use App\Http\Controllers\Production\QcController;

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

Route::middleware(['auth'])->group(function () {

    Route::prefix('production/qc')
        ->name('production.qc.')
        ->group(function () {

            // QC Cutting
            Route::get('/cutting/{cuttingJob}/edit', [QcController::class, 'editCutting'])
                ->name('cutting.edit');

            Route::put('/cutting/{cuttingJob}', [QcController::class, 'updateCutting'])
                ->name('cutting.update');

            // nanti bisa tambah:
            // Route::get('/sewing/{sewingJob}/edit', [...])->name('sewing.edit');
            // Route::put('/sewing/{sewingJob}', [...])->name('sewing.update');
        });
});
