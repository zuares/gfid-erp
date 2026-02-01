<?php

use App\Http\Controllers\Marketplace\MarketplaceOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        Route::resource('orders', MarketplaceOrderController::class)
            ->only(['index', 'show', 'create', 'store']);
    });
