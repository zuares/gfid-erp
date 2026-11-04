<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\StandalonePayrollPostingService;
use App\Services\Payroll\PieceworkPayrollPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollStandaloneEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_payroll_reconciles_gross_loan_savings_and_cash_in_both_ledgers(): void
    {
        $bank = Account::firstOrCreate(['code' => '1101'], [
            'name' => 'Kas Uji', 'type' => 'asset', 'is_cash' => true, 'is_active' => true,
        ]);
        foreach (['2102' => 'liability', '2104' => 'liability', '6103' => 'expense', '1308' => 'asset'] as $code => $type) {
            Account::firstOrCreate(['code' => $code], [
                'name' => $code, 'type' => $type, 'is_cash' => false, 'is_active' => true,
            ]);
        }
        foreach (['1101' => '1-120', '1308' => '1-135', '2102' => '2-130', '2104' => '2-140', '6103' => '6-180'] as $legacy => $standalone) {
            StandaloneAccountingAccountMapping::updateOrCreate(['legacy_account_code' => $legacy], [
                'standalone_account_code' => $standalone, 'purpose' => 'Fixture payroll end-to-end', 'is_active' => true,
            ]);
        }

        $employee = Employee::create([
            'code' => 'E2E-PAYROLL', 'name' => 'Operator E2E', 'role' => 'operating',
            'payment_type' => 'variable', 'active' => true,
        ]);
        $period = PieceworkPayrollPeriod::create([
            'module' => 'daily', 'period_start' => '2026-11-01', 'period_end' => '2026-11-07', 'status' => 'draft',
        ]);
        foreach (range(1, 6) as $day) {
            PieceworkPayrollLine::create([
                'payroll_period_id' => $period->id, 'employee_id' => $employee->id,
                'work_date' => "2026-11-0{$day}", 'attendance_status' => 'hadir', 'attendance_factor' => 1,
                'rate_per_day' => 100000, 'amount' => 100000,
            ]);
        }
        $loan = EmployeeLoan::create([
            'employee_id' => $employee->id, 'date' => '2026-10-01', 'principal_amount' => 100000,
            'cash_account_id' => $bank->id, 'receivable_account_id' => Account::where('code', '1308')->value('id'),
            'status' => 'posted',
        ]);

        $posting = app(PieceworkPayrollPostingService::class);
        $posting->finalize($period);
        $posting->pay($period, $bank->id, [$loan->id => 100000]);

        $mirror = StandaloneAccountingJournal::query()
            ->where('source_type', StandalonePayrollPostingService::PAYMENT)
            ->where('source_id', $period->id)
            ->firstOrFail();
        $this->assertSame(660000.0, (float) $period->fresh()->total_amount);
        $this->assertLine($mirror, '2-130', 660000, 0);
        $this->assertLine($mirror, '1-135', 0, 100000);
        $this->assertLine($mirror, '2-140', 0, 60000);
        $this->assertLine($mirror, '1-120', 0, 500000);

        $status = app(StandalonePayrollPostingService::class)->status($period->fresh());
        $this->assertSame(660000.0, $status['gross']);
        $this->assertSame(100000.0, $status['loans']);
        $this->assertSame(60000.0, $status['savings']);
        $this->assertSame(500000.0, $status['cash']);
        $this->assertSame(0.0, $status['difference']);
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
