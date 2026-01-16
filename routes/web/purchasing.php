<?php

use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\PurchasePaymentController;
use App\Http\Controllers\Purchasing\PurchaseReceiptController;

Route::middleware(['web', 'auth'])->group(function () {

    Route::prefix('purchasing')->name('purchasing.')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | PURCHASE ORDERS
        |--------------------------------------------------------------------------
        | Admin = boleh create/edit PO
        | Owner = boleh approve/cancel PO + semua akses PO
        |--------------------------------------------------------------------------
         */

        // CRUD PO — admin & owner
        Route::resource('purchase-orders', PurchaseOrderController::class)
            ->names('purchase_orders')
            ->middleware('role:owner,admin');

        // APPROVE PO — hanya owner
        Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])
            ->name('purchase_orders.approve')
            ->middleware('role:owner');

        // CANCEL PO — hanya owner
        Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])
            ->name('purchase_orders.cancel')
            ->middleware('role:owner');

        // ✅ API kecil untuk ambil harga terakhir supplier per item
        // GET /purchasing/supplier-price?supplier_id=1&item_id=2
        Route::get('supplier-price', [PurchaseOrderController::class, 'getSupplierLastPrice'])
            ->name('supplier_price')
            ->middleware('role:owner,admin');

        /*
        |--------------------------------------------------------------------------
        | PURCHASE ORDER PAYMENTS (DP / Payment)
        |--------------------------------------------------------------------------
        | DP = nominal angka (bukan persen)
        | Admin & Owner boleh input/void pembayaran
        |--------------------------------------------------------------------------
         */

        // Simpan pembayaran (DP / payment)
        Route::post('purchase-orders/{purchase_order}/payments', [PurchasePaymentController::class, 'store'])
            ->name('purchase_orders.payments.store')
            ->middleware('role:owner,admin');

        // VOID pembayaran
        Route::post('purchase-orders/{purchase_order}/payments/{payment}/void', [PurchasePaymentController::class, 'void'])
            ->name('purchase_orders.payments.void')
            ->middleware('role:owner,admin');

        /*
        |--------------------------------------------------------------------------
        | PURCHASE RECEIPTS (GRN)
        |--------------------------------------------------------------------------
        | GRN hanya boleh dibuat dari PO yg status-nya 'approved'
        | Admin & Owner boleh create GRN setelah PO approved
        |--------------------------------------------------------------------------
         */

        Route::resource('purchase-receipts', PurchaseReceiptController::class)
            ->names('purchase_receipts')
            ->middleware('role:owner,admin');

        // Posting GRN — admin & owner
        Route::post('purchase-receipts/{purchase_receipt}/post', [PurchaseReceiptController::class, 'post'])
            ->name('purchase_receipts.post')
            ->middleware('role:owner,admin');

        // Buat GRN langsung dari PO (hanya PO approved)
        Route::get('purchase-orders/{purchase_order}/create-grn', [PurchaseReceiptController::class, 'createFromOrder'])
            ->name('purchase_receipts.create_from_order')
            ->middleware('role:owner,admin');
    });

});
