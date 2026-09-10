<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Accounting\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderPaymentUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_the_direct_payment_url(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-PO-PAY-REDIRECT-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-PAY-REDIRECT-' . uniqid(),
            'name' => 'Supplier Payment Redirect',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-PAY-REDIRECT-' . uniqid(),
            'date' => '2026-08-31',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'approved',
        ]);

        $this->actingAs($owner)
            ->get(route('purchasing.purchase_orders.payments.index', $order))
            ->assertRedirect(route('purchasing.purchase_orders.show', $order) . '#payments');
    }

    public function test_owner_can_pay_a_posted_grn_and_payment_is_allocated_to_it(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-GRN-PAY-' . uniqid(),
        ]);
        $ap = Account::create([
            'code' => JournalService::CODE_AP,
            'name' => 'Hutang Dagang',
            'type' => 'liability',
            'is_active' => true,
        ]);
        Account::create([
            'code' => JournalService::CODE_ADV_PURCHASE,
            'name' => 'Uang Muka Pembelian',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $cash = Account::create([
            'code' => '1101',
            'name' => 'Kas',
            'type' => 'asset',
            'is_cash' => true,
            'is_active' => true,
        ]);
        $method = PaymentMethod::create([
            'code' => 'GRN-CASH-' . uniqid(),
            'name' => 'Cash GRN Test',
            'mode' => 'cash',
            'is_active' => true,
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-GRN-PAY-' . uniqid(),
            'name' => 'Supplier GRN Payment',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-GRN-PAY-' . uniqid(),
            'date' => '2026-09-11',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'approved',
        ]);
        $receipt = PurchaseReceipt::create([
            'code' => 'GRN-GRN-PAY-' . uniqid(),
            'date' => '2026-09-11',
            'purchase_order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'posted',
            'is_replacement' => false,
        ]);

        $this->actingAs($owner)
            ->post(route('purchasing.purchase_receipts.payments.store', $receipt), [
                'date' => '2026-09-11',
                'payment_method_id' => $method->id,
                'cash_account_id' => $cash->id,
                'amount' => '250.000',
                'ref_no' => 'GRN-PAY-001',
            ])
            ->assertRedirect();

        $payment = PurchasePayment::query()->where('purchase_receipt_id', $receipt->id)->firstOrFail();
        $this->assertSame('payment', $payment->type);
        $this->assertSame(250000.0, (float) $payment->amount);
        $this->assertNotNull($payment->journal_id);
        $this->assertSame(250000.0, (float) $payment->journal->lines()->where('account_id', $ap->id)->sum('debit'));

        $this->actingAs($owner)
            ->get(route('purchasing.purchase_receipts.show', $receipt))
            ->assertOk()
            ->assertSee('Pembayaran GRN')
            ->assertSee('Bayar GRN')
            ->assertSee('GRN-PAY-001');
    }

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

    public function test_owner_can_edit_approved_po_when_only_voided_payment_and_stale_grn_lock_remain(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-PO-EDIT-UI-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-EDIT-UI-' . uniqid(),
            'name' => 'Supplier Edit UI',
        ]);
        $paymentMethod = PaymentMethod::create([
            'code' => 'UI-EDIT-CASH-' . uniqid(),
            'name' => 'Cash Edit UI Test',
            'mode' => 'cash',
            'is_active' => true,
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-EDIT-UI-' . uniqid(),
            'date' => '2026-08-31',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'approved',
            'received_status' => 'not_received',
            'payment_status' => 'unpaid',
            'locked_at' => now(),
            'lock_reason' => 'Dikunci oleh GRN-GHOST-001.',
            'first_grn_id' => 999999,
        ]);
        PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-08-31',
            'payment_method_id' => $paymentMethod->id,
            'type' => 'dp',
            'amount' => 250000,
            'voided_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('purchasing.purchase_orders.show', $order))
            ->assertOk()
            ->assertSee('Edit PO')
            ->assertDontSee('Locked');

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'locked_at' => null,
            'first_grn_id' => null,
        ]);

        $this->actingAs($owner)
            ->get(route('purchasing.purchase_orders.edit', $order))
            ->assertOk();
    }

    public function test_owner_can_pay_again_after_the_last_payment_is_voided(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-PO-REPAY-UI-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-REPAY-UI-' . uniqid(),
            'name' => 'Supplier Repay UI',
        ]);
        $paymentMethod = PaymentMethod::create([
            'code' => 'UI-REPAY-CASH-' . uniqid(),
            'name' => 'Cash Repay UI Test',
            'mode' => 'cash',
            'is_active' => true,
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-REPAY-UI-' . uniqid(),
            'date' => '2026-08-31',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'approved',
            'payment_status' => 'paid',
            'paid_amount' => 1000000,
        ]);
        PurchasePayment::create([
            'purchase_order_id' => $order->id,
            'date' => '2026-08-31',
            'payment_method_id' => $paymentMethod->id,
            'type' => 'dp',
            'amount' => 1000000,
            'voided_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('purchasing.purchase_orders.show', $order))
            ->assertOk()
            ->assertSee('Bayar Sekarang', false);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
        ]);
    }
}
