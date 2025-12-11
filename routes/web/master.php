<?php
use App\Http\Controllers\Master\ItemController;

Route::middleware(['web', 'auth'])
    ->group(function () {

        Route::prefix('master')->name('master.')->group(function () {

            Route::resource('items', ItemController::class);

// HPP sementara master item
            Route::get('items/{item}/hpp-temp', [ItemController::class, 'editHppTemp'])
                ->name('items.hpp_temp.edit');

            Route::post('items/{item}/hpp-temp', [ItemController::class, 'storeHppTemp'])
                ->name('items.hpp_temp.store');

            Route::resource('customers', MasterCustomerController::class)
                ->except(['show']);

        });

    });
