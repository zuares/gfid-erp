<?php

namespace Tests\Feature;

use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\PurchasePaymentController;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class PurchasePaymentToleranceTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_rupiah_remainder_is_normalized_to_zero_but_larger_remainder_is_kept(): void
    {
        $this->assertSame(0.0, PurchaseOrder::normalizePaymentRemainder(1.00));
        $this->assertSame(0.0, PurchaseOrder::normalizePaymentRemainder(0.98));
        $this->assertSame(1.01, PurchaseOrder::normalizePaymentRemainder(1.01));
    }

    public function test_ap_outstanding_treats_one_rupiah_rounding_remainder_as_paid(): void
    {
        $supplier = Supplier::create([
            'code' => 'TOL-SUP-' . uniqid(),
            'name' => 'Tolerance Supplier',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'TOL-PO-' . uniqid(),
            'date' => '2026-08-27',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000,
            'status' => 'approved',
            'payment_status' => 'partial',
            'paid_amount' => 999,
        ]);
        $method = PaymentMethod::create([
            'code' => 'TOL-CASH-' . uniqid(),
            'name' => 'Tolerance Cash',
            'mode' => 'cash',
            'is_active' => true,
        ]);
        PurchaseReceipt::create([
            'code' => 'TOL-GRN-' . uniqid(),
            'date' => '2026-08-27',
            'purchase_order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'grand_total' => 1000,
            'status' => 'posted',
            'is_replacement' => false,
        ]);
        PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-08-27',
            'payment_method_id' => $method->id,
            'type' => 'payment',
            'amount' => 999,
        ]);

        $controller = app(PurchasePaymentController::class);
        $calc = new ReflectionMethod($controller, 'calcApOutstandingByGrn');
        $calc->setAccessible(true);

        $this->assertSame(0.0, $calc->invoke($controller, $order->fresh()));
    }

    public function test_payment_status_recalc_marks_one_rupiah_remainder_as_paid_in_both_flows(): void
    {
        $supplier = Supplier::create([
            'code' => 'TOL-STATUS-SUP-' . uniqid(),
            'name' => 'Tolerance Status Supplier',
        ]);
        $method = PaymentMethod::create([
            'code' => 'TOL-STATUS-CASH-' . uniqid(),
            'name' => 'Tolerance Status Cash',
            'mode' => 'cash',
            'is_active' => true,
        ]);

        $orders = collect([
            PurchaseOrder::create([
                'code' => 'TOL-STATUS-A-' . uniqid(),
                'date' => '2026-08-27',
                'supplier_id' => $supplier->id,
                'grand_total' => 1000,
                'status' => 'draft',
                'payment_status' => 'partial',
            ]),
            PurchaseOrder::create([
                'code' => 'TOL-STATUS-B-' . uniqid(),
                'date' => '2026-08-27',
                'supplier_id' => $supplier->id,
                'grand_total' => 1000,
                'status' => 'draft',
                'payment_status' => 'partial',
            ]),
        ]);

        foreach ($orders as $order) {
            PurchasePayment::create([
                'purchase_order_id' => $order->id,
                'date' => '2026-08-27',
                'payment_method_id' => $method->id,
                'type' => 'payment',
                'amount' => 999,
            ]);
        }

        $paymentController = app(PurchasePaymentController::class);
        $paymentRecalc = new ReflectionMethod($paymentController, 'recalcPaymentStatus');
        $paymentRecalc->setAccessible(true);
        $paymentRecalc->invoke($paymentController, $orders[0]);

        $orderController = app(PurchaseOrderController::class);
        $orderRecalc = new ReflectionMethod($orderController, 'recalcPaymentStatus');
        $orderRecalc->setAccessible(true);
        $orderRecalc->invoke($orderController, $orders[1]);

        foreach ($orders as $order) {
            $order->refresh();
            $this->assertSame('paid', $order->payment_status);
            $this->assertSame('approved', $order->status);
            $this->assertSame(999.0, (float) $order->paid_amount);
        }
    }

    public function test_dp_only_cannot_auto_close_po_before_ap_is_offset(): void
    {
        $supplier = Supplier::create([
            'code' => 'AUTO-CLOSE-DP-SUP-' . uniqid(),
            'name' => 'Auto Close DP Supplier',
        ]);
        $method = PaymentMethod::create([
            'code' => 'AUTO-CLOSE-DP-CASH-' . uniqid(),
            'name' => 'Auto Close DP Cash',
            'mode' => 'cash',
            'is_active' => true,
        ]);
        $order = PurchaseOrder::create([
            'code' => 'AUTO-CLOSE-DP-PO-' . uniqid(),
            'date' => '2026-08-31',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000,
            'status' => 'approved',
            'received_status' => 'fully_received',
            'payment_status' => 'paid',
            'paid_amount' => 1000,
        ]);
        PurchaseReceipt::create([
            'code' => 'AUTO-CLOSE-DP-GRN-' . uniqid(),
            'date' => '2026-08-31',
            'purchase_order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'grand_total' => 1000,
            'status' => 'posted',
            'is_replacement' => false,
        ]);
        PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-08-31',
            'payment_method_id' => $method->id,
            'type' => 'dp',
            'amount' => 1000,
        ]);

        $order->evaluateAutoClose();

        $this->assertSame('approved', $order->fresh()->status);
        $this->assertSame(1000.0, $order->fresh()->accountsPayableOutstanding());

        PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-08-31',
            'payment_method_id' => $method->id,
            'type' => 'dp_apply',
            'amount' => 1000,
        ]);

        $order = $order->fresh();
        $order->payment_status = 'paid';
        $order->save();
        $order = $order->fresh();
        $this->assertSame('fully_received', $order->received_status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(0.0, $order->accountsPayableOutstanding());
        $order->evaluateAutoClose();

        $this->assertSame('closed', $order->fresh()->status);
    }

    public function test_supplier_invoice_uses_the_same_rounding_tolerance(): void
    {
        $supplier = Supplier::create([
            'code' => 'TOL-INV-SUP-' . uniqid(),
            'name' => 'Tolerance Invoice Supplier',
        ]);
        $invoice = SupplierInvoice::create([
            'invoice_no' => 'TOL-INV-' . uniqid(),
            'supplier_id' => $supplier->id,
            'invoice_date' => '2026-08-27',
            'total_amount' => 1000,
            'paid_amount' => 999,
            'status' => 'posted',
        ]);

        $invoice->syncPaymentStatus();
        $invoice->refresh();

        $this->assertSame(0.0, $invoice->outstanding());
        $this->assertSame('paid', $invoice->status);
    }
}
