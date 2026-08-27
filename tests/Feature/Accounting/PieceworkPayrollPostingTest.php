<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Employee;
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
