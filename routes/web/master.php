<?php

use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\EmployeeController;
use App\Http\Controllers\Master\ItemBomController;
use App\Http\Controllers\Master\ItemCategoryController;
use App\Http\Controllers\Master\ItemController;
use App\Http\Controllers\Master\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'access:master'])->group(function () {

    Route::prefix('master')->name('master.')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
         */
        Route::resource('items', ItemController::class);

        // ✅ Bulk update (kategori / tipe / HPP) untuk beberapa item sekaligus
        Route::post('items/bulk-update', [ItemController::class, 'bulkUpdate'])
            ->name('items.bulk_update');

        /*
        |--------------------------------------------------------------------------
        | KATEGORI ITEM (CRUD halaman terpisah)
        |--------------------------------------------------------------------------
         */
        Route::resource('item-categories', ItemCategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['item-categories' => 'item_category'])
            ->names('item_categories');

        // HPP sementara master item
        Route::get('items/{item}/hpp-temp', [ItemController::class, 'editHppTemp'])
            ->name('items.hpp_temp.edit');
        Route::post('items/{item}/hpp-temp', [ItemController::class, 'storeHppTemp'])
            ->name('items.hpp_temp.store');

        // metadata master item (akun2, kategori, dll)
        Route::get('items/meta', [ItemController::class, 'meta'])
            ->name('items.meta');

        // ✅ suggest items buat mapping (ringan)
        Route::get('items/suggest', [SupplierController::class, 'suggestItems'])
            ->name('items.suggest');

        /*
        |--------------------------------------------------------------------------
        | CUSTOMERS
        |--------------------------------------------------------------------------
         */
        Route::resource('customers', CustomerController::class)->except(['show']);

        /*
        |--------------------------------------------------------------------------
        | EMPLOYEES (owner only)
        |--------------------------------------------------------------------------
         */
        Route::resource('employees', EmployeeController::class)
            ->except(['show'])
            ->middleware('role:owner');

        /*
        |--------------------------------------------------------------------------
        | SUPPLIERS + MAPPING
        |--------------------------------------------------------------------------
         */
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

        /*
        |--------------------------------------------------------------------------
        | BOM SKU (Item BOM)
        |--------------------------------------------------------------------------
         */
        Route::get('item-boms', [ItemBomController::class, 'index'])
            ->name('item_boms.index');

        Route::get('item-boms/create', [ItemBomController::class, 'create'])
            ->name('item_boms.create');

        Route::post('item-boms', [ItemBomController::class, 'store'])
            ->name('item_boms.store');

        Route::get('item-boms/{bom}/edit', [ItemBomController::class, 'edit'])
            ->name('item_boms.edit');

        Route::put('item-boms/{bom}', [ItemBomController::class, 'update'])
            ->name('item_boms.update');

        Route::delete('item-boms/{bom}', [ItemBomController::class, 'destroy'])
            ->name('item_boms.destroy');

        // AJAX search select2
        Route::get('item-boms/ajax/items', [ItemBomController::class, 'ajaxItems'])
            ->name('item_boms.ajax_items');

        // CSV
        Route::get('item-boms/import', [ItemBomController::class, 'importForm'])
            ->name('item_boms.import_form');

        Route::post('item-boms/import', [ItemBomController::class, 'import'])
            ->name('item_boms.import');

        Route::get('item-boms/template.csv', [ItemBomController::class, 'downloadTemplate'])
            ->name('item_boms.download_template');

        // Duplicate
        Route::get('item-boms/duplicate', [ItemBomController::class, 'duplicateForm'])
            ->name('item_boms.duplicate_form');

        Route::post('item-boms/duplicate', [ItemBomController::class, 'duplicate'])
            ->name('item_boms.duplicate');

    });

});
