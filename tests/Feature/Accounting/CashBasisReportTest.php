<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\PieceworkPayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashBasisReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_daily_payroll_is_included_as_operating_cash_out(): void
    {
        $bank = Account::create([
            'code' => '1101',
            'name' => 'Bank Test',
            'type' => 'asset',
            'is_cash' => true,
            'is_active' => true,
        ]);
        $payable = Account::firstOrCreate(['code' => '2102'], [
            'code' => '2102',
            'name' => 'Hutang Upah Borongan',
            'type' => 'liability',
            'is_active' => true,
        ]);
        Account::firstOrCreate(['code' => '6103'], [
            'code' => '6103',
            'name' => 'Biaya Gaji Operasional',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $period = PieceworkPayrollPeriod::create([
            'module' => 'daily',
            'period_start' => '2026-08-31',
            'period_end' => '2026-09-06',
            'status' => 'final',
            'total_amount' => 700000,
            'paid_from_account_id' => $bank->id,
        ]);
        $payment = Journal::create([
            'date' => '2026-09-05',
            'description' => 'DAILY Payroll Harian (PAY)',
            'source_type' => 'piecework_payroll_period_payment',
            'source_id' => $period->id,
            'posted_at' => '2026-09-05 12:00:00',
        ]);
        $payment->lines()->createMany([
            ['account_id' => $payable->id, 'debit' => 700000, 'credit' => 0],
            ['account_id' => $bank->id, 'debit' => 0, 'credit' => 700000],
        ]);
        $period->update(['payment_journal_id' => $payment->id, 'paid_at' => '2026-09-05 12:00:00']);

        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'CBR-DAILY-TEST',
        ]);

        $this->actingAs($user)
            ->get(route('accounting.cash-basis-report.index', [
                'from' => '2026-08-31',
                'to' => '2026-09-06',
                'period' => 'all',
            ]))
            ->assertOk()
            ->assertSee('Biaya Gaji Operasional')
            ->assertSee('Rp 700.000');
    }
}
