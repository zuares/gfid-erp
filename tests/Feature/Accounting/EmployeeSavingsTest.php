<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeSavingsTransaction;
use App\Models\Journal;
use App\Models\User;
use App\Services\Accounting\EmployeeSavingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeSavingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_savings_payment_posts_debit_liability_and_credit_cash(): void
    {
        [$employee, $savings, $bank] = $this->fixture();

        EmployeeSavingsTransaction::create([
            'employee_id' => $employee->id,
            'date' => '2026-09-27',
            'amount' => 300000,
            'type' => EmployeeSavingsTransaction::TYPE_DEPOSIT,
            'status' => 'posted',
        ]);
        $payment = EmployeeSavingsTransaction::create([
            'employee_id' => $employee->id,
            'date' => '2026-10-04',
            'amount' => 250000,
            'type' => EmployeeSavingsTransaction::TYPE_PAYMENT,
            'cash_account_id' => $bank->id,
            'reference' => 'PAY-SAV-001',
            'status' => 'draft',
        ]);

        app(EmployeeSavingsService::class)->post($payment);

        $journal = Journal::where('source_type', EmployeeSavingsTransaction::SOURCE_PAYMENT)->firstOrFail();
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $savings->id,
            'debit' => 250000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $bank->id,
            'debit' => 0,
            'credit' => 250000,
        ]);
        $this->assertSame('posted', $payment->fresh()->status);
    }

    public function test_employee_savings_payment_cannot_exceed_employee_balance(): void
    {
        [$employee, , $bank] = $this->fixture();
        EmployeeSavingsTransaction::create([
            'employee_id' => $employee->id,
            'date' => '2026-09-27',
            'amount' => 100000,
            'type' => EmployeeSavingsTransaction::TYPE_DEPOSIT,
            'status' => 'posted',
        ]);
        $payment = EmployeeSavingsTransaction::create([
            'employee_id' => $employee->id,
            'date' => '2026-10-04',
            'amount' => 100001,
            'type' => EmployeeSavingsTransaction::TYPE_PAYMENT,
            'cash_account_id' => $bank->id,
            'status' => 'draft',
        ]);

        $this->expectException(ValidationException::class);
        app(EmployeeSavingsService::class)->post($payment);
    }

    public function test_owner_can_open_and_create_employee_savings_payment_draft(): void
    {
        $user = User::factory()->create(['role' => 'owner', 'employee_code' => 'SAV-OWNER-1']);
        [$employee, , $bank] = $this->fixture();

        $this->actingAs($user)
            ->get(route('accounting.employee-savings.index'))
            ->assertOk()
            ->assertSee('Tabungan Karyawan');

        $this->actingAs($user)
            ->get(route('accounting.employee-savings.create'))
            ->assertOk()
            ->assertSee('Bayar Tabungan Karyawan');

        $this->actingAs($user)
            ->post(route('accounting.employee-savings.store'), [
                'employee_id' => $employee->id,
                'date' => '2026-10-04',
                'amount' => 50000,
                'cash_account_id' => $bank->id,
                'reference' => 'PAY-SAV-ROUTE',
            ])
            ->assertRedirect();

        $payment = EmployeeSavingsTransaction::query()->latest('id')->firstOrFail();
        $this->actingAs($user)
            ->get(route('accounting.employee-savings.show', $payment))
            ->assertOk()
            ->assertSee('Detail Pembayaran Tabungan');

        $this->assertDatabaseHas('employee_savings_transactions', [
            'employee_id' => $employee->id,
            'type' => EmployeeSavingsTransaction::TYPE_PAYMENT,
            'status' => 'draft',
            'amount' => 50000,
        ]);
    }

    private function fixture(): array
    {
        $employee = Employee::create([
            'code' => 'EMP-SAV-001', 'name' => 'Karyawan Tabungan', 'role' => 'other',
            'payment_type' => 'fixed', 'active' => true,
        ]);
        $savings = Account::firstOrCreate(
            ['code' => '2104'],
            ['name' => 'Tabungan Karyawan', 'type' => 'liability', 'is_cash' => false, 'is_active' => true],
        );
        $bank = Account::create([
            'code' => '1198', 'name' => 'Bank Tabungan Test', 'type' => 'asset',
            'is_cash' => true, 'is_active' => true,
        ]);

        return [$employee, $savings, $bank];
    }
}
