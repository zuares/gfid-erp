<?php

// routes/web.php
use App\Http\Controllers\Sales\SalesInvoiceController;

Route::middleware(['web', 'auth'])
    ->prefix('sales')
    ->name('sales.')
    ->group(function () {
        Route::resource('invoices', SalesInvoiceController::class)
            ->only(['index', 'create', 'store', 'show']);

        Route::get('reports/item-profit', [SalesReportController::class, 'itemProfit'])
            ->name('reports.item_profit');

        Route::get('reports/channel-profit', [SalesReportController::class, 'channelProfit'])
            ->name('reports.channel_profit');

    });
