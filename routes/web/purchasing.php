<?php

use App\Http\Controllers\Purchasing\MaterialShortageController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\PurchasePaymentController;
use App\Http\Controllers\Purchasing\PurchaseReceiptController;
use App\Http\Controllers\Purchasing\PurchaseRequestController;
use App\Http\Controllers\Purchasing\PurchaseReturnController;
use App\Http\Controllers\Purchasing\PurchasingDashboardController;
use App\Http\Controllers\Purchasing\SupplierInvoiceController;
use App\Http\Controllers\Purchasing\SupplierItemMappingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'access:purchasing'])
    ->prefix('purchasing')
    ->name('purchasing.')
    ->group(function () {

        // DASHBOARD — owner + admin + accounting
        Route::get('dashboard', [PurchasingDashboardController::class, 'index'])
            ->name('dashboard');  // full name: purchasing.dashboard

        Route::get('material-shortages', [MaterialShortageController::class, 'index'])
            ->name('material_shortages.index');
        Route::post('material-shortages/purchase-request', [MaterialShortageController::class, 'createPurchaseRequest'])
            ->name('material_shortages.purchase_request');

        Route::get('supplier-items', [SupplierItemMappingController::class, 'index'])
            ->name('supplier_items.index');
        Route::post('supplier-items', [SupplierItemMappingController::class, 'store'])
            ->name('supplier_items.store');
        Route::put('supplier-items/{supplierItem}', [SupplierItemMappingController::class, 'update'])
            ->name('supplier_items.update');
        Route::delete('supplier-items/{supplierItem}', [SupplierItemMappingController::class, 'destroy'])
            ->name('supplier_items.destroy');
        Route::post('supplier-category-mappings', [SupplierItemMappingController::class, 'storeCategory'])
            ->name('supplier_category_mappings.store');
        Route::put('supplier-category-mappings/{supplierCategoryMapping}', [SupplierItemMappingController::class, 'updateCategory'])
            ->name('supplier_category_mappings.update');
        Route::delete('supplier-category-mappings/{supplierCategoryMapping}', [SupplierItemMappingController::class, 'destroyCategory'])
            ->name('supplier_category_mappings.destroy');
        Route::post('supplier-category-mappings/sync', [SupplierItemMappingController::class, 'syncCategorySupplier'])
            ->name('supplier_category_mappings.sync');

        // PURCHASE REQUEST — semua yang punya access:purchasing
        // CRUD resource (index, create, store, show, edit, update)
        Route::resource('purchase-requests', PurchaseRequestController::class)
            ->names('purchase_requests')
            ->except(['destroy']);

        // PR-C + PR-D: Approve / Reject / Convert — hanya owner + admin
        Route::middleware('role:owner,admin')->group(function () {
            Route::post('purchase-requests/{purchase_request}/approve', [PurchaseRequestController::class, 'approve'])
                ->name('purchase_requests.approve');
            Route::post('purchase-requests/{purchase_request}/reject', [PurchaseRequestController::class, 'reject'])
                ->name('purchase_requests.reject');
            Route::get('purchase-requests/{purchase_request}/allocate-suppliers', [PurchaseRequestController::class, 'allocateSuppliers'])
                ->name('purchase_requests.allocate_suppliers');
            Route::post('purchase-requests/{purchase_request}/convert', [PurchaseRequestController::class, 'convert'])
                ->name('purchase_requests.convert');
        });

        // OWNER + ADMIN
        Route::middleware('access:purchasing')->group(function () {

            // PURCHASE ORDERS
            Route::resource('purchase-orders', PurchaseOrderController::class)
                ->names('purchase_orders');

            Route::get('supplier-price', [PurchaseOrderController::class, 'getSupplierLastPrice'])
                ->name('supplier_price');

            // PAYMENTS — standalone module
            Route::get('purchase-payments', [PurchasePaymentController::class, 'index'])
                ->name('purchase_payments.index');

            // PAYMENTS — sub-routes per PO (tetap ada)
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

            // ✅ CETAK BARCODE (qty label default = qty diterima, bisa disesuaikan)
            Route::get('purchase-receipts/{purchase_receipt}/barcode', [PurchaseReceiptController::class, 'barcode'])
                ->name('purchase_receipts.barcode');
            Route::get('purchase-receipts-barcode/print', [\App\Http\Controllers\Inventory\BarcodeLabelController::class, 'print'])
                ->name('purchase_receipts.barcode_print');

            // ✅ RETURN FROM GRN (create from posted GRN)
            Route::post('purchase-receipts/{purchase_receipt}/returns/create',
                [PurchaseReturnController::class, 'createFromGrn']
            )->name('grn.returns.create');

            // Tahap 6: QC / Pemeriksaan Barang (opsional, satu per GRN)
            Route::prefix('purchase-receipts/{purchase_receipt}/qc')
                ->name('purchase_receipts.qc.')
                ->group(function () {
                    Route::get('create',  [\App\Http\Controllers\Purchasing\PurchaseReceiptQcController::class, 'create'])->name('create');
                    Route::post('',       [\App\Http\Controllers\Purchasing\PurchaseReceiptQcController::class, 'store'])->name('store');
                    Route::get('edit',    [\App\Http\Controllers\Purchasing\PurchaseReceiptQcController::class, 'edit'])->name('edit');
                    Route::put('',        [\App\Http\Controllers\Purchasing\PurchaseReceiptQcController::class, 'update'])->name('update');
                    Route::delete('',     [\App\Http\Controllers\Purchasing\PurchaseReceiptQcController::class, 'cancel'])->name('cancel');
                });

            // Create GRN from PO
            Route::get('purchase-orders/{purchase_order}/create-grn', [PurchaseReceiptController::class, 'createFromOrder'])
                ->name('purchase_receipts.create_from_order');
        });

        // OWNER + ADMIN actions
        Route::middleware('role:owner,admin')->group(function () {

            Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])
                ->name('purchase_orders.approve');
        });

        // SUPPLIER INVOICE — owner + accounting (akses dikontrol di controller)
        Route::get('supplier-invoices', [SupplierInvoiceController::class, 'index'])
            ->name('supplier_invoices.index');
        Route::get('supplier-invoices/create', [SupplierInvoiceController::class, 'create'])
            ->name('supplier_invoices.create');
        Route::post('supplier-invoices', [SupplierInvoiceController::class, 'store'])
            ->name('supplier_invoices.store');
        Route::get('supplier-invoices/{supplierInvoice}', [SupplierInvoiceController::class, 'show'])
            ->name('supplier_invoices.show');
        Route::post('supplier-invoices/{supplierInvoice}/post', [SupplierInvoiceController::class, 'post'])
            ->name('supplier_invoices.post');
        Route::delete('supplier-invoices/{supplierInvoice}/void', [SupplierInvoiceController::class, 'void'])
            ->name('supplier_invoices.void');

        // OWNER + ADMIN — read + edit return (draft only), close PO, cancel PO
        Route::middleware('role:owner,admin')->group(function () {

            // RETURN — index, show, edit draft (owner+admin)
            Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])
                ->name('purchase_returns.index');

            Route::get('purchase-returns/{purchase_return}', [PurchaseReturnController::class, 'show'])
                ->name('purchase_returns.show');

            Route::put('purchase-returns/{purchase_return}', [PurchaseReturnController::class, 'update'])
                ->name('purchase_returns.update');

            // RETURN — submit (draft -> submitted) + post (owner+admin)
            Route::post('purchase-returns/{purchase_return}/submit', [PurchaseReturnController::class, 'submit'])
                ->name('purchase_returns.submit');

            Route::post('purchase-returns/{purchase_return}/post', [PurchaseReturnController::class, 'post'])
                ->name('purchase_returns.post');

            // Tahap 9 — QC resolve route (owner+admin)
            Route::post(
                'purchase-receipt-qcs/{qc}/resolve',
                [\App\Http\Controllers\Purchasing\PurchaseReceiptQcController::class, 'resolve']
            )->name('purchase_receipt_qcs.resolve');

            // Tahap 9 — Invoice deduction update (owner+admin)
            Route::post(
                'supplier-invoices/{supplier_invoice}/set-deduction',
                [\App\Http\Controllers\Purchasing\SupplierInvoiceController::class, 'setDeduction']
            )->name('supplier_invoices.set_deduction');
        });

        // OWNER only — destructive actions
        Route::middleware('role:owner')->group(function () {

            Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])
                ->name('purchase_orders.cancel');

            // Tahap 4: Close PO
            Route::post('purchase-orders/{purchase_order}/close', [PurchaseOrderController::class, 'close'])
                ->name('purchase_orders.close');

            // RETURN — void (owner only — reversal jurnal + stok)
            Route::post('purchase-returns/{purchase_return}/void', [PurchaseReturnController::class, 'void'])
                ->name('purchase_returns.void');
        });
    });
