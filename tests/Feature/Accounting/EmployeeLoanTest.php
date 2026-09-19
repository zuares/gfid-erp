<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;
use App\Models\User;
use App\Services\Accounting\EmployeeLoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeLoanTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_loan_posting_and_full_repayment_update_the_ledger(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'LOAN-TEST-OWNER']);
        $employee = Employee::create([
            'code' => 'EMP-LOAN-001', 'name' => 'Karyawan Pinjaman', 'role' => 'other',
            'payment_type' => 'fixed', 'active' => true,
        ]);
        $bank = Account::create(['code' => '1199', 'name' => 'Bank Pinjaman Test', 'type' => 'asset', 'is_cash' => true, 'is_active' => true]);
        $receivable = Account::firstOrCreate(['code' => '1307'], ['name' => 'Piutang Pinjaman Karyawan', 'type' => 'asset', 'is_cash' => false, 'is_active' => true]);

        $loan = EmployeeLoan::create([
            'employee_id' => $employee->id, 'date' => '2026-09-19', 'principal_amount' => 1000000,
            'installment_amount' => 500000, 'installment_count' => 2, 'cash_account_id' => $bank->id,
            'receivable_account_id' => $receivable->id, 'status' => 'draft', 'created_by' => $user->id,
        ]);

        $service = app(EmployeeLoanService::class);
        $service->post($loan);

        $this->assertSame('posted', $loan->fresh()->status);
        $this->assertDatabaseHas('journal_lines', ['journal_id' => $loan->fresh()->journal_id, 'account_id' => $receivable->id, 'debit' => 1000000, 'credit' => 0]);
        $this->assertDatabaseHas('journal_lines', ['journal_id' => $loan->fresh()->journal_id, 'account_id' => $bank->id, 'debit' => 0, 'credit' => 1000000]);

        $repayment = EmployeeLoanRepayment::create([
            'employee_loan_id' => $loan->id, 'date' => '2026-10-19', 'amount' => 1000000,
            'cash_account_id' => $bank->id, 'status' => 'draft', 'created_by' => $user->id,
        ]);
        $service->postRepayment($repayment);

        $this->assertSame('settled', $loan->fresh()->status);
        $this->assertSame('posted', $repayment->fresh()->status);
        $this->assertSame(0.0, $loan->fresh()->outstanding_amount);
    }
}
