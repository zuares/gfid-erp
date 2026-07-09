<?php

use App\Http\Controllers\Owner\AccessControlController;
use App\Http\Controllers\Owner\DatabaseModeController;
use App\Http\Controllers\Owner\DatabaseSnapshotController;
use App\Http\Controllers\Owner\WorkLogController;
use App\Http\Controllers\Api\ItemController as ApiItemController;

Route::middleware(['auth'])
    ->get('/api/v1/items/suggest', [ApiItemController::class, 'suggest'])
    ->name('web_api.items.suggest');

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
require __DIR__ . '/web/settings.php';

// NOTE: Route produksi-lama (orders/issues/receipts/activities) dipindah ke
// routes/web/production-legacy.php agar konsisten dengan pola routing per-domain.


// OWNER WORK LOG
Route::middleware(['auth'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {

        Route::middleware('role:owner')->group(function () {
            Route::get('access-control', [AccessControlController::class, 'index'])->name('access-control.index');
            Route::put('access-control', [AccessControlController::class, 'update'])->name('access-control.update');
        });

        Route::post('database-mode', [DatabaseModeController::class, 'switch'])->name('database-mode.switch');

        // Snapshot & Rollback
        Route::get('snapshots', [DatabaseSnapshotController::class, 'index'])->name('snapshots.index');
        Route::post('snapshots', [DatabaseSnapshotController::class, 'store'])->name('snapshots.store');
        Route::patch('snapshots/{filename}', [DatabaseSnapshotController::class, 'restore'])->name('snapshots.restore');
        Route::delete('snapshots/{filename}', [DatabaseSnapshotController::class, 'destroy'])->name('snapshots.destroy');

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
use App\Http\Controllers\TikTokShopAuthController;

// Marketplace — halaman
Route::middleware(['auth', 'access:marketplace'])->group(function () {
    Route::get('/marketplace/toko',        [MarketplaceController::class, 'toko'])->name('marketplace.toko');
    Route::get('/marketplace/orders',      [MarketplaceController::class, 'orders'])->name('marketplace.orders');
    Route::get('/marketplace/fulfillment',                          [MarketplaceController::class, 'fulfillment'])->name('marketplace.fulfillment');
    Route::get('/marketplace/fulfillment/{fulfillment}/history',    [FulfillmentController::class, 'history'])->name('marketplace.fulfillment.history');
    Route::get('/marketplace/picking',     [MarketplaceController::class, 'picking'])->name('marketplace.picking');
    Route::get('/marketplace/sku-mapping', [MarketplaceController::class, 'skuMapping'])->name('marketplace.sku-mapping');
    Route::get('/marketplace/sync',        [MarketplaceController::class, 'sync'])->name('marketplace.sync');
    Route::get('/marketplace/settlement',  [MarketplaceController::class, 'settlement'])->name('marketplace.settlement');
    Route::get('/marketplace/profit',      [MarketplaceController::class, 'profit'])->name('marketplace.profit');
    Route::get('/marketplace/ads',         [MarketplaceController::class, 'ads'])->name('marketplace.ads');
    Route::get('/marketplace/analytics',  [MarketplaceController::class, 'analytics'])->name('marketplace.analytics');
    Route::get('/marketplace/issues',      [MarketplaceController::class, 'issueCenter'])->name('marketplace.issues');
});

Route::middleware(['auth', 'access:marketplace'])->group(function () {
    // Shopee OAuth
    Route::get('/marketplace/shopee/connect',  [ShopeeStoreAuthController::class, 'redirect'])->name('marketplace.shopee.connect');
    Route::get('/marketplace/shopee/callback', [ShopeeStoreAuthController::class, 'callback'])->name('marketplace.shopee.callback');

    // TikTok Shop OAuth
    Route::get('/marketplace/tiktok/connect',  [TikTokShopAuthController::class, 'redirect'])->name('marketplace.tiktok.connect');
    Route::get('/marketplace/tiktok/callback', [TikTokShopAuthController::class, 'callback'])->name('marketplace.tiktok.callback');
});

// Marketplace API
Route::middleware(['auth', 'access:marketplace'])->prefix('api/marketplace')->group(function () {
    Route::post('/bootstrap', [MarketplaceController::class, 'bootstrap'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    Route::get('/channels',                    [MarketplaceController::class, 'channels']);
    Route::get('/stores',                      [MarketplaceController::class, 'stores']);
    Route::get('/stores/{store}/shop-info',    [MarketplaceController::class, 'shopInfo']);
    Route::post('/stores/{store}/sync-orders', [MarketplaceController::class, 'syncOrders'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    // Logistics Endpoints
    Route::get('/stores/{store}/orders/{orderSn}/shipping-parameter', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'getShippingParameter']);
    Route::post('/stores/{store}/orders/{orderSn}/ship', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'arrangeShipment'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/stores/{store}/orders/{orderSn}/document', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'printDocument']);

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
    Route::post('/auto-map-by-code', [MarketplaceController::class, 'autoMapByCode'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/stores-summary',  [MarketplaceController::class, 'storesSummary']);
    Route::get('/items/search',    [MarketplaceController::class, 'searchInternalItems']);
    Route::get('/items/by-code',   [MarketplaceController::class, 'itemByCode']);
    Route::patch('/order-items/{item}/fill-sku',  [MarketplaceController::class, 'fillItemSku'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::patch('/order-items/{item}/map-sku',   [MarketplaceController::class, 'mapItemSku'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::patch('/order-items/{item}/fill-hpp',  [MarketplaceController::class, 'fillItemHpp'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/order-items/{item}/recalc-profit', [MarketplaceController::class, 'recalcItemProfit'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/order-items/bulk-map',              [MarketplaceController::class, 'bulkMapSku'])
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
Route::middleware(['auth', 'access:marketplace'])->prefix('api/fulfillments')->group(function () {
    Route::get('/',                                    [FulfillmentController::class, 'index']);
    Route::get('/batch-stats',                         [FulfillmentController::class, 'batchStats']);
    Route::post('/create-draft',                       [FulfillmentController::class, 'createDraft'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/scan',                               [FulfillmentController::class, 'scanOrder'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/remap-all',                          [FulfillmentController::class, 'remapAll'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    // Picking workflow
    Route::get('/picking-queue',                              [FulfillmentController::class, 'pickingQueue']);
    Route::post('/start-picking',                             [FulfillmentController::class, 'startPicking'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/{fulfillment}',                              [FulfillmentController::class, 'show']);
    Route::patch('/{fulfillment}/lines/{line}',               [FulfillmentController::class, 'updateLine'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/confirm',                     [FulfillmentController::class, 'confirm'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/refresh-stock',               [FulfillmentController::class, 'refreshStock'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/mark-packed',                 [FulfillmentController::class, 'markPacked'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/unpack',                      [FulfillmentController::class, 'unpack'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/lines/{line}/toggle-picked',  [FulfillmentController::class, 'toggleLinePicked'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/lines/{line}/flag-problem',   [FulfillmentController::class, 'flagLineProblem'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/lines/{line}/resolve-problem',[FulfillmentController::class, 'resolveLineProblem'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/complete-picking',            [FulfillmentController::class, 'completePicking'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/lines/{line}/substitute',     [FulfillmentController::class, 'substituteItem'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/lines/{line}/split',          [FulfillmentController::class, 'splitLine'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/lines/{line}/restore-split',  [FulfillmentController::class, 'restoreSplitLine'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/batch-confirm',               [FulfillmentController::class, 'batchConfirm'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/pack',                        [FulfillmentController::class, 'packOrder'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/{fulfillment}/confirm-packed',              [FulfillmentController::class, 'confirmPacked'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/{fulfillment}/audit-logs',                   [FulfillmentController::class, 'auditLogs']);
});

// Dev-only API (non-production)
if (! app()->isProduction()) {
    $noCsrf = [\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class];
    Route::middleware(['auth', 'access:marketplace'])->group(function () use ($noCsrf) {
        Route::post('/api/dev/fresh-orders',       [MarketplaceController::class, 'devFreshOrders'])->withoutMiddleware($noCsrf);
        Route::post('/api/dev/seed-orders',        [MarketplaceController::class, 'devSeedOrders'])->withoutMiddleware($noCsrf);
        Route::post('/api/dev/reset-fulfillments', [MarketplaceController::class, 'devResetFulfillments'])->withoutMiddleware($noCsrf);
        Route::get('/api/dev/next-order',          [MarketplaceController::class, 'devNextOrder']);
        Route::get('/api/dev/stats',               [MarketplaceController::class, 'devStats']);
    });
}

// SKU Mapping API
Route::middleware(['auth', 'access:marketplace'])->prefix('api/sku-mappings')->group(function () {
    Route::get('/',                  [SkuMappingController::class, 'index']);
    Route::post('/',                 [SkuMappingController::class, 'store'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::delete('/{skuMapping}',   [SkuMappingController::class, 'destroy'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/search-items',      [SkuMappingController::class, 'searchItems']);
    Route::get('/unmapped-skus',     [SkuMappingController::class, 'unmappedSkus']);
    Route::get('/categories',        [SkuMappingController::class, 'categories']);
    Route::post('/quick-create-category', [SkuMappingController::class, 'quickCreateCategory'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/quick-create-item', [SkuMappingController::class, 'quickCreateItem'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
});
