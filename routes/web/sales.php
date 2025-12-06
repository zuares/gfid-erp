<?php

use App\Http\Controllers\Sales\Reports\ChannelProfitReportController;
use App\Http\Controllers\Sales\Reports\ItemProfitReportController;
use App\Http\Controllers\Sales\Reports\ShipmentAnalyticsController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\ShipmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {

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
        // ⭐ NEW: Buat invoice dari Shipment

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

        Route::patch('shipments/lines/{line}', [ShipmentController::class, 'updateLineQty'])
            ->name('shipments.update_line_qty');
    });
});
