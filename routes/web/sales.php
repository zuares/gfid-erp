<?php

use App\Http\Controllers\Sales\Reports\ChannelProfitReportController;
use App\Http\Controllers\Sales\Reports\ItemProfitReportController;
use App\Http\Controllers\Sales\Reports\ShipmentAnalyticsController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\ShipmentController;
use App\Http\Controllers\Sales\ShipmentReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:owner,admin'])->group(function () {

    Route::prefix('sales')->as('sales.')->group(function () {

        /**
         * =========================
         *  INVOICES
         * =========================
         */
        Route::get('invoices/create-from-shipment/{shipment}', [SalesInvoiceController::class, 'createFromShipment'])
            ->name('invoices.create_from_shipment');

        Route::post('invoices/{invoice}/post', [SalesInvoiceController::class, 'post'])
            ->name('invoices.post');

        Route::resource('invoices', SalesInvoiceController::class);

        /**
         * =========================
         *  SALES REPORTS
         * =========================
         */
        Route::prefix('reports')->as('reports.')->group(function () {
            Route::get('item-profit', [ItemProfitReportController::class, 'index'])
                ->name('item_profit');

            Route::get('channel-profit', [ChannelProfitReportController::class, 'index'])
                ->name('channel_profit');

            Route::get('shipment-analytics', [ShipmentAnalyticsController::class, 'index'])
                ->name('shipment_analytics');
        });

        /**
         * =========================
         *  SHIPMENTS
         * =========================
         */
        Route::prefix('shipments')->as('shipments.')->group(function () {
            // ⚠️ Penting: "report" harus didefinisikan sebelum {shipment} untuk menghindari bentrok
            Route::get('report', [ShipmentController::class, 'report'])
                ->name('report');

            Route::get('/', [ShipmentController::class, 'index'])
                ->name('index');

            Route::get('create', [ShipmentController::class, 'create'])
                ->name('create');

            Route::post('/', [ShipmentController::class, 'store'])
                ->name('store');

            Route::delete('{shipment}', [ShipmentController::class, 'destroy'])
                ->name('destroy');

            Route::get('{shipment}', [ShipmentController::class, 'show'])
                ->name('show');

            Route::get('{shipment}/edit', [ShipmentController::class, 'edit'])
                ->name('edit');

            Route::post('{shipment}/clear-lines', [ShipmentController::class, 'clearLines'])
                ->name('clear_lines');

            Route::post('{shipment}/scan-item', [ShipmentController::class, 'scanItem'])
                ->name('scan_item');

            Route::post('{shipment}/submit', [ShipmentController::class, 'submit'])
                ->name('submit');

            Route::post('{shipment}/post', [ShipmentController::class, 'post'])
                ->name('post');

            Route::post('{shipment}/sync-scans', [ShipmentController::class, 'syncScans'])
                ->name('sync_scans');

            Route::get('{shipment}/export-lines', [ShipmentController::class, 'exportLines'])
                ->name('export_lines');

            Route::post('{shipment}/import-lines', [ShipmentController::class, 'importLines'])
                ->name('import_lines');

            Route::post('{shipment}/import-preview', [ShipmentController::class, 'importPreview'])
                ->name('import_preview');
            Route::post('{shipment}/cancel', [ShipmentController::class, 'cancelPosted'])
                ->name('cancel')
                ->middleware('role:owner');
        });

        // Lines tetap dipisah karena parameternya langsung {line}
        Route::patch('shipments/lines/{line}', [ShipmentController::class, 'updateLineQty'])
            ->name('shipments.update_line_qty');

        Route::delete('shipments/lines/{line}', [ShipmentController::class, 'destroyLine'])
            ->name('shipments.destroy_line');

        /**
         * =========================
         *  SHIPMENT RETURNS
         * =========================
         */
        Route::prefix('shipment-returns')->as('shipment_returns.')->group(function () {
            Route::get('/', [ShipmentReturnController::class, 'index'])
                ->name('index');

            Route::get('create', [ShipmentReturnController::class, 'create'])
                ->name('create');

            Route::post('/', [ShipmentReturnController::class, 'store'])
                ->name('store');

            Route::get('{shipmentReturn}', [ShipmentReturnController::class, 'show'])
                ->name('show');

            Route::post('{shipmentReturn}/scan-item', [ShipmentReturnController::class, 'scanItem'])
                ->name('scan_item');

            Route::post('{shipmentReturn}/submit', [ShipmentReturnController::class, 'submit'])
                ->name('submit');

            Route::post('{shipmentReturn}/post', [ShipmentReturnController::class, 'post'])
                ->name('post');

            Route::post('{shipmentReturn}/sync-scans', [ShipmentReturnController::class, 'syncScans'])
                ->name('sync_scans');
        });

        Route::patch('shipment-return-lines/{line}', [ShipmentReturnController::class, 'updateLineQty'])
            ->name('shipment_returns.update_line_qty');
    });
});
