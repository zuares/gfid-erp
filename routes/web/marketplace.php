<?php

use App\Http\Controllers\Marketplace\MarketplaceOrderController;
use App\Http\Controllers\Marketplace\MpReconciliationController;
use App\Http\Controllers\Marketplace\MpReconciliationQueueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {

        // =========================
        // Marketplace Orders
        // =========================
        Route::resource('orders', MarketplaceOrderController::class)
            ->only(['index', 'show', 'create', 'store']);

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
    });
