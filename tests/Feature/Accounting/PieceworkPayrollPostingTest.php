<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;
use App\Models\EmployeeSavingsTransaction;
use App\Models\Journal;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use App\Services\Payroll\PieceworkPayrollPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PieceworkPayrollPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalize_and_pay_persist_accounting_markers(): void
    {
        [$period, $bank] = $this->makePayroll();

        $service = app(PieceworkPayrollPostingService::class);
        $service->finalize($period);
        $service->pay($period, $bank->id);

        $period = $period->fresh();

        $this->assertSame('final', $period->status);
        $this->assertNotNull($period->finalized_at);
        $this->assertNotNull($period->accrual_journal_id);
        $this->assertNotNull($period->payment_journal_id);
        $this->assertNotNull($period->paid_at);
        $this->assertSame($bank->id, (int) $period->paid_from_account_id);
        $this->assertDatabaseHas('journals', [
            'id' => $period->payment_journal_id,
            'source_type' => 'piecework_payroll_period_payment',
            'source_id' => $period->id,
            'voided_at' => null,
        ]);
    }

    public function test_existing_payment_journal_repairs_missing_markers_without_duplicate(): void
    {
        [$period, $bank] = $this->makePayroll();

        $service = app(PieceworkPayrollPostingService::class);
        $service->finalize($period);
        $service->pay($period, $bank->id);

        $paymentJournalId = $period->fresh()->payment_journal_id;
        $journalCount = Journal::count();

        // Simulasikan data lama yang sudah punya jurnal, tetapi marker period
        // belum tersimpan karena mass assignment versi sebelumnya.
        $period->forceFill([
            'payment_journal_id' => null,
            'paid_at' => null,
            'paid_from_account_id' => null,
        ])->save();

        $service->pay($period->fresh(), $bank->id);

        $repaired = $period->fresh();
        $this->assertSame($paymentJournalId, $repaired->payment_journal_id);
        $this->assertNotNull($repaired->paid_at);
        $this->assertSame($bank->id, (int) $repaired->paid_from_account_id);
        $this->assertSame($journalCount, Journal::count());
    }

    public function test_payroll_payment_can_deduct_employee_loan_and_pay_net_salary(): void
    {
        [$period, $bank] = $this->makePayroll();
        $employee = Employee::where('code', 'EMP-PAYROLL-TEST')->firstOrFail();
        $receivable = Account::firstOrCreate(['code' => '1307'], [
            'name' => 'Piutang Pinjaman Karyawan',
            'type' => 'asset',
            'is_cash' => false,
            'is_active' => true,
        ]);
        $loan = EmployeeLoan::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-01',
            'principal_amount' => 8000,
            'installment_amount' => 4000,
            'cash_account_id' => $bank->id,
            'receivable_account_id' => $receivable->id,
            'status' => 'posted',
        ]);

        $service = app(PieceworkPayrollPostingService::class);
        $service->finalize($period);
        $service->pay($period, $bank->id, [$loan->id => 4000]);

        $payment = Journal::where('source_type', 'piecework_payroll_period_payment')
            ->where('source_id', $period->id)
            ->firstOrFail();
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $payment->id,
            'account_id' => $bank->id,
            'debit' => 0,
            'credit' => 6000,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $payment->id,
            'account_id' => $receivable->id,
            'debit' => 0,
            'credit' => 4000,
        ]);
        $this->assertDatabaseHas('employee_loan_repayments', [
            'employee_loan_id' => $loan->id,
            'amount' => 4000,
            'status' => 'posted',
            'source_type' => EmployeeLoanRepayment::SOURCE_PAYROLL_DEDUCTION,
            'source_id' => $period->id,
            'journal_id' => $payment->id,
        ]);
        $this->assertSame(4000.0, $loan->fresh()->outstanding_amount);
    }

    public function test_daily_payroll_is_accrued_to_operating_payroll_expense(): void
    {
        $wip = Account::firstOrCreate(['code' => '1202'], [
            'code' => '1202',
            'name' => 'Persediaan WIP',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $payrollExpense = Account::firstOrCreate(['code' => '6103'], [
            'code' => '6103',
            'name' => 'Biaya Gaji Operasional',
            'type' => 'expense',
            'is_active' => true,
        ]);
        $payable = Account::firstOrCreate(['code' => '2102'], [
            'code' => '2102',
            'name' => 'Hutang Upah Borongan',
            'type' => 'liability',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'code' => 'EMP-DAILY-POSTING-TEST',
            'name' => 'Payroll Harian Test',
            'role' => 'operating',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $period = PieceworkPayrollPeriod::create([
            'module' => 'daily',
            'period_start' => '2026-08-31',
            'period_end' => '2026-09-06',
            'status' => 'draft',
            'total_amount' => 700000,
        ]);
        PieceworkPayrollLine::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'total_qty_ok' => 7,
            'rate_per_day' => 100000,
            'amount' => 700000,
        ]);

        app(PieceworkPayrollPostingService::class)->finalize($period);

        $journal = Journal::where('source_type', 'piecework_payroll_period_accrual')
            ->where('source_id', $period->id)
            ->firstOrFail();
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $payrollExpense->id,
            'debit' => 700000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $payable->id,
            'debit' => 0,
            'credit' => 700000,
        ]);
        $this->assertDatabaseMissing('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $wip->id,
            'debit' => 700000,
            'credit' => 0,
        ]);
    }

    public function test_daily_attendance_bonus_defaults_to_employee_savings(): void
    {
        $payrollExpense = Account::firstOrCreate(['code' => '6103'], [
            'name' => 'Biaya Gaji Operasional',
            'type' => 'expense',
            'is_active' => true,
        ]);
        $payable = Account::firstOrCreate(['code' => '2102'], [
            'name' => 'Hutang Upah Borongan',
            'type' => 'liability',
            'is_active' => true,
        ]);
        $savings = Account::where('code', '2104')->firstOrFail();
        $bank = Account::create([
            'code' => '1102',
            'name' => 'Bank Bonus Test',
            'type' => 'asset',
            'is_cash' => true,
            'is_active' => true,
        ]);
        $employee = Employee::create([
            'code' => 'EMP-DAILY-BONUS-TEST',
            'name' => 'Payroll Bonus Test',
            'role' => 'operating',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $period = PieceworkPayrollPeriod::create([
            'module' => 'daily',
            'period_start' => '2026-08-31',
            'period_end' => '2026-09-06',
            'status' => 'draft',
            'total_amount' => 0,
        ]);

        foreach (range(0, 5) as $day) {
            PieceworkPayrollLine::create([
                'payroll_period_id' => $period->id,
                'employee_id' => $employee->id,
                'work_date' => date('Y-m-d', strtotime("2026-08-31 +{$day} days")),
                'attendance_status' => 'hadir',
                'attendance_factor' => 1,
                'rate_per_day' => 100000,
                'amount' => 100000,
            ]);
        }
        PieceworkPayrollLine::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'work_date' => '2026-09-06',
            'attendance_status' => 'libur',
            'attendance_factor' => 0,
            'rate_per_day' => 100000,
            'amount' => 0,
        ]);

        $service = app(PieceworkPayrollPostingService::class);
        $service->finalize($period);
        $service->pay($period, $bank->id);

        $payment = Journal::where('source_type', 'piecework_payroll_period_payment')
            ->where('source_id', $period->id)
            ->firstOrFail();
        $this->assertSame(660000.0, (float) $period->fresh()->total_amount);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $payment->id,
            'account_id' => $bank->id,
            'debit' => 0,
            'credit' => 600000,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $payment->id,
            'account_id' => $savings->id,
            'debit' => 0,
            'credit' => 60000,
        ]);
        $this->assertDatabaseHas('employee_savings_transactions', [
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'amount' => 60000,
            'type' => EmployeeSavingsTransaction::TYPE_DEPOSIT,
            'source_type' => EmployeeSavingsTransaction::SOURCE_PAYROLL_BONUS,
            'journal_id' => $payment->id,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $payment->id,
            'account_id' => $payable->id,
            'debit' => 660000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $period->fresh()->accrual_journal_id,
            'account_id' => $payrollExpense->id,
            'debit' => 660000,
            'credit' => 0,
        ]);
    }

    private function makePayroll(): array
    {
        $wip = Account::firstOrCreate(['code' => '1202'], [
            'name' => 'Persediaan WIP',
            'type' => 'asset',
            'is_active' => true,
        ]);
        $bank = Account::create([
            'code' => '1101',
            'name' => 'Bank Test',
            'type' => 'asset',
            'is_cash' => true,
            'is_active' => true,
        ]);
        Account::firstOrCreate(['code' => '2102'], [
            'code' => '2102',
            'name' => 'Hutang Upah Borongan',
            'type' => 'liability',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'code' => 'EMP-PAYROLL-TEST',
            'name' => 'Payroll Test',
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
        ]);

        $period = PieceworkPayrollPeriod::create([
            'module' => 'cutting',
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-07',
            'status' => 'draft',
        ]);

        PieceworkPayrollLine::create([
            'payroll_period_id' => $period->id,
            'employee_id' => $employee->id,
            'total_qty_ok' => 10,
            'rate_per_pcs' => 1000,
            'amount' => 10000,
        ]);

        return [$period, $bank, $wip];
    }
}
