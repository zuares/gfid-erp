<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\Supplier;
use App\Models\SupplierLoan;
use App\Models\User;
use App\Services\Accounting\SupplierLoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SupplierLoanTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_loan_posts_receivable_against_cash_without_touching_expense(): void
    {
        [$bank, $receivable] = $this->accounts();
        $supplier = Supplier::create([
            'code' => 'SUP-LOAN-1',
            'name' => 'Supplier Uji',
            'active' => true,
        ]);
        $loan = SupplierLoan::create([
            'supplier_id' => $supplier->id,
            'date' => '2026-09-08',
            'principal_amount' => 1500000,
            'cash_account_id' => $bank->id,
            'receivable_account_id' => $receivable->id,
            'description' => 'Pinjaman bahan baku',
            'status' => 'draft',
        ]);

        app(SupplierLoanService::class)->post($loan);

        $journal = Journal::where('source_type', 'supplier_loan')->firstOrFail();
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $receivable->id,
            'debit' => 1500000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $bank->id,
            'debit' => 0,
            'credit' => 1500000,
        ]);
        $this->assertSame('posted', $loan->fresh()->status);
    }

    public function test_supplier_repayment_reduces_outstanding_and_marks_loan_settled(): void
    {
        [$bank, $receivable] = $this->accounts();
        $supplier = Supplier::create(['code' => 'SUP-LOAN-2', 'name' => 'Supplier Uji 2', 'active' => true]);
        $loan = SupplierLoan::create([
            'supplier_id' => $supplier->id,
            'date' => '2026-09-08',
            'principal_amount' => 750000,
            'cash_account_id' => $bank->id,
            'receivable_account_id' => $receivable->id,
            'status' => 'draft',
        ]);
        $service = app(SupplierLoanService::class);
        $service->post($loan);

        $repayment = $loan->repayments()->create([
            'date' => '2026-09-09',
            'amount' => 750000,
            'cash_account_id' => $bank->id,
            'status' => 'draft',
        ]);
        $service->postRepayment($repayment);

        $this->assertSame('posted', $repayment->fresh()->status);
        $this->assertSame('settled', $loan->fresh()->status);
        $this->assertSame(0.0, $loan->fresh()->outstanding_amount);
        $this->assertDatabaseHas('journals', ['source_type' => 'supplier_loan_repayment', 'source_id' => $repayment->id]);
    }

    public function test_supplier_repayment_cannot_exceed_outstanding_balance(): void
    {
        [$bank, $receivable] = $this->accounts();
        $supplier = Supplier::create(['code' => 'SUP-LOAN-3', 'name' => 'Supplier Uji 3', 'active' => true]);
        $loan = SupplierLoan::create([
            'supplier_id' => $supplier->id,
            'date' => '2026-09-08',
            'principal_amount' => 100000,
            'cash_account_id' => $bank->id,
            'receivable_account_id' => $receivable->id,
            'status' => 'draft',
        ]);
        $service = app(SupplierLoanService::class);
        $service->post($loan);
        $repayment = $loan->repayments()->create([
            'date' => '2026-09-09',
            'amount' => 100001,
            'cash_account_id' => $bank->id,
            'status' => 'draft',
        ]);

        $this->expectException(ValidationException::class);
        $service->postRepayment($repayment);
    }

    public function test_owner_can_open_supplier_loan_pages(): void
    {
        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'SUP-LOAN-UI',
        ]);

        $this->actingAs($user)
            ->get(route('accounting.supplier-loans.index'))
            ->assertOk()
            ->assertSee('Dana Supplier');

        $this->actingAs($user)
            ->get(route('accounting.supplier-loans.create'))
            ->assertOk()
            ->assertSee('Tambah Dana Supplier');
    }

    private function accounts(): array
    {
        return [
            Account::create(['code' => '1111', 'name' => 'Bank Uji', 'type' => 'asset', 'is_cash' => true, 'is_active' => true]),
            Account::firstOrCreate(
                ['code' => '1306'],
                ['name' => 'Piutang Pinjaman Supplier', 'type' => 'asset', 'is_cash' => false, 'is_active' => true],
            ),
        ];
    }
}
