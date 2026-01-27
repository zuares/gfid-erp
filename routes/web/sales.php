<?php

use App\Http\Controllers\Sales\Reports\ChannelProfitReportController;
use App\Http\Controllers\Sales\Reports\ItemProfitReportController;
use App\Http\Controllers\Sales\Reports\SalesReportController;
use App\Http\Controllers\Sales\Reports\ShipmentAnalyticsController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:owner,admin'])
    ->prefix('sales')
    ->as('sales.')
    ->group(function () {

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

            // ✅ NEW: Penjualan & Performa Produk (daily_item_sales)
            Route::get('sales-performance', [SalesReportController::class, 'index'])
                ->name('sales_performance.index');
        });
    });
