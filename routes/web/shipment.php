<?php
// routes/web.php

use App\Http\Controllers\Shipment\ShipmentController;

Route::middleware(['auth'])
    ->prefix('shipments')
    ->name('shipments.')
    ->group(function () {
        Route::get('/', [ShipmentController::class, 'index'])->name('index');
        Route::get('/create', [ShipmentController::class, 'create'])->name('create');
        Route::post('/', [ShipmentController::class, 'store'])->name('store');
        Route::get('/{shipment}', [ShipmentController::class, 'show'])->name('show');

        // 🔗 Buat Shipment dari Sales Invoice
        Route::get('/from-invoice/{invoice}', [ShipmentController::class, 'createFromInvoice'])
            ->name('from_invoice');

        // 🔗 Buat Shipment dari Marketplace Order
        Route::get('/from-marketplace-order/{order}', [ShipmentController::class, 'createFromMarketplaceOrder'])
            ->name('from_marketplace_order');
    });
