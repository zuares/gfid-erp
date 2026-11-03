<?php

namespace Tests\Feature\Purchasing;

use App\Models\Account;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Services\Accounting\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierInvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_pay_posted_supplier_invoice_and_sync_ap_status(): void
    {
        [$owner, $invoice, $method, $cash, $ap] = $this->paymentContext();

        $this->actingAs($owner)
            ->post(route('purchasing.supplier_invoices.payments.store', $invoice), [
                'date' => '2026-11-02',
                'payment_method_id' => $method->id,
                'cash_account_id' => $cash->id,
                'amount' => '250.000',
                'ref_no' => 'INV-PAY-001',
            ])
            ->assertRedirect(route('purchasing.supplier_invoices.show', $invoice));

        $payment = PurchasePayment::query()
            ->where('supplier_invoice_id', $invoice->id)
            ->firstOrFail();

        $this->assertSame(250000.0, (float) $payment->amount);
        $this->assertSame($invoice->purchase_order_id, $payment->purchase_order_id);
        $this->assertNotNull($payment->journal_id);
        $this->assertSame(250000.0, (float) $payment->journal->lines()->where('account_id', $ap->id)->sum('debit'));

        $invoice->refresh();
        $this->assertSame('partial_paid', $invoice->status);
        $this->assertSame(250000.0, (float) $invoice->paid_amount);

        $this->actingAs($owner)
            ->get(route('purchasing.supplier_invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Bayar Invoice')
            ->assertSee('Riwayat Pembayaran Invoice')
            ->assertSee('INV-PAY-001');
    }

    public function test_supplier_invoice_payment_cannot_exceed_invoice_or_po_outstanding(): void
    {
        [$owner, $invoice, $method, $cash] = $this->paymentContext();

        $this->actingAs($owner)
            ->post(route('purchasing.supplier_invoices.payments.store', $invoice), [
                'date' => '2026-11-02',
                'payment_method_id' => $method->id,
                'cash_account_id' => $cash->id,
                'amount' => '800.000',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('purchase_payments', 0);
        $this->assertDatabaseMissing('journals', ['source_type' => JournalService::SRC_PURCHASE_PAYMENT]);
    }

    private function paymentContext(): array
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-SUPPLIER-INVOICE-PAY-'.uniqid(),
        ]);
        $ap = Account::firstOrCreate(['code' => JournalService::CODE_AP], [
            'code' => JournalService::CODE_AP,
            'name' => 'Hutang Dagang',
            'type' => 'liability',
            'is_active' => true,
        ]);
        Account::firstOrCreate(['code' => JournalService::CODE_ADV_PURCHASE], [
            'code' => JournalService::CODE_ADV_PURCHASE,
            'name' => 'Uang Muka Pembelian',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $cash = Account::firstOrCreate(['code' => '1101'], [
            'code' => '1101',
            'name' => 'Kas Tunai',
            'type' => 'asset',
            'is_cash' => true,
            'is_active' => true,
        ]);
        $method = PaymentMethod::create([
            'code' => 'TEST-SUPPLIER-INVOICE-CASH-'.uniqid(),
            'name' => 'Tunai',
            'mode' => 'cash',
            'is_active' => true,
        ]);
        $supplier = Supplier::create([
            'code' => 'SUPPLIER-INVOICE-PAY-'.uniqid(),
            'name' => 'Supplier Invoice Payment Test',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-SUPPLIER-INVOICE-PAY-'.uniqid(),
            'date' => '2026-11-01',
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'approved',
        ]);
        PurchaseReceipt::create([
            'code' => 'GRN-SUPPLIER-INVOICE-PAY-'.uniqid(),
            'date' => '2026-11-01',
            'purchase_order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'grand_total' => 1000000,
            'status' => 'posted',
            'is_replacement' => false,
        ]);
        $invoice = SupplierInvoice::create([
            'invoice_no' => 'SI-SUPPLIER-INVOICE-PAY-'.uniqid(),
            'supplier_invoice_ref' => 'SUP-REF-001',
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $order->id,
            'invoice_date' => '2026-11-01',
            'subtotal' => 750000,
            'discount_amount' => 0,
            'return_deduction_amount' => 0,
            'total_amount' => 750000,
            'paid_amount' => 0,
            'status' => 'posted',
            'posted_at' => now(),
            'created_by' => $owner->id,
        ]);

        return [$owner, $invoice, $method, $cash, $ap];
    }
}
