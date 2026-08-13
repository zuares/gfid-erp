<?php

use App\Http\Controllers\Sales\Reports\ChannelProfitReportController;
use App\Http\Controllers\Sales\Reports\ItemProfitReportController;
use App\Http\Controllers\Sales\Reports\SalesReportController;
use App\Http\Controllers\Sales\Reports\ShipmentAnalyticsController;
use App\Http\Controllers\Sales\Reports\ShipmentReportController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Admin\StorefrontWebsiteSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'access:sales'])
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

            // Laporan Pengiriman (ringkasan + daftar shipment per periode)
            Route::get('shipment', [ShipmentReportController::class, 'index'])
                ->name('shipment');

            // ✅ NEW: Penjualan & Performa Produk (daily_item_sales)
            Route::get('sales-performance', [SalesReportController::class, 'index'])
                ->name('sales_performance.index');
        });

        Route::middleware('role:owner')->prefix('settings')->as('settings.')->group(function () {
            Route::get('operational', [StorefrontWebsiteSettingsController::class, 'operationalIndex'])
                ->name('operational');
            Route::get('operational/documents/{document}', [StorefrontWebsiteSettingsController::class, 'operationalDocument'])
                ->name('operational.documents');
            Route::post('operational', [StorefrontWebsiteSettingsController::class, 'updateOperational'])
                ->name('operational.update');
            Route::post('operational/reset-scan', [StorefrontWebsiteSettingsController::class, 'resetOperationalScanSettings'])
                ->name('operational.reset-scan');
            Route::post('operational/reset-lookup', [StorefrontWebsiteSettingsController::class, 'resetOperationalLookupSettings'])
                ->name('operational.reset-lookup');
            Route::delete('operational/ringtones/{ringtone}', [StorefrontWebsiteSettingsController::class, 'deleteOperationalRingtone'])
                ->name('operational.ringtones.delete');
            Route::post('operational/ringtones/{ringtone}/trim', [StorefrontWebsiteSettingsController::class, 'trimOperationalRingtone'])
                ->name('operational.ringtones.trim');
        });
    });
