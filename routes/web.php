<?php

use App\Http\Controllers\Owner\WorkLogController;

// Grouping per domain
require __DIR__ . '/web/auth.php';
require __DIR__ . '/web/dashboard.php';
require __DIR__ . '/web/purchasing.php';
require __DIR__ . '/web/inventory.php';
require __DIR__ . '/web/production.php';
require __DIR__ . '/web/production-legacy.php'; // @deprecated — Orders/Issues/Receipts/Activities (lihat file)
require __DIR__ . '/web/payroll.php';
require __DIR__ . '/web/costing.php';
require __DIR__ . '/web/marketplace.php';
require __DIR__ . '/web/master.php';
require __DIR__ . '/web/sales.php';
require __DIR__ . '/web/accounting.php';
require __DIR__ . '/web/shipments.php';
require __DIR__ . '/web/imports.php';

// NOTE: Route produksi-lama (orders/issues/receipts/activities) dipindah ke
// routes/web/production-legacy.php agar konsisten dengan pola routing per-domain.


// OWNER WORK LOG
Route::middleware(['auth'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {

        Route::post('work-logs/{workLog}/done', [WorkLogController::class, 'markDone'])->name('work-logs.mark-done');
        Route::post('work-logs/{workLog}/reopen', [WorkLogController::class, 'reopen'])->name('work-logs.reopen');

        Route::resource('work-logs', WorkLogController::class)->parameters([
            'work-logs' => 'workLog',
        ]);
    });

use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\Owner\FulfillmentController;
use App\Http\Controllers\Owner\SkuMappingController;
use App\Http\Controllers\ShopeeStoreAuthController;

// Marketplace — halaman
Route::get('/marketplace/toko',        [MarketplaceController::class, 'toko'])->name('marketplace.toko');
Route::get('/marketplace/orders',      [MarketplaceController::class, 'orders'])->name('marketplace.orders');
Route::get('/marketplace/fulfillment', [MarketplaceController::class, 'fulfillment'])->name('marketplace.fulfillment');
Route::get('/marketplace/sku-mapping', [MarketplaceController::class, 'skuMapping'])->name('marketplace.sku-mapping');
Route::get('/marketplace/sync',        [MarketplaceController::class, 'sync'])->name('marketplace.sync');
Route::get('/marketplace/settlement',  [MarketplaceController::class, 'settlement'])->name('marketplace.settlement');
Route::get('/marketplace/profit',      [MarketplaceController::class, 'profit'])->name('marketplace.profit');
Route::get('/marketplace/ads',         [MarketplaceController::class, 'ads'])->name('marketplace.ads');
Route::get('/marketplace/issues',      [MarketplaceController::class, 'issueCenter'])->name('marketplace.issues');

// Shopee OAuth
Route::get('/marketplace/shopee/connect',  [ShopeeStoreAuthController::class, 'redirect'])->name('marketplace.shopee.connect');
Route::get('/marketplace/shopee/callback', [ShopeeStoreAuthController::class, 'callback'])->name('marketplace.shopee.callback');

// Marketplace API
Route::prefix('api/marketplace')->group(function () {
    Route::post('/bootstrap', [MarketplaceController::class, 'bootstrap'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    Route::get('/channels',                    [MarketplaceController::class, 'channels']);
    Route::get('/stores',                      [MarketplaceController::class, 'stores']);
    Route::get('/stores/{store}/shop-info',    [MarketplaceController::class, 'shopInfo']);
    Route::post('/stores/{store}/sync-orders', [MarketplaceController::class, 'syncOrders'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/local-orders',                [MarketplaceController::class, 'localOrders']);
    Route::get('/sync-logs',                         [MarketplaceController::class, 'syncLogs']);
    Route::get('/settlements',                       [MarketplaceController::class, 'settlements']);
    Route::post('/stores/{store}/sync-settlements',  [MarketplaceController::class, 'syncSettlements'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/order-profits',                     [MarketplaceController::class, 'orderProfits']);
    Route::get('/ads-analytics',                     [MarketplaceController::class, 'adsAnalytics']);
    Route::post('/stores/{store}/sync-ad-campaigns', [MarketplaceController::class, 'syncAdCampaigns'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/stores/{store}/debug-ad-api', [MarketplaceController::class, 'debugAdApi']); // TODO: hapus setelah debug
    Route::get('/issue-items',    [MarketplaceController::class, 'issueItems']);
    Route::get('/issue-summary',  [MarketplaceController::class, 'issueSummary']);
    Route::post('/remap-items',   [MarketplaceController::class, 'remapOrderItems'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/stores-summary',  [MarketplaceController::class, 'storesSummary']);
    Route::get('/items/search',    [MarketplaceController::class, 'searchInternalItems']);
    Route::patch('/order-items/{item}/fill-sku',  [MarketplaceController::class, 'fillItemSku'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::patch('/order-items/{item}/map-sku',   [MarketplaceController::class, 'mapItemSku'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::patch('/order-items/{item}/fill-hpp',  [MarketplaceController::class, 'fillItemHpp'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/order-items/{item}/recalc-profit', [MarketplaceController::class, 'recalcItemProfit'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::patch('/ad-campaigns/{campaign}/break-even', [MarketplaceController::class, 'updateCampaignBreakEven'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::patch('/settlements/{settlement}/ad-cost', [MarketplaceController::class, 'updateSettlementAdCost'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/warehouses',                  [MarketplaceController::class, 'warehouses']);
    Route::patch('/stores/{store}',            [MarketplaceController::class, 'updateStore'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
});

// Fulfillment API
Route::prefix('api/fulfillments')->group(function () {
    Route::get('/',                                    [FulfillmentController::class, 'index']);
    Route::post('/create-draft',                       [FulfillmentController::class, 'createDraft'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/remap-all',                          [FulfillmentController::class, 'remapAll'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/{fulfillment}',                       [FulfillmentController::class, 'show']);
    Route::patch('/{fulfillment}/lines/{line}',        [FulfillmentController::class, 'updateLine'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/confirm',              [FulfillmentController::class, 'confirm'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/refresh-stock',        [FulfillmentController::class, 'refreshStock'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
});

// SKU Mapping API
Route::prefix('api/sku-mappings')->group(function () {
    Route::get('/',                  [SkuMappingController::class, 'index']);
    Route::post('/',                 [SkuMappingController::class, 'store'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::delete('/{skuMapping}',   [SkuMappingController::class, 'destroy'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/search-items',      [SkuMappingController::class, 'searchItems']);
    Route::get('/unmapped-skus',     [SkuMappingController::class, 'unmappedSkus']);
    Route::post('/quick-create-item', [SkuMappingController::class, 'quickCreateItem'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
});
