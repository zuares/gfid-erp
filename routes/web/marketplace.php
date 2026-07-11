<?php

use App\Http\Controllers\Marketplace\MarketplaceOrderController;
use App\Http\Controllers\Marketplace\MpReconciliationController;
use App\Http\Controllers\Marketplace\MpReconciliationItemsController;
use App\Http\Controllers\Marketplace\MpReconciliationQueueController;

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
    });
