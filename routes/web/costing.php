<?php

use App\Http\Controllers\Costing\HppController;
use App\Http\Controllers\Costing\ProductionCostPeriodController;

/*
|--------------------------------------------------------------------------
| HPP Finished Goods
| Hanya bisa diakses oleh owner
|--------------------------------------------------------------------------
 */
Route::middleware(['web', 'auth', 'access:costing'])->group(function () {

    Route::get('costing/hpp', [HppController::class, 'index'])
        ->name('costing.hpp.index');

    Route::post('costing/hpp/generate', [HppController::class, 'generate'])
        ->name('costing.hpp.generate');

    Route::post('costing/hpp/{snapshot}/set-active', [HppController::class, 'setActive'])
        ->name('costing.hpp.set_active');
});

/*
|--------------------------------------------------------------------------
| Production Cost Periods
| Hanya owner yang boleh atur periode costing & trigger generate HPP
|--------------------------------------------------------------------------
 */
Route::middleware(['web', 'auth', 'access:costing'])->group(function () {

    Route::prefix('costing')->name('costing.')->group(function () {

        Route::resource('production-cost-periods', ProductionCostPeriodController::class)
            ->parameters([
                'production-cost-periods' => 'period',
            ])
            ->names('production_cost_periods')
            ->only(['index', 'show', 'edit', 'update']);

        // Generate HPP dari payroll untuk 1 periode
        Route::post('production-cost-periods/{period}/generate',
            [ProductionCostPeriodController::class, 'generate']
        )->name('production_cost_periods.generate');
    });
});
