<?php

namespace Tests\Feature\Purchasing;

use App\Models\Account;
use App\Models\Journal;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\SupplierLoan;
use App\Services\Accounting\SupplierLoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierLoanAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_supplier_loan_can_be_allocated_to_the_same_supplier_po(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'SUP-ALLOC-UI',
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-ALLOC',
            'name' => 'Supplier Alokasi',
            'active' => true,
        ]);
        $bank = Account::create([
            'code' => '1111',
            'name' => 'Bank Alokasi',
            'type' => 'asset',
            'is_cash' => true,
            'is_active' => true,
        ]);
        $receivable = Account::firstOrCreate(
            ['code' => '1306'],
            ['name' => 'Piutang Pinjaman Supplier', 'type' => 'asset', 'is_cash' => false, 'is_active' => true],
        );
        Account::firstOrCreate(
            ['code' => '1151'],
            ['name' => 'Uang Muka Pembelian', 'type' => 'asset', 'is_cash' => false, 'is_active' => true],
        );
        Account::firstOrCreate(
            ['code' => '2101'],
            ['name' => 'Hutang Dagang', 'type' => 'liability', 'is_cash' => false, 'is_active' => true],
        );
        PaymentMethod::firstOrCreate(
            ['code' => 'DP_APPLY'],
            [
                'name' => 'Offset DP',
                'mode' => 'credit',
                'description' => 'Offset uang muka ke hutang supplier',
                'sort_order' => 90,
                'is_active' => true,
            ],
        );
        $loan = SupplierLoan::create([
            'supplier_id' => $supplier->id,
            'date' => '2026-09-08',
            'principal_amount' => 1000000,
            'cash_account_id' => $bank->id,
            'receivable_account_id' => $receivable->id,
            'status' => 'draft',
        ]);
        app(SupplierLoanService::class)->post($loan);

        $order = PurchaseOrder::create([
            'code' => 'PO-ALLOC-001',
            'date' => '2026-09-08',
            'supplier_id' => $supplier->id,
            'grand_total' => 800000,
            'status' => 'approved',
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
        ]);

        $response = $this->actingAs($user)->post(
            route('purchasing.purchase_orders.payments.apply_supplier_loan', $order),
            [
                'date' => '2026-09-08',
                'supplier_loan_id' => $loan->id,
                'amount' => '400000',
                'notes' => 'Alokasi sebagian',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('purchase_payments', [
            'purchase_order_id' => $order->id,
            'supplier_loan_id' => $loan->id,
            'type' => 'loan_apply',
            'amount' => 400000,
        ]);
        $this->assertDatabaseHas('journals', [
            'source_type' => 'supplier_loan_apply',
        ]);
        $journal = Journal::where('source_type', 'supplier_loan_apply')->firstOrFail();
        $advanceAccount = Account::where('code', '1151')->firstOrFail();
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $advanceAccount->id,
            'debit' => 400000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $receivable->id,
            'debit' => 0,
            'credit' => 400000,
        ]);

        $this->assertSame('partial', $order->fresh()->payment_status);
        $this->assertSame(400000.0, (float) $order->fresh()->paid_amount);
        $this->assertSame(600000.0, $loan->fresh()->allocation_available_amount);

        // Setelah GRN posted, alokasi pinjaman harus bisa di-offset ke AP.
        PurchaseReceipt::create([
            'code' => 'GRN-ALLOC-001',
            'date' => '2026-09-08',
            'purchase_order_id' => $order->id,
            'supplier_id' => $supplier->id,
            'subtotal' => 400000,
            'grand_total' => 400000,
            'status' => 'posted',
        ]);

        $offsetResponse = $this->actingAs($user)->post(
            route('purchasing.purchase_orders.payments.apply_dp', $order),
            [
                'date' => '2026-09-08',
                'amount' => '400000',
                'notes' => 'Offset alokasi pinjaman ke AP',
            ],
        );

        $offsetResponse->assertRedirect();
        $offsetResponse->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('purchase_payments', [
            'purchase_order_id' => $order->id,
            'type' => 'dp_apply',
            'amount' => 400000,
        ]);
        $apAccount = Account::where('code', '2101')->firstOrFail();
        $this->assertDatabaseHas('journal_lines', [
            'account_id' => $apAccount->id,
            'debit' => 400000,
            'credit' => 0,
        ]);

        // Alokasi kedua menghabiskan sisa nilai PO. Record payment yang sedang
        // diposting tidak boleh dihitung dua kali oleh JournalService.
        $secondResponse = $this->actingAs($user)->post(
            route('purchasing.purchase_orders.payments.apply_supplier_loan', $order),
            [
                'date' => '2026-09-08',
                'supplier_loan_id' => $loan->id,
                'amount' => '400000',
                'notes' => 'Alokasi kedua',
            ],
        );

        $secondResponse->assertRedirect();
        $secondResponse->assertSessionDoesntHaveErrors();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(800000.0, (float) $order->fresh()->paid_amount);
        $this->assertSame(200000.0, $loan->fresh()->allocation_available_amount);
    }

    public function test_supplier_loan_allocation_rejects_a_different_supplier(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'SUP-ALLOC-UI-2',
        ]);
        $supplierA = Supplier::create(['code' => 'SUP-ALLOC-A', 'name' => 'Supplier A', 'active' => true]);
        $supplierB = Supplier::create(['code' => 'SUP-ALLOC-B', 'name' => 'Supplier B', 'active' => true]);
        $bank = Account::create([
            'code' => '1112',
            'name' => 'Bank Alokasi 2',
            'type' => 'asset',
            'is_cash' => true,
            'is_active' => true,
        ]);
        $receivable = Account::firstOrCreate(
            ['code' => '1306'],
            ['name' => 'Piutang Pinjaman Supplier', 'type' => 'asset', 'is_cash' => false, 'is_active' => true],
        );
        $loan = SupplierLoan::create([
            'supplier_id' => $supplierA->id,
            'date' => '2026-09-08',
            'principal_amount' => 500000,
            'cash_account_id' => $bank->id,
            'receivable_account_id' => $receivable->id,
            'status' => 'draft',
        ]);
        app(SupplierLoanService::class)->post($loan);

        $order = PurchaseOrder::create([
            'code' => 'PO-ALLOC-002',
            'date' => '2026-09-08',
            'supplier_id' => $supplierB->id,
            'grand_total' => 500000,
            'status' => 'approved',
            'payment_status' => 'unpaid',
            'paid_amount' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('purchasing.purchase_orders.payments.apply_supplier_loan', $order), [
                'date' => '2026-09-08',
                'supplier_loan_id' => $loan->id,
                'amount' => '100000',
            ])
            ->assertSessionHasErrors('supplier_loan_id');

        $this->assertSame(0, PurchasePayment::where('type', 'loan_apply')->count());
    }
}
