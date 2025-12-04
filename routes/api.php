<?php

// routes/api.php
use App\Http\Controllers\Api\CustomerController as ApiCustomerController;
use App\Http\Controllers\Api\ItemController;

Route::prefix('v1')->group(function () {
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/suggest', [ItemController::class, 'suggest']);
    Route::get('/items/{item}', [ItemController::class, 'show']);
    Route::get('/items/by-barcode', [ItemController::class, 'findByBarcode'])
        ->name('items.by_barcode');

});

Route::prefix('api')
    ->name('api.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/customers/suggest', [ApiCustomerController::class, 'suggest'])
            ->name('customers.suggest');

        // ... route api lain (stock, items, dll)
    });
