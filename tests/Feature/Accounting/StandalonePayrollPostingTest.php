<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Employee;
use App\Models\Journal;
use App\Models\PieceworkPayrollLine;
use App\Models\PieceworkPayrollPeriod;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\PayrollOverpaymentRecoveryService;
use App\Services\Accounting\StandalonePayrollPostingService;
use App\Services\Payroll\PieceworkPayrollPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StandalonePayrollPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cutting_and_sewing_reconcile_only_difference_and_reuse_production_mirrors(): void
    {
        foreach (['cutting' => '1-151-01', 'sewing' => '1-151-02'] as $module => $code) {
            [$period, $bank] = $this->payroll($module);
            $source = $module === 'cutting' ? 'cutting_job_wage' : 'sewing_pickup_wage';
            $this->productionWage($source, $period->id, 7000);
            $service = app(PieceworkPayrollPostingService::class);
            $service->finalize($period);
            $service->pay($period, $bank->id);
            $service->pay($period, $bank->id);
            $service->finalize($period);
            $accrual = StandaloneAccountingJournal::where('source_type', StandalonePayrollPostingService::ACCRUAL)
                ->where('source_id', $period->id)->firstOrFail();
            $this->assertLine($accrual, $code, 3000, 0);
            $this->assertLine($accrual, '2-130', 0, 3000);
            $this->assertSame(1, StandaloneAccountingJournal::where('source_type', $source)->where('source_id', $period->id)->count());
            $this->assertSame('Sudah diposting', app(StandalonePayrollPostingService::class)->status($period->fresh())['label']);
        }
        $this->assertSame(6, StandaloneAccountingJournal::count());
        $this->assertSame(0, StandaloneAccountingJournal::where('source_type', 'like', 'opening%')->count());
    }

    public function test_fully_accrued_payroll_has_no_duplicate_accrual_and_can_unpost_repost(): void
    {
        [$period, $bank] = $this->payroll();
        $this->productionWage('cutting_job_wage', 81, 10000);
        $service = app(PieceworkPayrollPostingService::class);
        $service->finalize($period);
        $this->assertNull($period->fresh()->accrual_journal_id);
        $service->pay($period, $bank->id);
        $payment = StandaloneAccountingJournal::where('source_type', StandalonePayrollPostingService::PAYMENT)->firstOrFail();
        config(['accounting.standalone_dual_posting_payroll_enabled' => false]);
        $service->unpost($period);
        $this->assertNotNull($payment->fresh()->voided_at);
        $this->assertSame(1, StandaloneAccountingJournal::whereNull('voided_at')->count());
        $this->assertSame('Dibatalkan', app(StandalonePayrollPostingService::class)->status($period->fresh())['label']);
        config(['accounting.standalone_dual_posting_payroll_enabled' => true]);
        $service->finalize($period);
        $service->pay($period, $bank->id);
        $this->assertSame(2, StandaloneAccountingJournal::whereNull('voided_at')->count());
        $this->assertSame(1, StandaloneAccountingJournal::where('source_type', 'payroll_reversal')->whereNotNull('voided_at')->count());
    }

    public function test_missing_mapping_rolls_back_payment_and_finalize_completely(): void
    {
        [$period, $bank] = $this->payroll();
        StandaloneAccountingAccount::where('code', '1-151-01')->update(['is_active' => false]);
        try {
            app(PieceworkPayrollPostingService::class)->finalize($period);
            $this->fail('Expected invalid WIP mapping.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('belum aktif', $e->getMessage());
        }
        $this->assertSame('draft', $period->fresh()->status);
        $this->assertSame(0, Journal::count());
        $this->assertSame(0, StandaloneAccountingJournal::count());
        StandaloneAccountingAccount::where('code', '1-151-01')->update(['is_active' => true]);
        app(PieceworkPayrollPostingService::class)->finalize($period);
        StandaloneAccountingAccountMapping::where('legacy_account_code', $bank->code)->update(['is_active' => false]);
        try {
            app(PieceworkPayrollPostingService::class)->pay($period, $bank->id);
            $this->fail('Expected missing cash mapping.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Mapping akun payroll', $e->getMessage());
        }
        $this->assertNull($period->fresh()->paid_at);
        $this->assertSame(0, Journal::where('source_type', StandalonePayrollPostingService::PAYMENT)->count());
        $this->assertSame(1, StandaloneAccountingJournal::count());
    }

    public function test_daily_expense_and_loan_deduction_are_mapped_and_reversed(): void
    {
        [$period, $bank, $employee] = $this->payroll('daily');
        $loan = \App\Models\EmployeeLoan::create([
            'employee_id' => $employee->id, 'date' => '2026-08-01', 'principal_amount' => 4000,
            'cash_account_id' => $bank->id, 'receivable_account_id' => Account::where('code', '1308')->value('id'), 'status' => 'posted',
        ]);
        $service = app(PieceworkPayrollPostingService::class);
        $service->finalize($period);
        $service->pay($period, $bank->id, [$loan->id => 4000]);
        $accrual = StandaloneAccountingJournal::where('source_type', StandalonePayrollPostingService::ACCRUAL)->firstOrFail();
        $payment = StandaloneAccountingJournal::where('source_type', StandalonePayrollPostingService::PAYMENT)->firstOrFail();
        $this->assertLine($accrual, '6-180', 10000, 0);
        $this->assertLine($payment, '1-135', 0, 4000);
        $this->assertLine($payment, '1-120', 0, 6000);
        $service->unpost($period);
        $this->assertSame(0, StandaloneAccountingJournal::whereNull('voided_at')->count());
        $this->assertSame(4000.0, $loan->fresh()->outstanding_amount);
    }

    public function test_preview_is_read_only_and_history_post_is_idempotent(): void
    {
        [$period, $bank] = $this->payroll();
        config(['accounting.standalone_dual_posting_payroll_enabled' => false]);
        app(PieceworkPayrollPostingService::class)->finalize($period);
        app(PieceworkPayrollPostingService::class)->pay($period, $bank->id);
        config(['accounting.standalone_dual_posting_payroll_enabled' => true]);
        $before = $period->fresh()->getAttributes();
        $this->artisan('accounting:backfill-payroll', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, StandaloneAccountingJournal::count());
        $this->artisan('accounting:backfill-payroll', ['--post' => true])->assertSuccessful();
        $this->artisan('accounting:backfill-payroll', ['--post' => true])->assertSuccessful();
        $this->assertSame(2, StandaloneAccountingJournal::count());
        $this->assertSame($before, $period->fresh()->getAttributes());
        $this->assertSame(2, Journal::count());
    }

    public function test_existing_different_mirror_blocks_payment_instead_of_silently_reusing_it(): void
    {
        [$period, $bank] = $this->payroll();
        $service = app(PieceworkPayrollPostingService::class);
        $service->finalize($period);
        StandaloneAccountingJournal::first()->lines()->where('debit', '>', 0)->update(['debit' => 9999]);
        try {
            $service->pay($period, $bank->id);
            $this->fail('Expected mirror mismatch.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('berbeda dari sumber', $e->getMessage());
        }
        $this->assertNull($period->fresh()->paid_at);
    }

    public function test_existing_opening_blocks_historical_backfill(): void
    {
        [$period] = $this->payroll();
        config(['accounting.standalone_dual_posting_payroll_enabled' => false]);
        app(PieceworkPayrollPostingService::class)->finalize($period);
        config(['accounting.standalone_dual_posting_payroll_enabled' => true]);
        StandaloneAccountingJournal::create(['date' => '2026-09-01', 'source_type' => 'opening_migration', 'source_id' => 1, 'posted_at' => now()]);
        $this->artisan('accounting:backfill-payroll', ['--post' => true])->assertFailed();
        $this->assertSame(1, StandaloneAccountingJournal::count());
    }

    public function test_payroll_page_displays_posting_status_and_authenticated_root_redirect(): void
    {
        [$period] = $this->payroll();
        app(PieceworkPayrollPostingService::class)->finalize($period);
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'PAYROLL-OWNER']);
        $this->actingAs($owner)->get('/payroll')->assertRedirect('/payroll/dashboard');
        $this->get('/payroll/')->assertRedirect('/payroll/dashboard');
        $this->get(route('payroll.piecework.show', ['module' => 'cutting', 'period' => $period]))
            ->assertOk()->assertSee('Akuntansi Baru')->assertSee('Sudah diposting')->assertSee('SA-PAYROLL-J')
            ->assertDontSee('Dr HPP (5101)');
        $this->post(route('standalone-accounting.journals.void', StandaloneAccountingJournal::first()))->assertSessionHasErrors('journal');
    }

    public function test_source_change_after_finalize_blocks_new_payment(): void
    {
        [$period, $bank] = $this->payroll();
        $service = app(PieceworkPayrollPostingService::class);
        $service->finalize($period);
        $this->productionWage('cutting_job_wage', 97, 1000);
        try {
            $service->pay($period, $bank->id);
            $this->fail('Expected accrual mismatch.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Akrual upah tidak sesuai', $e->getMessage());
        }
        $this->assertNull($period->fresh()->paid_at);
        $this->assertSame(1, StandaloneAccountingJournal::count());
    }

    public function test_paid_payroll_surfaces_late_and_voided_wage_sources_without_posting_a_correction(): void
    {
        [$period, $bank] = $this->payroll();
        $source = $this->productionWage('cutting_job_wage', 98, 10000);
        $service = app(PieceworkPayrollPostingService::class);
        $service->finalize($period);
        $service->pay($period, $bank->id);

        $source->forceFill(['voided_at' => now()->addSecond()])->save();
        $lateSource = $this->productionWage('cutting_job_wage', 99, 1);
        $lateSource->forceFill(['created_at' => now()->addSecond()])->save();

        $alerts = app(StandalonePayrollPostingService::class)->status($period->fresh())['source_alerts'];

        $this->assertTrue(collect($alerts)->contains(fn ($alert) => $alert['type'] === 'voided_after_payment'
            && $alert['journal_id'] === $source->id && $alert['amount'] === 10000.0));
        $this->assertTrue(collect($alerts)->contains(fn ($alert) => $alert['type'] === 'created_after_payment'
            && $alert['amount'] === 1.0));
        $this->assertSame(2, StandaloneAccountingJournal::whereNull('voided_at')->count());
    }

    public function test_voided_paid_wage_can_be_recovered_as_employee_receivable_without_changing_payroll(): void
    {
        [$period, $bank] = $this->payroll();
        $source = $this->productionWage('cutting_job_wage', 98, 10000);
        $posting = app(PieceworkPayrollPostingService::class);
        $posting->finalize($period);
        $posting->pay($period, $bank->id);
        $source->forceFill(['voided_at' => now()->addSecond()])->save();

        $journal = app(PayrollOverpaymentRecoveryService::class)->post(
            $period->fresh(),
            $period->lines()->firstOrFail(),
            $source,
        );

        $this->assertLine($journal, '1-135', 10000, 0);
        $this->assertLine($journal, '2-130', 0, 10000);
        $this->assertDatabaseHas('employee_loans', [
            'employee_id' => $period->lines()->value('employee_id'),
            'principal_amount' => 10000,
            'status' => 'posted',
            'reference' => "PAYROLL-REC-P{$period->id}-L".$period->lines()->value('id'),
        ]);
        $this->assertSame(0.0, app(StandalonePayrollPostingService::class)->status($period->fresh())['difference']);
        $this->assertSame(10000.0, app(StandalonePayrollPostingService::class)->status($period->fresh())['recovery']);
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'PAYROLL-RECOVERY-OWNER']);
        $this->actingAs($owner)
            ->get(route('payroll.piecework.show', ['module' => 'cutting', 'period' => $period]))
            ->assertOk()
            ->assertSee('Tagihan koreksi yang sudah diposting: Rp 10.000,00');
        $this->assertSame($journal->id, app(PayrollOverpaymentRecoveryService::class)->post(
            $period->fresh(), $period->lines()->firstOrFail(), $source,
        )->id);
    }

    public function test_invalid_history_batch_does_not_partially_post_valid_periods(): void
    {
        [$first, $bank] = $this->payroll();
        [$second] = $this->payroll('daily');
        config(['accounting.standalone_dual_posting_payroll_enabled' => false]);
        app(PieceworkPayrollPostingService::class)->finalize($first);
        app(PieceworkPayrollPostingService::class)->finalize($second);
        config(['accounting.standalone_dual_posting_payroll_enabled' => true]);
        StandaloneAccountingAccountMapping::where('legacy_account_code', '6103')->update(['is_active' => false]);
        $this->artisan('accounting:backfill-payroll', ['--post' => true])->assertFailed();
        $this->assertSame(0, StandaloneAccountingJournal::count());
    }

    public function test_existing_production_mirror_is_reused_without_duplicate_and_excess_is_reconciled(): void
    {
        [$period] = $this->payroll();
        $source = $this->productionWage('cutting_job_wage', 99, 12000);
        app(\App\Services\Accounting\StandaloneProductionPostingService::class)->mirrorPayrollSource($source);
        app(PieceworkPayrollPostingService::class)->finalize($period);
        $accrual = StandaloneAccountingJournal::where('source_type', StandalonePayrollPostingService::ACCRUAL)->firstOrFail();
        $this->assertLine($accrual, '2-130', 2000, 0);
        $this->assertLine($accrual, '1-151-01', 0, 2000);
        $this->assertSame(2, StandaloneAccountingJournal::count());
    }

    public function test_historical_reclassification_and_difference_remain_visible_without_rewriting_legacy(): void
    {
        [$period, $bank] = $this->payroll('daily');
        config(['accounting.standalone_dual_posting_payroll_enabled' => false]);
        app(PieceworkPayrollPostingService::class)->finalize($period);
        app(PieceworkPayrollPostingService::class)->pay($period, $bank->id);
        $accrual = Journal::findOrFail($period->fresh()->accrual_journal_id);
        $accrual->lines()->where('debit', '>', 0)->update(['account_id' => Account::where('code', '1202')->value('id')]);
        app(JournalService::class)->post('2026-08-07', StandalonePayrollPostingService::RECLASS, $period->id, 'Reclass gaji harian', [
            ['account_id' => Account::where('code', '6103')->value('id'), 'debit' => 10000, 'credit' => 0],
            ['account_id' => Account::where('code', '1202')->value('id'), 'debit' => 0, 'credit' => 10000],
        ]);
        config(['accounting.standalone_dual_posting_payroll_enabled' => true]);
        $this->artisan('accounting:backfill-payroll', ['--post' => true])->assertSuccessful();
        $this->assertSame(3, StandaloneAccountingJournal::count());
        $period->update(['total_amount' => 10001]);
        $status = app(StandalonePayrollPostingService::class)->status($period->fresh());
        $this->assertStringContainsString('Selisih perlu diperiksa', $status['label']);
        $this->assertSame(-1.0, $status['difference']);
        app(PieceworkPayrollPostingService::class)->unpost($period);
        $this->assertSame(0, StandaloneAccountingJournal::whereNull('voided_at')->count());
    }

    public function test_another_active_mirror_for_the_same_source_cannot_be_duplicated(): void
    {
        [$period] = $this->payroll();
        config(['accounting.standalone_dual_posting_payroll_enabled' => false]);
        app(PieceworkPayrollPostingService::class)->finalize($period);
        StandaloneAccountingJournal::create([
            'date' => '2026-08-07', 'reference' => 'IMPORTED-PAYROLL', 'source_type' => StandalonePayrollPostingService::ACCRUAL,
            'source_id' => $period->id, 'posted_at' => now(),
        ]);
        config(['accounting.standalone_dual_posting_payroll_enabled' => true]);
        $this->artisan('accounting:backfill-payroll', ['--post' => true])->assertFailed();
        $this->assertSame(1, StandaloneAccountingJournal::count());
    }

    public function test_one_cent_difference_is_posted_in_both_ledgers(): void
    {
        [$period] = $this->payroll();
        $period->lines()->update(['amount' => 10000.01]);
        $this->productionWage('cutting_job_wage', 101, 10000);
        app(PieceworkPayrollPostingService::class)->finalize($period);
        $journal = StandaloneAccountingJournal::where('source_type', StandalonePayrollPostingService::ACCRUAL)->firstOrFail();
        $this->assertLine($journal, '2-130', 0, 0.01);
        $this->assertSame(0.0, app(StandalonePayrollPostingService::class)->status($period->fresh())['difference']);
    }

    private function payroll(string $module = 'cutting'): array
    {
        foreach (['1202' => 'asset', '2102' => 'liability', '6103' => 'expense', '1308' => 'asset'] as $code => $type) {
            Account::firstOrCreate(['code' => $code], ['name' => $code, 'type' => $type, 'is_active' => true]);
        }
        $bank = Account::firstOrCreate(['code' => '1101'], ['name' => 'Kas', 'type' => 'asset', 'is_cash' => true, 'is_active' => true]);
        $employee = Employee::create(['code' => 'PAYROLL-'.$module, 'name' => 'Payroll Test', 'role' => $module === 'daily' ? 'operating' : $module, 'payment_type' => 'variable', 'active' => true]);
        $period = PieceworkPayrollPeriod::create(['module' => $module, 'period_start' => '2026-08-01', 'period_end' => '2026-08-07', 'status' => 'draft']);
        PieceworkPayrollLine::create(['payroll_period_id' => $period->id, 'employee_id' => $employee->id, 'total_qty_ok' => 10,
            'rate_per_pcs' => 1000, 'amount' => 10000, 'attendance_status' => 'hadir']);

        return [$period, $bank, $employee];
    }

    private function productionWage(string $source, int $id, float $amount): Journal
    {
        return app(JournalService::class)->post('2026-08-07', $source, $id, 'Upah produksi', [
            ['account_id' => Account::where('code', '1202')->value('id'), 'debit' => $amount, 'credit' => 0],
            ['account_id' => Account::where('code', '2102')->value('id'), 'debit' => 0, 'credit' => $amount],
        ]);
    }

    private function assertLine(StandaloneAccountingJournal $journal, string $code, float $debit, float $credit): void
    {
        $this->assertDatabaseHas('standalone_accounting_journal_lines', ['journal_id' => $journal->id,
            'account_id' => StandaloneAccountingAccount::where('code', $code)->value('id'), 'debit' => $debit, 'credit' => $credit]);
    }
}
