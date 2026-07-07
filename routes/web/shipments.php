<?php

use App\Http\Controllers\Sales\ShipmentController;
use App\Http\Controllers\Sales\ShipmentReturnController;
use App\Models\Shipment;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'access:sales'])
    ->prefix('sales')
    ->as('sales.')
    ->group(function () {

        /**
     * =========================
     *  SHIPMENTS
     * =========================
     */
        Route::prefix('shipments')
            ->as('shipments.')
            ->controller(ShipmentController::class)
            ->group(function () {

                // ⚠️ Penting: harus sebelum {shipment}
                Route::get('report', 'report')->name('report');
                Route::get('invoice-lookup', 'invoiceLookup')->name('invoice_lookup');

                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::post('dev-fresh', 'devFreshShipments')
                    ->name('dev_fresh')
                    ->middleware('role:owner');

                Route::get('{shipment}', 'show')->name('show');
                Route::get('{shipment}/scan-order', 'editOrderFirst')->name('scan_order');
                Route::get('{shipment}/edit', 'edit')->name('edit');
                Route::delete('{shipment}', 'destroy')->name('destroy');

                Route::post('{shipment}/clear-lines', 'clearLines')->name('clear_lines');
                Route::get('{shipment}/scan-lookup', 'scanLookup')->name('scan_lookup');
                Route::post('{shipment}/scan-item', 'scanItem')->name('scan_item');

                Route::post('{shipment}/submit', 'submit')->name('submit');
                Route::post('{shipment}/post', 'post')->name('post');
                Route::post('{shipment}/sync-scans', 'syncScans')->name('sync_scans');

                Route::get('{shipment}/export-lines', 'exportLines')->name('export_lines');
                Route::post('{shipment}/import-lines', 'importLines')->name('import_lines');
                Route::post('{shipment}/import-preview', 'importPreview')->name('import_preview');
                Route::get('/{shipment}/reconcile', [ShipmentController::class, 'reconcile'])
                    ->name('reconcile');

                // ── Opsi C: Rekonsiliasi Pesanan ──────────────────────────
                Route::get('/{shipment}/rekon',        'rekon')->name('rekon');
                Route::get('/{shipment}/confirm',      'confirmOrders')->name('confirm_orders');
                Route::post('/{shipment}/rekon/match', 'rekonMatch')->name('rekon_match');
                Route::post('/{shipment}/rekon/apply', 'rekonApply')->name('rekon_apply');

                Route::post('{shipment}/cancel', 'cancelPosted')
                    ->name('cancel')
                    ->middleware('role:owner');
            });

        // Lines tetap dipisah karena parameternya langsung {line}
        Route::patch('shipments/lines/{line}', [ShipmentController::class, 'updateLineQty'])
            ->name('shipments.update_line_qty');

        Route::delete('shipments/lines/{line}', [ShipmentController::class, 'destroyLine'])
            ->name('shipments.destroy_line');

        /**
     * =========================
     *  SHIPMENT RETURNS
     * =========================
     */
        Route::prefix('shipment-returns')
            ->as('shipment_returns.')
            ->controller(ShipmentReturnController::class)
            ->group(function () {

                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');

                Route::get('{shipmentReturn}/scan-lookup', 'scanLookup')->name('scan_lookup');
                Route::get('{shipmentReturn}/edit', 'edit')->name('edit');

                // ✅ Cetak barcode (qty label default = qty retur, bisa disesuaikan)
                Route::get('{shipmentReturn}/barcode', 'barcode')->name('barcode');
                Route::get('{shipmentReturn}/barcode-print', [\App\Http\Controllers\Inventory\BarcodeLabelController::class, 'print'])
                    ->name('barcode_print');

                Route::get('{shipmentReturn}', 'show')->name('show');

                Route::post('{shipmentReturn}/orders/bulk', 'bulkOrders')->name('bulk_orders');
                Route::post('{shipmentReturn}/orders/clear', 'clearOrders')->name('clear_orders');
                Route::post('{shipmentReturn}/scan-item', 'scanItem')->name('scan_item');
                Route::post('{shipmentReturn}/submit', 'submit')->name('submit');
                Route::post('{shipmentReturn}/post', 'post')->name('post');
                Route::post('{shipmentReturn}/sync-scans', 'syncScans')->name('sync_scans');
            });

        Route::patch('shipment-return-lines/{line}', [ShipmentReturnController::class, 'updateLineQty'])
            ->name('shipment_returns.update_line_qty');

        // AJAX: lookup shipment by code (for return create form)
        Route::get('api/shipments/lookup', function (\Illuminate\Http\Request $req) {
            $code = strtoupper(trim($req->query('code', '')));
            if (!$code) return response()->json(null);

            $shipment = Shipment::with('store:id,code,name')
                ->where('code', $code)
                ->first(['id', 'code', 'store_id', 'date']);

            if (!$shipment) return response()->json(null);

            return response()->json([
                'id'         => $shipment->id,
                'code'       => $shipment->code,
                'store_id'   => $shipment->store_id,
                'store_code' => $shipment->store?->code,
                'store_name' => $shipment->store?->name,
            ]);
        })->name('api.shipments.lookup');
    });
