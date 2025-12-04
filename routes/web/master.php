<?php

use App\Http\Controllers\Master\CustomerController;

Route::middleware(['web', 'auth'])
    ->group(function () {

        Route::resource('items', \App\Http\Controllers\Master\ItemController::class);

        Route::resource('customers', CustomerController::class)
            ->except(['show']); // kalau belum perlu

    });
