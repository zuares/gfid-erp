<?php

use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\ItemController;
use App\Http\Controllers\Master\SupplierController;

Route::middleware(['web', 'auth'])
    ->group(function () {

        Route::prefix('master')->name('master.')->group(function () {

            // ✅ suggest items buat mapping (ringan)
            Route::get('items/suggest', [SupplierController::class, 'suggestItems'])
                ->name('items.suggest');

            // =========================
            // MASTER ITEMS
            // =========================
            Route::resource('items', ItemController::class);

            // HPP sementara master item
            Route::get('items/{item}/hpp-temp', [ItemController::class, 'editHppTemp'])
                ->name('items.hpp_temp.edit');

            Route::post('items/{item}/hpp-temp', [ItemController::class, 'storeHppTemp'])
                ->name('items.hpp_temp.store');

            // =========================
            // MASTER CUSTOMERS
            // =========================
            Route::resource('customers', CustomerController::class)
                ->except(['show']);

            // =========================
            // MASTER SUPPLIERS ✅
            // =========================
            Route::resource('suppliers', SupplierController::class);

            // ✅ Mapping endpoints (AJAX)
            Route::get('suppliers/{supplier}/items', [SupplierController::class, 'itemsJson'])
                ->name('suppliers.items.json');

            Route::post('suppliers/{supplier}/items', [SupplierController::class, 'attachItem'])
                ->name('suppliers.items.attach');

            Route::put('suppliers/{supplier}/items/{item}', [SupplierController::class, 'updateItem'])
                ->name('suppliers.items.update');

            Route::delete('suppliers/{supplier}/items/{item}', [SupplierController::class, 'detachItem'])
                ->name('suppliers.items.detach');

            Route::post('suppliers/{supplier}/items/bulk', [SupplierController::class, 'bulkAttach'])
                ->name('suppliers.items.bulk');

        });

    });
