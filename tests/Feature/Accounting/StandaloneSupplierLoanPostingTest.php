<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\Supplier;
use App\Models\SupplierLoan;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\SupplierLoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StandaloneSupplierLoanPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_loan_has_a_dedicated_new_account_mapping(): void
    {
        $this->assertDatabaseHas('standalone_accounting_accounts', [
            'code' => '1-136',
            'name' => 'Piutang Pinjaman Supplier',
            'type' => 'asset',
        ]);
        $this->assertDatabaseHas('standalone_accounting_account_mappings', [
            'legacy_account_code' => '1306',
            'standalone_account_code' => '1-136',
            'is_active' => 1,
        ]);
    }

    public function test_supplier_loan_mirror_is_disabled_by_default(): void
    {
        config(['accounting.standalone_dual_posting_supplier_loans_enabled' => false]);
        [$loan] = $this->loanFixture();

        app(SupplierLoanService::class)->post($loan);

        $this->assertDatabaseHas('journals', [
            'source_type' => JournalService::SRC_SUPPLIER_LOAN,
            'source_id' => $loan->id,
        ]);
        $this->assertDatabaseMissing('standalone_accounting_journals', [
            'source_type' => JournalService::SRC_SUPPLIER_LOAN,
            'source_id' => $loan->id,
        ]);
    }

    public function test_supplier_loan_repayment_and_void_are_mirrored_when_explicitly_enabled(): void
    {
        config(['accounting.standalone_dual_posting_supplier_loans_enabled' => true]);
        [$loan] = $this->loanFixture(true);
        $service = app(SupplierLoanService::class);

        $service->post($loan);
        $loanMirror = StandaloneAccountingJournal::query()
            ->where('source_type', JournalService::SRC_SUPPLIER_LOAN)
            ->where('source_id', $loan->id)
            ->firstOrFail();
        $this->assertLine($loanMirror, '1-136', 150000, 0);
        $this->assertLine($loanMirror, '1-120', 0, 150000);

        $repayment = $loan->repayments()->create([
            'date' => '2026-11-05',
            'amount' => 50000,
            'cash_account_id' => $loan->cash_account_id,
            'reference' => 'SUP-REPAY-SA-001',
            'status' => 'draft',
        ]);
        $service->postRepayment($repayment);

        $repaymentMirror = StandaloneAccountingJournal::query()
            ->where('source_type', JournalService::SRC_SUPPLIER_LOAN_REPAYMENT)
            ->where('source_id', $repayment->id)
            ->firstOrFail();
        $this->assertLine($repaymentMirror, '1-120', 50000, 0);
        $this->assertLine($repaymentMirror, '1-136', 0, 50000);

        $service->voidRepayment($repayment->fresh(), 'Uji VOID repayment');
        $this->assertNotNull($repaymentMirror->fresh()->voided_at);

        $service->void($loan->fresh(), 'Uji VOID');
        $this->assertNotNull($loanMirror->fresh()->voided_at);
        $this->assertDatabaseHas('standalone_accounting_journals', [
            'source_type' => JournalService::SRC_SUPPLIER_LOAN.'_reversal_'.$loanMirror->id,
            'source_id' => $loan->id,
        ]);
    }

    public function test_supplier_loan_apply_is_mirrored_when_explicitly_enabled(): void
    {
        config(['accounting.standalone_dual_posting_supplier_loans_enabled' => true]);
        [, $bank, $receivable] = $this->loanFixture(true);
        $advance = Account::firstOrCreate(['code' => '1151'], [
            'name' => 'Uang Muka Pembelian', 'type' => 'asset', 'is_cash' => false, 'is_active' => true,
        ]);

        $journal = app(JournalService::class)->post(
            '2026-11-05',
            JournalService::SRC_SUPPLIER_LOAN_APPLY,
            987654,
            'Alokasi Pinjaman Supplier ke PO UJI',
            [
                ['account_id' => $advance->id, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $receivable->id, 'debit' => 0, 'credit' => 50000],
            ],
        );

        $this->assertSame(JournalService::SRC_SUPPLIER_LOAN_APPLY, $journal->source_type);
        $this->assertDatabaseHas('standalone_accounting_journals', [
            'source_type' => JournalService::SRC_SUPPLIER_LOAN_APPLY,
            'source_id' => 987654,
        ]);
    }

    public function test_enabled_supplier_loan_mirror_fails_without_account_mapping_and_rolls_back(): void
    {
        config(['accounting.standalone_dual_posting_supplier_loans_enabled' => true]);
        [$loan] = $this->loanFixture(false, '1199');

        $this->expectException(ValidationException::class);
        app(SupplierLoanService::class)->post($loan);

        $this->assertDatabaseMissing('journals', [
            'source_type' => JournalService::SRC_SUPPLIER_LOAN,
            'source_id' => $loan->id,
        ]);
        $this->assertSame('draft', $loan->fresh()->status);
    }

    private function loanFixture(bool $withMappings = false, string $bankCode = '1101'): array
    {
        $supplier = Supplier::create([
            'code' => 'SUP-SA-'.str_replace('-', '', $bankCode),
            'name' => 'Supplier Mirror Uji',
            'active' => true,
        ]);
        $bank = Account::firstOrCreate(['code' => $bankCode], [
            'name' => 'Bank Supplier Uji', 'type' => 'asset', 'is_cash' => true, 'is_active' => true,
        ]);
        $receivable = Account::firstOrCreate(['code' => '1306'], [
            'name' => 'Piutang Pinjaman Supplier', 'type' => 'asset', 'is_cash' => false, 'is_active' => true,
        ]);

        if ($withMappings) {
            foreach ([$bankCode => '1-120', '1306' => '1-136', '1151' => '1-140'] as $legacy => $standalone) {
                StandaloneAccountingAccount::firstOrCreate(['code' => $standalone], [
                    'name' => 'Akun Uji '.$standalone, 'type' => 'asset', 'is_active' => true,
                ]);
                StandaloneAccountingAccountMapping::updateOrCreate(['legacy_account_code' => $legacy], [
                    'standalone_account_code' => $standalone,
                    'purpose' => 'Fixture supplier loan mirror',
                    'is_active' => true,
                ]);
            }
        }

        $loan = SupplierLoan::create([
            'supplier_id' => $supplier->id,
            'date' => '2026-11-04',
            'principal_amount' => 150000,
            'cash_account_id' => $bank->id,
            'receivable_account_id' => $receivable->id,
            'reference' => 'SUP-LOAN-SA-001',
            'status' => 'draft',
        ]);

        return [$loan, $bank, $receivable];
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
