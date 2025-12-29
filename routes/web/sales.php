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
        Route::get('reports/item-profit', [ItemProfitReportController::class, 'index'])
            ->name('reports.item_profit');

        Route::get('reports/channel-profit', [ChannelProfitReportController::class, 'index'])
            ->name('reports.channel_profit');

        Route::get('reports/shipment-analytics', [ShipmentAnalyticsController::class, 'index'])
            ->name('reports.shipment_analytics');

        /**
         * =========================
         *  SHIPMENTS
         * =========================
         */
        Route::get('shipments', [ShipmentController::class, 'index'])
            ->name('shipments.index');

        Route::get('shipments/create', [ShipmentController::class, 'create'])
            ->name('shipments.create');

        Route::post('shipments', [ShipmentController::class, 'store'])
            ->name('shipments.store');

        Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])
            ->name('shipments.show');

        Route::post('shipments/{shipment}/scan-item', [ShipmentController::class, 'scanItem'])
            ->name('shipments.scan_item');

        Route::post('shipments/{shipment}/submit', [ShipmentController::class, 'submit'])
            ->name('shipments.submit');

        Route::post('shipments/{shipment}/post', [ShipmentController::class, 'post'])
            ->name('shipments.post');

        Route::post('shipments/{shipment}/sync-scans', [ShipmentController::class, 'syncScans'])
            ->name('shipments.sync_scans');

        Route::get('shipments/{shipment}/export-lines', [ShipmentController::class, 'exportLines'])
            ->name('shipments.export_lines');

        Route::patch('shipments/lines/{line}', [ShipmentController::class, 'updateLineQty'])
            ->name('shipments.update_line_qty');
        Route::delete('shipments/lines/{line}', [ShipmentController::class, 'destroyLine'])
            ->name('shipments.destroy_line');
        Route::post('/shipments/{shipment}/import-lines', [ShipmentController::class, 'importLines'])
            ->name('shipments.import_lines');
        Route::post('/shipments/{shipment}/import-preview', [ShipmentController::class, 'importPreview'])
            ->name('shipments.import_preview');

        /**
         * =========================
         *  SHIPMENT RETURNS
         * =========================
         */
        Route::get('shipment-returns', [ShipmentReturnController::class, 'index'])
            ->name('shipment_returns.index');

        Route::get('shipment-returns/create', [ShipmentReturnController::class, 'create'])
            ->name('shipment_returns.create');

        Route::post('shipment-returns', [ShipmentReturnController::class, 'store'])
            ->name('shipment_returns.store');

        Route::get('shipment-returns/{shipmentReturn}', [ShipmentReturnController::class, 'show'])
            ->name('shipment_returns.show');

        Route::post('shipment-returns/{shipmentReturn}/scan-item', [ShipmentReturnController::class, 'scanItem'])
            ->name('shipment_returns.scan_item');

        Route::post('shipment-returns/{shipmentReturn}/submit', [ShipmentReturnController::class, 'submit'])
            ->name('shipment_returns.submit');

        Route::post('shipment-returns/{shipmentReturn}/post', [ShipmentReturnController::class, 'post'])
            ->name('shipment_returns.post');

        Route::post('shipment-returns/{shipmentReturn}/sync-scans', [ShipmentReturnController::class, 'syncScans'])
            ->name('shipment_returns.sync_scans');

        Route::patch('shipment-return-lines/{line}', [ShipmentReturnController::class, 'updateLineQty'])
            ->name('shipment_returns.update_line_qty');
    });
});
