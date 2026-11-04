<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;
use App\Models\EmployeeSavingsTransaction;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\EmployeeLoanService;
use App\Services\Accounting\EmployeeSavingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandaloneEmployeeBalancePostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_loan_disbursement_is_mirrored_and_voided_in_new_accounting(): void
    {
        [$employee, $bank, $receivable] = $this->fixture();
        $loan = EmployeeLoan::create([
            'employee_id' => $employee->id, 'date' => '2026-11-04', 'principal_amount' => 150000,
            'cash_account_id' => $bank->id, 'receivable_account_id' => $receivable->id,
            'reference' => 'LOAN-SA-001', 'status' => 'draft',
        ]);

        app(EmployeeLoanService::class)->post($loan);
        $mirror = StandaloneAccountingJournal::where('source_type', 'employee_loan')->where('source_id', $loan->id)->firstOrFail();
        $this->assertLine($mirror, '1-135', 150000, 0);
        $this->assertLine($mirror, '1-120', 0, 150000);

        app(EmployeeLoanService::class)->void($loan->fresh(), 'Uji VOID');
        $this->assertNotNull($mirror->fresh()->voided_at);
        $this->assertDatabaseHas('standalone_accounting_journals', [
            'source_type' => 'employee_loan_reversal_'.$mirror->id,
            'source_id' => $loan->id,
        ]);
    }

    public function test_direct_loan_repayment_is_mirrored_and_voided_in_new_accounting(): void
    {
        [$employee, $bank, $receivable] = $this->fixture();
        $loan = EmployeeLoan::create([
            'employee_id' => $employee->id, 'date' => '2026-11-04', 'principal_amount' => 150000,
            'cash_account_id' => $bank->id, 'receivable_account_id' => $receivable->id,
            'reference' => 'LOAN-SA-002', 'status' => 'draft',
        ]);
        $service = app(EmployeeLoanService::class);
        $service->post($loan);
        $repayment = EmployeeLoanRepayment::create([
            'employee_loan_id' => $loan->id, 'date' => '2026-11-05', 'amount' => 50000,
            'cash_account_id' => $bank->id, 'reference' => 'REPAY-SA-001', 'status' => 'draft',
        ]);

        $service->postRepayment($repayment);
        $mirror = StandaloneAccountingJournal::where('source_type', 'employee_loan_repayment')->where('source_id', $repayment->id)->firstOrFail();
        $this->assertLine($mirror, '1-120', 50000, 0);
        $this->assertLine($mirror, '1-135', 0, 50000);

        $service->voidRepayment($repayment->fresh(), 'Uji VOID');
        $this->assertNotNull($mirror->fresh()->voided_at);
    }

    public function test_direct_savings_withdrawal_is_mirrored_and_voided_in_new_accounting(): void
    {
        [$employee, $bank] = $this->fixture();
        EmployeeSavingsTransaction::create([
            'employee_id' => $employee->id, 'date' => '2026-11-01', 'amount' => 90000,
            'type' => EmployeeSavingsTransaction::TYPE_DEPOSIT, 'status' => 'posted',
        ]);
        $payment = EmployeeSavingsTransaction::create([
            'employee_id' => $employee->id, 'date' => '2026-11-05', 'amount' => 50000,
            'type' => EmployeeSavingsTransaction::TYPE_PAYMENT, 'cash_account_id' => $bank->id,
            'reference' => 'SAV-SA-001', 'status' => 'draft',
        ]);

        $service = app(EmployeeSavingsService::class);
        $service->post($payment);
        $mirror = StandaloneAccountingJournal::where('source_type', 'employee_savings_payment')->where('source_id', $payment->id)->firstOrFail();
        $this->assertLine($mirror, '2-140', 50000, 0);
        $this->assertLine($mirror, '1-120', 0, 50000);

        $service->void($payment->fresh(), 'Uji VOID');
        $this->assertNotNull($mirror->fresh()->voided_at);
    }

    private function fixture(): array
    {
        $employee = Employee::create([
            'code' => 'EMP-SA-001', 'name' => 'Karyawan Integrasi', 'role' => 'other',
            'payment_type' => 'fixed', 'active' => true,
        ]);
        $bank = Account::firstOrCreate(['code' => '1101'], [
            'name' => 'Kas Uji', 'type' => 'asset', 'is_cash' => true, 'is_active' => true,
        ]);
        $receivable = Account::firstOrCreate(['code' => '1308'], [
            'name' => 'Piutang Pinjaman Karyawan', 'type' => 'asset', 'is_cash' => false, 'is_active' => true,
        ]);
        Account::firstOrCreate(['code' => '2104'], [
            'name' => 'Tabungan Karyawan', 'type' => 'liability', 'is_cash' => false, 'is_active' => true,
        ]);
        foreach (['1101' => '1-120', '1308' => '1-135', '2104' => '2-140'] as $legacy => $standalone) {
            StandaloneAccountingAccountMapping::updateOrCreate(['legacy_account_code' => $legacy], [
                'standalone_account_code' => $standalone, 'purpose' => 'Fixture saldo karyawan', 'is_active' => true,
            ]);
        }

        return [$employee, $bank, $receivable];
    }

    private function assertLine(StandaloneAccountingJournal $journal, string $code, float $debit, float $credit): void
    {
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => StandaloneAccountingAccount::where('code', $code)->value('id'),
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }
}
