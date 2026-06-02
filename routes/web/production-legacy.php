<?php

use App\Http\Controllers\Production\ProductionActivityController;
use App\Http\Controllers\Production\ProductionIssueController;
use App\Http\Controllers\Production\ProductionOrderController;
use App\Http\Controllers\Production\ProductionReceiptController;

/*
|--------------------------------------------------------------------------
| PRODUCTION (LEGACY / DEPRECATED) — Orders, Issues, Receipts, Activities
|--------------------------------------------------------------------------
| @deprecated Generasi lama alur produksi (production_orders / issues /
| receipts / activities). Digantikan oleh modul aktif Cutting → QC →
| Sewing → Finishing → Packing + production_movements (lihat production.php).
|
| Per 2026-06: keempat tabel pendukung modul ini berjumlah 0 baris.
| Route dipertahankan apa adanya (TIDAK diubah middleware/nama) demi
| backward-compatibility — view legacy (production/orders|issues|receipts)
| masih memanggil nama route ini. Jangan dihapus tanpa persetujuan.
|
| Sebelumnya didefinisikan inline di routes/web.php; dipindah ke sini
| agar konsisten dengan pola routing per-domain. Perilaku identik.
|--------------------------------------------------------------------------
 */
Route::prefix('production')->group(function () {
    Route::get('orders', [ProductionOrderController::class, 'index'])
        ->name('production.orders.index');

    Route::get('orders/{order}', [ProductionOrderController::class, 'show'])
        ->name('production.orders.show');

    Route::post('issues/{issue}/post', [ProductionIssueController::class, 'post'])
        ->name('production.issues.post');

    Route::post('receipts/{receipt}/post', [ProductionReceiptController::class, 'post'])
        ->name('production.receipts.post');

    Route::get('orders/{order}/issues/create', [ProductionIssueController::class, 'create'])
        ->name('production.issues.create');

    Route::post('orders/{order}/issues', [ProductionIssueController::class, 'store'])
        ->name('production.issues.store');

    Route::get('orders/{order}/receipts/create', [ProductionReceiptController::class, 'create'])
        ->name('production.receipts.create');

    Route::post('orders/{order}/receipts', [ProductionReceiptController::class, 'store'])
        ->name('production.receipts.store');

    Route::post('orders/{order}/activities', [ProductionActivityController::class, 'store'])
        ->name('production.activities.store');

    Route::delete('activities/{activity}', [ProductionActivityController::class, 'destroy'])
        ->name('production.activities.destroy');

});
