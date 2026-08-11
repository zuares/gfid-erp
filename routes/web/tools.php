<?php

use App\Http\Controllers\Tools\PricingCalculatorController;

/*
|--------------------------------------------------------------------------
| Tools
| Kumpulan alat bantu owner (stateless). Saat ini: Pricing & ROAS Calculator.
|--------------------------------------------------------------------------
 */
Route::middleware(['web', 'auth', 'role:owner'])
    ->prefix('tools')
    ->name('tools.')
    ->group(function () {

        Route::get('pricing-calculator', [PricingCalculatorController::class, 'index'])
            ->name('pricing-calculator');
    });
