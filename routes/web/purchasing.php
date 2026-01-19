<?php

use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\PurchasePaymentController;
use App\Http\Controllers\Purchasing\PurchaseReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('purchasing')->name('purchasing.')->group(function () {

    // ==========================================================
    // Default access: OWNER + ADMIN
    // ==========================================================
    Route::middleware('role:owner,admin')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | PURCHASE ORDERS
        |--------------------------------------------------------------------------
         */

        Route::resource('purchase-orders', PurchaseOrderController::class)
            ->names('purchase_orders');

        // API kecil: last price supplier per item
        // GET /purchasing/supplier-price?supplier_id=1&item_id=2
        Route::get('supplier-price', [PurchaseOrderController::class, 'getSupplierLastPrice'])
            ->name('supplier_price');

        /*
        |--------------------------------------------------------------------------
        | PURCHASE ORDER PAYMENTS (DP / Payment)
        |--------------------------------------------------------------------------
         */

        Route::post('purchase-orders/{purchase_order}/payments', [PurchasePaymentController::class, 'store'])
            ->name('purchase_orders.payments.store');

        Route::post('purchase-orders/{purchase_order}/payments/{payment}/void', [PurchasePaymentController::class, 'void'])
            ->name('purchase_orders.payments.void');

        /*
        |--------------------------------------------------------------------------
        | PURCHASE RECEIPTS (GRN)
        |--------------------------------------------------------------------------
         */

        Route::resource('purchase-receipts', PurchaseReceiptController::class)
            ->names('purchase_receipts');

        Route::post('purchase-receipts/{purchase_receipt}/post', [PurchaseReceiptController::class, 'post'])
            ->name('purchase_receipts.post');

        Route::post('purchase-receipts/{purchase_receipt}/unpost', [PurchaseReceiptController::class, 'unpost'])
            ->name('purchase_receipts.unpost');

        // Buat GRN langsung dari PO (hanya PO approved) - action route
        Route::get('purchase-orders/{purchase_order}/create-grn', [PurchaseReceiptController::class, 'createFromOrder'])
            ->name('purchase_receipts.create_from_order');
    });

    // ==========================================================
    // OWNER only actions
    // ==========================================================
    Route::middleware('role:owner')->group(function () {
        Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])
            ->name('purchase_orders.approve');

        Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])
            ->name('purchase_orders.cancel');
    });
});
