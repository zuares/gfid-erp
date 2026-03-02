<?php

use App\Http\Controllers\Api\MarketplaceShipmentController;
use App\Http\Controllers\Imports\MarketplaceImportController;
use App\Http\Controllers\Imports\MarketplaceIncomeImportController;
use App\Http\Controllers\Marketplace\MpAdsImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('imports')
    ->name('imports.')
    ->group(function () {

        /*
    |--------------------------------------------------------------------------
    | Marketplace Shipments (Import Wizard)
    |--------------------------------------------------------------------------
    | URL: /imports/marketplace/...
    | Name: imports.marketplace.*
     */
        Route::prefix('marketplace')->name('marketplace.')->group(function () {

            // API JSON (datatable) — keep di atas route parameter
            Route::get('data', [MarketplaceShipmentController::class, 'index'])->name('data');

            // pages
            Route::get('/', [MarketplaceImportController::class, 'index'])->name('index');
            Route::get('create', [MarketplaceImportController::class, 'create'])->name('create');
            Route::get('export', [MarketplaceImportController::class, 'export'])->name('export');

            // draft/session helpers
            Route::get('draft', [MarketplaceImportController::class, 'draft'])->name('draft');

            // actions
            Route::post('preview', [MarketplaceImportController::class, 'preview'])->name('preview');
            Route::post('commit', [MarketplaceImportController::class, 'commit'])->name('commit');
            Route::post('cancel', [MarketplaceImportController::class, 'cancel'])->name('cancel');

            // detail import/draft (opsional)
            Route::get('{import}', [MarketplaceImportController::class, 'show'])
                ->whereNumber('import')
                ->name('show');
        });

        /*
    |--------------------------------------------------------------------------
    | Marketplace Income (Import Wizard)
    |--------------------------------------------------------------------------
    | URL: /imports/marketplace-income/...
    | Name: imports.marketplace_income.*
     */
        Route::prefix('marketplace-income')->name('marketplace_income.')->group(function () {

            // pages
            Route::get('/', [MarketplaceIncomeImportController::class, 'index'])->name('index');
            Route::get('create', [MarketplaceIncomeImportController::class, 'create'])->name('create');

            // draft/session helpers
            Route::get('draft', [MarketplaceIncomeImportController::class, 'draft'])->name('draft');

            // actions
            Route::post('preview', [MarketplaceIncomeImportController::class, 'preview'])->name('preview');
            Route::post('commit', [MarketplaceIncomeImportController::class, 'commit'])->name('commit');
            Route::post('cancel', [MarketplaceIncomeImportController::class, 'cancel'])->name('cancel');

            // show batch & show order(items)
            Route::get('batches/{batch}', [MarketplaceIncomeImportController::class, 'showBatch'])
                ->whereNumber('batch')
                ->name('show');

            Route::get('orders/{income}', [MarketplaceIncomeImportController::class, 'showOrder'])
                ->whereNumber('income')
                ->name('order.show');

            Route::post('batches/{batch}/apply', [MarketplaceIncomeImportController::class, 'apply'])
                ->whereNumber('batch')
                ->name('apply');
        });

        /*
    |--------------------------------------------------------------------------
    | Marketplace Ads (Shopee Ads Import)
    |--------------------------------------------------------------------------
    | URL: /imports/marketplace-ads/...
    | Name: imports.marketplace_ads.*
    |
    | Note: Controller ada di namespace Marketplace karena ini domain Ads.
     */
        /*
    |--------------------------------------------------------------------------
    | Marketplace Ads (Shopee Ads Import)
    |--------------------------------------------------------------------------
    | URL: /imports/marketplace-ads/...
    | Name: imports.marketplace_ads.*
     */
        Route::prefix('marketplace-ads')->name('marketplace_ads.')->group(function () {
            // pages
            Route::get('/', [MpAdsImportController::class, 'index'])->name('index');
            Route::get('create', [MpAdsImportController::class, 'create'])->name('create');

            // guard GET preview/commit
            Route::get('preview', fn() => redirect()->route('imports.marketplace_ads.create'))->name('preview.get');
            Route::get('commit', fn() => redirect()->route('imports.marketplace_ads.create'))->name('commit.get');

            // actions
            Route::post('preview', [MpAdsImportController::class, 'preview'])->name('preview');
            Route::post('commit', [MpAdsImportController::class, 'commit'])->name('commit');

            // report API (optional)
            Route::get('data', [MpAdsImportController::class, 'data'])->name('data');
        });

    });
