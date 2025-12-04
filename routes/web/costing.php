<?php
use App\Http\Controllers\Costing\HppController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('costing/hpp', [HppController::class, 'index'])
        ->name('costing.hpp.index');

    Route::post('costing/hpp/generate', [HppController::class, 'generate'])
        ->name('costing.hpp.generate');

    Route::post('costing/hpp/{snapshot}/set-active', [HppController::class, 'setActive'])
        ->name('costing.hpp.set_active');
});
