<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderPaymentUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_tambah_dp_after_a_dp_has_been_recorded(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-PO-PAY-UI-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-PAY-UI-' . uniqid(),
            'name' => 'Supplier Payment UI',
        ]);
        $paymentMethod = PaymentMethod::create([
            'code' => 'UI-CASH-' . uniqid(),
            'name' => 'Cash UI Test',
            'mode' => 'cash',
            'is_active' => true,
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-PAY-UI-' . uniqid(),
            'date' => '2026-08-31',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'draft',
        ]);
        $item = Item::create([
            'code' => 'ITEM-PO-PAY-UI-' . uniqid(),
            'name' => 'Item Payment UI',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
            'qty' => 1,
            'purchase_unit' => 'pcs',
            'stock_unit' => 'pcs',
            'conversion_factor' => 1,
            'unit_price' => 1000000,
            'discount' => 0,
            'line_total' => 1000000,
        ]);
        PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-08-31',
            'payment_method_id' => $paymentMethod->id,
            'type' => 'dp',
            'amount' => 250000,
        ]);

        $this->actingAs($owner)
            ->get(route('purchasing.purchase_orders.show', $order))
            ->assertOk()
            ->assertSee('Bayar Sekarang')
            ->assertSee('DP sudah tercatat')
            ->assertSee('Rp 250.000')
            ->assertSee('Dana Dibayar')
            ->assertSee('Rp 250.000')
            ->assertDontSee('title="Bayar DP"');
    }

    public function test_owner_sees_warning_when_additional_dp_will_be_supplier_receivable(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-PO-PAY-FULL-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-PAY-FULL-' . uniqid(),
            'name' => 'Supplier Full DP UI',
        ]);
        $paymentMethod = PaymentMethod::create([
            'code' => 'UI-FULL-CASH-' . uniqid(),
            'name' => 'Cash Full DP UI Test',
            'mode' => 'cash',
            'is_active' => true,
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-PAY-FULL-' . uniqid(),
            'date' => '2026-08-31',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'approved',
        ]);
        $item = Item::create([
            'code' => 'ITEM-PO-PAY-FULL-' . uniqid(),
            'name' => 'Item Full DP UI',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
            'qty' => 1,
            'purchase_unit' => 'pcs',
            'stock_unit' => 'pcs',
            'conversion_factor' => 1,
            'unit_price' => 1000000,
            'discount' => 0,
            'line_total' => 1000000,
        ]);
        PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-08-31',
            'payment_method_id' => $paymentMethod->id,
            'type' => 'dp',
            'amount' => 1000000,
        ]);

        $this->actingAs($owner)
            ->get(route('purchasing.purchase_orders.show', $order))
            ->assertOk()
            ->assertSee('DP sudah menutup total PO. Tambahan nominal tetap boleh dicatat, tetapi selisihnya akan menjadi piutang supplier.');
    }

    public function test_owner_sees_unapplied_dp_as_ap_warning_and_offset_primary_action(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-PO-OFFSET-UI-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-OFFSET-UI-' . uniqid(),
            'name' => 'Supplier Offset UI',
        ]);
        $paymentMethod = PaymentMethod::create([
            'code' => 'UI-OFFSET-CASH-' . uniqid(),
            'name' => 'Cash Offset UI Test',
            'mode' => 'cash',
            'is_active' => true,
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-OFFSET-UI-' . uniqid(),
            'date' => '2026-08-31',
            'supplier_id' => $supplier->id,
            'grand_total' => 100000,
            'status' => 'approved',
            'received_status' => 'fully_received',
            'payment_status' => 'paid',
            'paid_amount' => 100000,
        ]);
        PurchaseReceipt::create([
            'code' => 'GRN-OFFSET-UI-' . uniqid(),
            'date' => '2026-08-31',
            'purchase_order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'grand_total' => 100000,
            'status' => 'posted',
            'is_replacement' => false,
        ]);
        PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-08-31',
            'payment_method_id' => $paymentMethod->id,
            'type' => 'dp',
            'amount' => 100000,
        ]);

        $this->actingAs($owner)
            ->get(route('purchasing.purchase_orders.show', $order))
            ->assertOk()
            ->assertSee('MENUNGGU OFFSET DP')
            ->assertSee('DP Belum Dipakai')
            ->assertSee('Hutang AP Tersisa')
            ->assertSee('Offset DP')
            ->assertDontSee('data-bs-target="#modalAddPayment"')
            ->assertDontSee('Bayar PO');
    }

    public function test_admin_can_see_payment_status_but_not_owner_only_payment_action(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-PO-PAY-UI-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-ADMIN-UI-' . uniqid(),
            'name' => 'Supplier Admin Payment UI',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-ADMIN-PAY-UI-' . uniqid(),
            'date' => '2026-08-31',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'approved',
            'payment_status' => 'unpaid',
        ]);
        $item = Item::create([
            'code' => 'ITEM-PO-ADMIN-UI-' . uniqid(),
            'name' => 'Item Admin Payment UI',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $order->id,
            'item_id' => $item->id,
            'qty' => 1,
            'purchase_unit' => 'pcs',
            'stock_unit' => 'pcs',
            'conversion_factor' => 1,
            'unit_price' => 1000000,
            'discount' => 0,
            'line_total' => 1000000,
        ]);

        $this->actingAs($admin)
            ->get(route('purchasing.purchase_orders.show', $order))
            ->assertOk()
            ->assertSee('BELUM BAYAR')
            ->assertDontSee('Bayar DP')
            ->assertDontSee('Tambah DP');
    }

    public function test_owner_can_cancel_approved_po_without_receipt_or_active_payment(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-PO-CANCEL-UI-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-CANCEL-UI-' . uniqid(),
            'name' => 'Supplier Cancel UI',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-CANCEL-UI-' . uniqid(),
            'date' => '2026-08-31',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'approved',
            'received_status' => 'not_received',
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($owner)
            ->get(route('purchasing.purchase_orders.show', $order))
            ->assertOk()
            ->assertSee('Cancel PO')
            ->assertDontSee('Tutup Paksa PO');
    }
}
