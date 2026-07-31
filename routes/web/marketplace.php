<?php

use App\Http\Controllers\Marketplace\MarketplaceOrderController;
use App\Http\Controllers\Marketplace\MpReconciliationController;
use App\Http\Controllers\Marketplace\MpReconciliationItemsController;
use App\Http\Controllers\Marketplace\MpReconciliationQueueController;
use App\Http\Controllers\Marketplace\AdsDashboardController;
Route::middleware(['web', 'auth', 'access:marketplace'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {

        Route::get('fix-unpaid-orders', function() {
            $orders = \App\Models\MarketplaceOrder::where('order_status', 'UNPAID')->get();
            $sync = app(\App\Services\OmnichannelSyncService::class);
            $html = "<div style='font-family:sans-serif; padding:20px;'><h3>Memperbaiki " . $orders->count() . " pesanan nyangkut...</h3><br>";
            foreach($orders as $o) {
                try {
                    $sync->syncSpecificOrder($o->store, $o->channel_order_id);
                    $html .= "✅ Berhasil update status pesanan: {$o->channel_order_id}<br>";
                } catch (\Exception $e) {
                    $html .= "❌ Gagal update {$o->channel_order_id}: " . $e->getMessage() . "<br>";
                }
            }
            return $html . "<br><b>Selesai!</b> Semua pesanan nyangkut sudah dibersihkan. Silakan tutup halaman ini.</div>";
        });

        // =========================
        // Marketplace Orders
        // =========================
        Route::resource('orders', MarketplaceOrderController::class)
            ->only(['index', 'show', 'create', 'store']);
        Route::post('orders/{order}/test-settlement-fields', [MarketplaceOrderController::class, 'updateSettlementTestFields'])
            ->name('orders.test-settlement-fields');

        Route::get('reports/sales', [MarketplaceOrderController::class, 'salesSummary'])
            ->name('reports.sales');

        Route::get('reports/sales/export', [MarketplaceOrderController::class, 'salesSummaryCsv'])
            ->name('reports.sales.export');

        // =========================
        // Marketplace Reconciliation (Domain)
        // =========================
        Route::get('reconcile/queue', [MpReconciliationQueueController::class, 'index'])
            ->name('reconcile.queue');

        Route::post('reconcile/queue', [MpReconciliationQueueController::class, 'bulk'])
            ->name('reconcile.queue.bulk');

        Route::post('reconcile/preview', [MpReconciliationQueueController::class, 'preview'])
            ->name('reconcile.preview');

        Route::post('reconcile/commit', [MpReconciliationQueueController::class, 'commit'])
            ->name('reconcile.commit');

        Route::post('reconciliations/{rec}/resolve', [MpReconciliationController::class, 'resolve'])
            ->name('reconciliations.resolve');

        Route::get('reconciliations/{rec}/diff', [MpReconciliationController::class, 'diff'])
            ->name('reconciliations.diff');

        Route::get('reconcile-items', [MpReconciliationItemsController::class, 'index'])
            ->name('reconcile.items');

        Route::post('reconcile-items/apply', [MpReconciliationItemsController::class, 'apply'])
            ->name('reconcile.items.apply');

        Route::get('reconcile-items/packets', [MpReconciliationItemsController::class, 'packets'])
            ->name('reconcile.items.packets');

        // =========================
        // Marketplace Ads Dashboard
        // =========================
        Route::get('ads-dashboard', [AdsDashboardController::class, 'index'])
            ->name('ads.dashboard');
        Route::post('ads-dashboard/sync', [AdsDashboardController::class, 'sync'])
            ->name('ads.sync');
        Route::post('ads-dashboard/clear', [AdsDashboardController::class, 'clear'])
            ->name('ads.clear');
        Route::get('ads-dashboard/realtime-status', [AdsDashboardController::class, 'realtimeStatus'])
            ->name('ads.realtime.status');
        Route::get('ads-dashboard/campaign-hourly', [AdsDashboardController::class, 'campaignHourly'])
            ->name('ads.campaign.hourly');
        Route::post('ads-dashboard/gms-item-action', [AdsDashboardController::class, 'actionGmsItem'])
            ->name('ads.gms.action');
        Route::post('ads-dashboard/gms-campaign-edit', [AdsDashboardController::class, 'actionGmsCampaign'])
            ->name('ads.gms.campaign.edit');
        Route::post('ads-dashboard/cpc-campaign-edit', [AdsDashboardController::class, 'actionCpcCampaign'])
            ->name('ads.cpc.campaign.edit');
        Route::post('ads-dashboard/fee-setting', [AdsDashboardController::class, 'saveFeeSetting'])
            ->name('ads.fee.setting');
    });
