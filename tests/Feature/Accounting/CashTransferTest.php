<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\CashTransfer;
use App\Models\Journal;
use App\Models\User;
use App\Services\Accounting\CashTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CashTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_transfer_posts_debit_to_destination_and_credit_to_source(): void
    {
        [$bank, $cash] = $this->cashAccounts();
        $transfer = CashTransfer::create([
            'date' => '2026-09-04',
            'amount' => 1000000,
            'from_cash_account_id' => $bank->id,
            'to_cash_account_id' => $cash->id,
            'description' => 'Penarikan tunai',
            'status' => 'draft',
        ]);

        app(CashTransferService::class)->post($transfer);

        $journal = Journal::where('source_type', 'cash_transfer')->firstOrFail();
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $cash->id,
            'debit' => 1000000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $bank->id,
            'debit' => 0,
            'credit' => 1000000,
        ]);
        $this->assertSame('posted', $transfer->fresh()->status);
    }

    public function test_void_creates_reversal_for_cash_transfer(): void
    {
        [$bank, $cash] = $this->cashAccounts();
        $transfer = CashTransfer::create([
            'date' => '2026-09-04',
            'amount' => 250000,
            'from_cash_account_id' => $bank->id,
            'to_cash_account_id' => $cash->id,
            'status' => 'draft',
        ]);
        $service = app(CashTransferService::class);
        $service->post($transfer);

        $service->void($transfer, 'Salah nominal');

        $journal = Journal::where('source_type', 'cash_transfer_void')->firstOrFail();
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $bank->id,
            'debit' => 250000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => $cash->id,
            'debit' => 0,
            'credit' => 250000,
        ]);
        $this->assertSame('void', $transfer->fresh()->status);
    }

    public function test_transfer_requires_two_different_active_cash_accounts(): void
    {
        [$bank] = $this->cashAccounts();
        $transfer = CashTransfer::create([
            'date' => '2026-09-04',
            'amount' => 100000,
            'from_cash_account_id' => $bank->id,
            'to_cash_account_id' => $bank->id,
            'status' => 'draft',
        ]);

        $this->expectException(ValidationException::class);
        app(CashTransferService::class)->post($transfer);
    }

    public function test_owner_can_open_transfer_page_and_accounting_root_redirects(): void
    {
        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'TRF-UI-1',
        ]);
        [$bank] = $this->cashAccounts();

        $this->actingAs($user)
            ->get(route('accounting.index'))
            ->assertRedirect(route('accounting.cash-basis-report.index'));

        $this->actingAs($user)
            ->get(route('accounting.cash-transfers.index'))
            ->assertOk()
            ->assertSee('Transfer Kas/Bank');

        $this->actingAs($user)
            ->get(route('accounting.cash-transfers.create'))
            ->assertOk()
            ->assertSee('value="' . $bank->id . '" selected', false);
    }

    private function cashAccounts(): array
    {
        return [
            Account::create(['code' => '1111', 'name' => 'Bank Utama', 'type' => 'asset', 'is_cash' => true, 'is_active' => true]),
            Account::create(['code' => '1101', 'name' => 'Kas Tunai', 'type' => 'asset', 'is_cash' => true, 'is_active' => true]),
        ];
    }
}
