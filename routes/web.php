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
            Route::post('access-control/reset-password', [AccessControlController::class, 'resetPassword'])->name('access-control.reset-password');
            Route::put('access-control', [AccessControlController::class, 'update'])->name('access-control.update');
            
            // Activity Logs
            Route::get('activity-logs', [App\Http\Controllers\Owner\ActivityLogController::class, 'index'])->name('activity-logs.index');
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
    Route::get('/marketplace/webhook-tests', [MarketplaceController::class, 'webhookTests'])->name('marketplace.webhook-tests');
    Route::get('/marketplace/chat',        [\App\Http\Controllers\MarketplaceChatController::class, 'page'])->name('marketplace.chat');
    Route::get('/marketplace/products',    [\App\Http\Controllers\MarketplaceProductController::class, 'page'])->name('marketplace.products');
    Route::get('/marketplace/boost',       [\App\Http\Controllers\MarketplaceBoostController::class, 'page'])->name('marketplace.boost');
    Route::get('/marketplace/fulfillment',                          [MarketplaceController::class, 'fulfillment'])->name('marketplace.fulfillment');
    Route::get('/marketplace/fulfillment/{fulfillment}/process',    [MarketplaceController::class, 'fulfillmentProcess'])->name('marketplace.fulfillment.process');
    Route::get('/marketplace/fulfillment/{fulfillment}/history',    [FulfillmentController::class, 'history'])->name('marketplace.fulfillment.history');
    Route::get('/marketplace/picking',     [MarketplaceController::class, 'picking'])->name('marketplace.picking');
    Route::get('/marketplace/sku-mapping', [MarketplaceController::class, 'skuMapping'])->name('marketplace.sku-mapping');
    Route::get('/marketplace/sync',        [MarketplaceController::class, 'sync'])->name('marketplace.sync');
    Route::get('/marketplace/settlement',  [MarketplaceController::class, 'settlement'])->name('marketplace.settlement');
    Route::get('/marketplace/profit',      [MarketplaceController::class, 'profit'])->name('marketplace.profit');
    Route::get('/marketplace/ads',         [MarketplaceController::class, 'ads'])->name('marketplace.ads');
    Route::get('/marketplace/cache-monitor', [MarketplaceController::class, 'cacheMonitor'])->name('marketplace.cache-monitor');
    Route::post('/marketplace/cache-monitor/run', [MarketplaceController::class, 'runCacheCleanup'])->name('marketplace.cache-monitor.run')
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    Route::get('/marketplace/settings',    [MarketplaceController::class, 'settings'])->name('marketplace.settings');
    Route::post('/marketplace/settings',   [MarketplaceController::class, 'updateSettings'])->name('marketplace.settings.update');
    Route::get('marketplace/settings/sample-greeting', [MarketplaceController::class, 'printSampleGreetingCard'])->name('marketplace.settings.sample_greeting');
    Route::post('/marketplace/settings/preview-pdf', [MarketplaceController::class, 'previewSettingsPdf'])->name('marketplace.settings.previewPdf');
    Route::post('/marketplace/settings/delete-template', [MarketplaceController::class, 'deleteTemplate'])->name('marketplace.settings.delete_template');
    Route::get('/marketplace/analytics',  [MarketplaceController::class, 'analytics'])->name('marketplace.analytics');
    Route::get('/marketplace/issues',      [MarketplaceController::class, 'issueCenter'])->name('marketplace.issues');
    Route::get('/marketplace/returns',     [\App\Http\Controllers\MarketplaceReturnController::class, 'index'])->name('marketplace.returns');
    Route::get('/marketplace/kilat',       [\App\Http\Controllers\MarketplaceBookingController::class, 'index'])->name('marketplace.kilat');
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
    
    Route::post('/stores/{store}/sync-historical', [MarketplaceController::class, 'syncHistorical']);
    Route::post('/stores/{store}/force-sync-background', [MarketplaceController::class, 'forceSyncBackground']);

    // Logistics Endpoints
    Route::get('/stores/{store}/orders/{orderSn}/shipping-parameter', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'getShippingParameter']);
    Route::get('/stores/{store}/orders/{orderSn}/booking-detail', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'getBookingDetail']);
    Route::get('/stores/{store}/orders/{orderSn}/raw-detail', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'getOrderDetailRaw']);
    Route::get('/stores/{store}/orders/{orderSn}/tracking', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'getTrackingInfo']);
    Route::get('/stores/{store}/packages/{packageNumber}/raw-detail', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'getPackageDetailRaw']);
    Route::get('/stores/{store}/return-list/raw-detail', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'getReturnListRaw']);
    Route::get('/stores/{store}/booking-list', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'getBookingList']);
    Route::get('/stores/{store}/order-list', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'getOrderList']);
    Route::post('/stores/{store}/sync-bookings', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'syncBookings'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/stores/{store}/orders/{orderSn}/ship', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'arrangeShipment'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('marketplace.logistics.ship');
    Route::get('/stores/{store}/orders/{orderSn}/document', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'printDocument']);
    Route::get('/stores/{store}/orders/{orderSn}/sync-awb', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'syncAwb']);
    Route::post('/documents/bulk-print', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'createBulkPrintJob'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/documents/bulk-print/{uuid}', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'downloadBulkPrintJob']);
    Route::get('/stores/{store}/documents/bulk-greetings', [\App\Http\Controllers\MarketplaceLogisticsController::class, 'printBulkGreetings']);

    Route::get('/local-orders',                [MarketplaceController::class, 'localOrders']);

    // Produk Marketplace
    Route::get('/products',                        [\App\Http\Controllers\MarketplaceProductController::class, 'index']);
    Route::post('/products/sync',                  [\App\Http\Controllers\MarketplaceProductController::class, 'sync'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/products/auto-map',              [\App\Http\Controllers\MarketplaceProductController::class, 'autoMap'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/products/{product}/stock',       [\App\Http\Controllers\MarketplaceProductController::class, 'updateStock'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/products/{product}/price',       [\App\Http\Controllers\MarketplaceProductController::class, 'updatePrice'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/products/{product}/unlist',      [\App\Http\Controllers\MarketplaceProductController::class, 'toggleUnlist'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/products/{product}/sku',         [\App\Http\Controllers\MarketplaceProductController::class, 'updateSku'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/products/{product}/model-sku',   [\App\Http\Controllers\MarketplaceProductController::class, 'updateModelSku'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/products/{product}/history',      [\App\Http\Controllers\MarketplaceProductController::class, 'history']);

    // Naikkan Produk (boost)
    $noCsrf = [\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class];
    Route::get('/boost/status',            [\App\Http\Controllers\MarketplaceBoostController::class, 'status']);
    Route::post('/boost/now',              [\App\Http\Controllers\MarketplaceBoostController::class, 'boostNow'])->withoutMiddleware($noCsrf);
    Route::get('/boost/logs',              [\App\Http\Controllers\MarketplaceBoostController::class, 'logs']);
    // Jadwal jam-tetap
    Route::get('/boost/schedules',         [\App\Http\Controllers\MarketplaceBoostController::class, 'schedules']);
    Route::post('/boost/schedules',        [\App\Http\Controllers\MarketplaceBoostController::class, 'storeSchedule'])->withoutMiddleware($noCsrf);
    Route::post('/boost/schedules/{schedule}/toggle', [\App\Http\Controllers\MarketplaceBoostController::class, 'toggleSchedule'])->withoutMiddleware($noCsrf);
    Route::delete('/boost/schedules/{schedule}',      [\App\Http\Controllers\MarketplaceBoostController::class, 'destroySchedule'])->withoutMiddleware($noCsrf);
    // Antrian rotasi (pool)
    Route::get('/boost/pool',              [\App\Http\Controllers\MarketplaceBoostController::class, 'pool']);
    Route::post('/boost/pool',             [\App\Http\Controllers\MarketplaceBoostController::class, 'storePool'])->withoutMiddleware($noCsrf);
    Route::post('/boost/pool/{poolItem}/toggle', [\App\Http\Controllers\MarketplaceBoostController::class, 'togglePool'])->withoutMiddleware($noCsrf);
    Route::delete('/boost/pool/{poolItem}',      [\App\Http\Controllers\MarketplaceBoostController::class, 'destroyPool'])->withoutMiddleware($noCsrf);

    // Chat Marketplace
    Route::get('/chat/unread-count',                         [\App\Http\Controllers\MarketplaceChatController::class, 'unreadCount']);
    Route::get('/chat/test-shopee-chat',                     [\App\Http\Controllers\MarketplaceChatController::class, 'diagnoseChat']);
    Route::get('/chat/conversations',                        [\App\Http\Controllers\MarketplaceChatController::class, 'conversations']);
    Route::get('/chat/conversations/{conversation}/messages', [\App\Http\Controllers\MarketplaceChatController::class, 'messages']);
    Route::post('/chat/conversations/{conversation}/send',    [\App\Http\Controllers\MarketplaceChatController::class, 'send'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/chat/conversations/{conversation}/read',    [\App\Http\Controllers\MarketplaceChatController::class, 'markRead'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/chat/start-from-order',                     [\App\Http\Controllers\MarketplaceChatController::class, 'startFromOrder'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/sync-logs',                         [MarketplaceController::class, 'syncLogs']);
    Route::get('/settlements',                       [MarketplaceController::class, 'settlements']);
    Route::post('/stores/{store}/sync-settlements',  [MarketplaceController::class, 'syncSettlements'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/stores/{store}/sync-settlements-background', [MarketplaceController::class, 'syncSettlementsBackground'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/order-profits',                     [MarketplaceController::class, 'orderProfits']);
    
    // Returns Module
    Route::get('/returns/live', [\App\Http\Controllers\MarketplaceReturnController::class, 'live']);
    Route::get('/returns/stored', [\App\Http\Controllers\MarketplaceReturnController::class, 'storedRrc']);
    Route::post('/returns/sync-all', [\App\Http\Controllers\MarketplaceReturnController::class, 'syncAllReturns'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/stores/{store}/returns/list', [\App\Http\Controllers\MarketplaceReturnController::class, 'getReturnList']);
    Route::post('/stores/{store}/returns/sync', [\App\Http\Controllers\MarketplaceReturnController::class, 'syncReturns'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/stores/{store}/returns/{returnSn}/detail', [\App\Http\Controllers\MarketplaceReturnController::class, 'getReturnDetail']);
    Route::get('/stores/{store}/returns/{returnSn}/tracking', [\App\Http\Controllers\MarketplaceReturnController::class, 'getTracking']);
    Route::post('/stores/{store}/returns/{returnSn}/confirm', [\App\Http\Controllers\MarketplaceReturnController::class, 'confirmAndRestock'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    // Pesanan Kilat (Booking) Module
    Route::get('/bookings/stored', [\App\Http\Controllers\MarketplaceBookingController::class, 'stored']);
    Route::post('/bookings/sync-all', [\App\Http\Controllers\MarketplaceBookingController::class, 'syncAll'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/stores/{store}/bookings/{bookingSn}/detail', [\App\Http\Controllers\MarketplaceBookingController::class, 'detail']);
    Route::get('/stores/{store}/bookings/{bookingSn}/tracking', [\App\Http\Controllers\MarketplaceBookingController::class, 'tracking']);
    Route::get('/stores/{store}/bookings/{bookingSn}/shipping-parameter', [\App\Http\Controllers\MarketplaceBookingController::class, 'shippingParameter']);
    Route::post('/stores/{store}/bookings/{bookingSn}/ship', [\App\Http\Controllers\MarketplaceBookingController::class, 'ship'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/stores/{store}/bookings/{bookingSn}/document', [\App\Http\Controllers\MarketplaceBookingController::class, 'printDocument']);
    Route::post('/documents/booking-bulk-print', [\App\Http\Controllers\MarketplaceBookingController::class, 'createBulkPrintJob'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/documents/booking-bulk-print/{uuid}', [\App\Http\Controllers\MarketplaceBookingController::class, 'downloadBulkPrintJob']);
    Route::get('/ads-analytics',                     [MarketplaceController::class, 'adsAnalytics']);
    Route::get('/stores/{store}/ads-balance',           [MarketplaceController::class, 'adsBalance']);
    Route::get('/stores/{store}/ads-shop-performance',  [MarketplaceController::class, 'adsShopPerformance']);
    Route::get('/ads-balance-all',                      [MarketplaceController::class, 'adsBalanceAll']);
    Route::get('/ads-balance-history',                  [MarketplaceController::class, 'adsBalanceHistory']);
    Route::get('/ads-daily',                            [MarketplaceController::class, 'adsDaily']);
    Route::post('/ads-daily/sync',                      [MarketplaceController::class, 'syncAdsDaily'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
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
    Route::delete('/stores/{store}',           [MarketplaceController::class, 'deleteStore'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/stores/{store}/disconnect',  [MarketplaceController::class, 'disconnectStore'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/stores/{store}/toggle-active', [MarketplaceController::class, 'toggleActive'])
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

// Dev/Owner Tools (Restricted to Owner, available in all environments)
$noCsrf = [\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class];
Route::middleware(['auth', 'role:owner'])->group(function () use ($noCsrf) {
    Route::post('/api/dev/fresh-orders',       [MarketplaceController::class, 'devFreshOrders'])->withoutMiddleware($noCsrf);
    Route::post('/api/dev/seed-orders',        [MarketplaceController::class, 'devSeedOrders'])->withoutMiddleware($noCsrf);
    Route::post('/api/dev/reset-fulfillments', [MarketplaceController::class, 'devResetFulfillments'])->withoutMiddleware($noCsrf);
    Route::post('/api/dev/run-audit',          [\App\Http\Controllers\DashboardController::class, 'devRunAudit'])->withoutMiddleware($noCsrf);
    Route::get('/api/dev/next-order',          [MarketplaceController::class, 'devNextOrder']);
    Route::get('/api/dev/stats',               [MarketplaceController::class, 'devStats']);
});

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

if (app()->environment(['local', 'testing'])) {
    Route::post('/dev/dummy/bulk-print', [\App\Http\Controllers\Dev\DummyBulkPrintController::class, 'createBulkPrintJob'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
}

// API endpoint for tracking
Route::post('activity-logs', [App\Http\Controllers\Owner\ActivityLogController::class, 'store'])->name('activity-logs.store')->middleware('auth')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
