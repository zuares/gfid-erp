<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizeEmployeeLoanReceivablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reclasses_legacy_employee_loans_and_backfills_their_mirrors(): void
    {
        $employee = Employee::create([
            'code' => 'EMP-NORMALIZE-LOAN', 'name' => 'Karyawan Lama', 'role' => 'other',
            'payment_type' => 'fixed', 'active' => true,
        ]);
        $cash = Account::firstOrCreate(['code' => '1101'], [
            'name' => 'Kas', 'type' => 'asset', 'is_cash' => true, 'is_active' => true,
        ]);
        $oldReceivable = Account::create([
            'code' => '1307', 'name' => 'Akun Lama Salah', 'type' => 'asset', 'is_cash' => false, 'is_active' => true,
        ]);
        $canonical = Account::firstOrCreate(['code' => '1308'], [
            'name' => 'Piutang Pinjaman Karyawan', 'type' => 'asset', 'is_cash' => false, 'is_active' => true,
        ]);
        foreach (['1101' => '1-120', '1307' => '1-130', '1308' => '1-135'] as $legacy => $standalone) {
            StandaloneAccountingAccountMapping::updateOrCreate(['legacy_account_code' => $legacy], [
                'standalone_account_code' => $standalone, 'purpose' => 'Fixture normalisasi pinjaman', 'is_active' => true,
            ]);
        }
        $loan = EmployeeLoan::create([
            'employee_id' => $employee->id, 'date' => '2026-09-01', 'principal_amount' => 50000,
            'cash_account_id' => $cash->id, 'receivable_account_id' => $oldReceivable->id, 'status' => 'posted',
        ]);
        $legacy = app(JournalService::class)->post('2026-09-01', 'employee_loan', $loan->id, 'Pinjaman historis', [
            ['account_id' => $oldReceivable->id, 'debit' => 50000, 'credit' => 0],
            ['account_id' => $cash->id, 'debit' => 0, 'credit' => 50000],
        ]);
        $loan->update(['journal_id' => $legacy->id]);

        $this->artisan('accounting:normalize-employee-loan-receivables')->assertSuccessful();
        $this->assertSame($oldReceivable->id, (int) $loan->fresh()->receivable_account_id);
        $this->assertSame(0, StandaloneAccountingJournal::count());

        $this->artisan('accounting:normalize-employee-loan-receivables', ['--post' => true])->assertSuccessful();
        $this->assertSame($canonical->id, (int) $loan->fresh()->receivable_account_id);
        $reclass = Journal::where('source_type', JournalService::SRC_EMPLOYEE_LOAN_RECEIVABLE_RECLASS)
            ->where('source_id', $loan->id)->firstOrFail();
        $this->assertDatabaseHas('journal_lines', ['journal_id' => $reclass->id, 'account_id' => $canonical->id, 'debit' => 50000, 'credit' => 0]);
        $this->assertDatabaseHas('journal_lines', ['journal_id' => $reclass->id, 'account_id' => $oldReceivable->id, 'debit' => 0, 'credit' => 50000]);

        $historicalMirror = StandaloneAccountingJournal::where('source_type', 'employee_loan')->where('source_id', $loan->id)->firstOrFail();
        $reclassMirror = StandaloneAccountingJournal::where('source_type', JournalService::SRC_EMPLOYEE_LOAN_RECEIVABLE_RECLASS)->where('source_id', $loan->id)->firstOrFail();
        $this->assertLine($historicalMirror, '1-130', 50000, 0);
        $this->assertLine($reclassMirror, '1-135', 50000, 0);
        $this->assertLine($reclassMirror, '1-130', 0, 50000);
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
