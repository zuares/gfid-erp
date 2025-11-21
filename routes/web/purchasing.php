<?php
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\PurchaseReceiptController;

Route::middleware(['web', 'auth'])->group(function () {

    Route::prefix('purchasing')->name('purchasing.')->group(function () {

        // Purchase Orders
        Route::resource('purchase-orders', PurchaseOrderController::class)
            ->names('purchase_orders');

        // Purchase Receipts (GRN)
        Route::resource('purchase-receipts', PurchaseReceiptController::class)
            ->names('purchase_receipts');

        // Action khusus: POST GRN
        Route::post('purchase-receipts/{purchase_receipt}/post', [PurchaseReceiptController::class, 'post'])
            ->name('purchase_receipts.post');

        // 🔥 NEW: Buat GRN dari PO
        Route::get('purchase-orders/{purchase_order}/create-grn', [PurchaseReceiptController::class, 'createFromOrder'])
            ->name('purchase_receipts.create_from_order');
    });

});
