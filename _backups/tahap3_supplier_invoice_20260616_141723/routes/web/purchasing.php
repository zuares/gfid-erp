<?php

use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\PurchasePaymentController;
use App\Http\Controllers\Purchasing\PurchaseReceiptController;
use App\Http\Controllers\Purchasing\PurchaseReturnController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'access:purchasing'])
    ->prefix('purchasing')
    ->name('purchasing.')
    ->group(function () {

        // OWNER + ADMIN
        Route::middleware('access:purchasing')->group(function () {

            // PURCHASE ORDERS
            Route::resource('purchase-orders', PurchaseOrderController::class)
                ->names('purchase_orders');

            Route::get('supplier-price', [PurchaseOrderController::class, 'getSupplierLastPrice'])
                ->name('supplier_price');

            // PAYMENTS
            Route::post('purchase-orders/{purchase_order}/payments', [PurchasePaymentController::class, 'store'])
                ->name('purchase_orders.payments.store');

            Route::post('purchase-orders/{purchase_order}/payments/{payment}/void', [PurchasePaymentController::class, 'void'])
                ->name('purchase_orders.payments.void');

            Route::post('purchase-orders/{purchase_order}/apply-dp', [PurchasePaymentController::class, 'applyDp'])
                ->name('purchase_orders.payments.apply_dp');

            // GRN
            Route::resource('purchase-receipts', PurchaseReceiptController::class)
                ->names('purchase_receipts');

            Route::post('purchase-receipts/{purchase_receipt}/post', [PurchaseReceiptController::class, 'post'])
                ->name('purchase_receipts.post');

            Route::post('purchase-receipts/{purchase_receipt}/unpost', [PurchaseReceiptController::class, 'unpost'])
                ->name('purchase_receipts.unpost');

            // ✅ RETURN FROM GRN (create from posted GRN)
            Route::post('purchase-receipts/{purchase_receipt}/returns/create',
                [PurchaseReturnController::class, 'createFromGrn']
            )->name('grn.returns.create');

            // Create GRN from PO
            Route::get('purchase-orders/{purchase_order}/create-grn', [PurchaseReceiptController::class, 'createFromOrder'])
                ->name('purchase_receipts.create_from_order');
        });

        // OWNER + ADMIN actions
        Route::middleware('role:owner,admin')->group(function () {

            Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])
                ->name('purchase_orders.approve');
        });

        // OWNER only actions
        Route::middleware('role:owner')->group(function () {

            Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])
                ->name('purchase_orders.cancel');

            // RETURN lifecycle
            Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])
                ->name('purchase_returns.index');

            Route::get('purchase-returns/{purchase_return}', [PurchaseReturnController::class, 'show'])
                ->name('purchase_returns.show');

            Route::put('purchase-returns/{purchase_return}', [PurchaseReturnController::class, 'update'])
                ->name('purchase_returns.update');

            Route::post('purchase-returns/{purchase_return}/post', [PurchaseReturnController::class, 'post'])
                ->name('purchase_returns.post');

            Route::post('purchase-returns/{purchase_return}/void', [PurchaseReturnController::class, 'void'])
                ->name('purchase_returns.void');
        });
    });
