<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\CashExpense;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Services\Accounting\CashExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CashExpenseReclassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_posted_cash_expense_can_be_reclassified_without_changing_cash(): void
    {
        [$oldCategory, $newCategory, $cash] = $this->accounts();
        $expense = $this->postedExpense($oldCategory, $cash);

        $updated = app(CashExpenseService::class)->reclassify(
            $expense,
            $newCategory->id,
            'Kategori awal keliru',
        );

        $this->assertSame($newCategory->id, (int) $updated->expense_account_id);
        $this->assertDatabaseHas('cash_expense_reclassifications', [
            'cash_expense_id' => $expense->id,
            'from_expense_account_id' => $oldCategory->id,
            'to_expense_account_id' => $newCategory->id,
            'amount' => 125000,
            'reason' => 'Kategori awal keliru',
        ]);

        $correction = Journal::where('source_type', 'cash_expense_reclass')->firstOrFail();
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $correction->id,
            'account_id' => $newCategory->id,
            'debit' => 125000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $correction->id,
            'account_id' => $oldCategory->id,
            'debit' => 0,
            'credit' => 125000,
        ]);
        $this->assertDatabaseMissing('journal_lines', [
            'journal_id' => $correction->id,
            'account_id' => $cash->id,
        ]);
    }

    public function test_reclassification_requires_posted_expense_and_different_active_expense_account(): void
    {
        [$oldCategory, $newCategory, $cash] = $this->accounts();
        $draft = CashExpense::create([
            'date' => '2026-08-27',
            'amount' => 125000,
            'expense_account_id' => $oldCategory->id,
            'cash_account_id' => $cash->id,
            'status' => 'draft',
        ]);

        $this->expectException(ValidationException::class);
        app(CashExpenseService::class)->reclassify($draft, $newCategory->id, 'Tidak boleh');
    }

    public function test_void_after_reclassification_reverses_effective_category(): void
    {
        [$oldCategory, $newCategory, $cash] = $this->accounts();
        $expense = $this->postedExpense($oldCategory, $cash);
        $service = app(CashExpenseService::class);

        $service->reclassify($expense, $newCategory->id, 'Kategori awal keliru');
        $service->void($expense, 'Transaksi dibatalkan');

        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => Journal::where('source_type', 'cash_expense_void')->value('id'),
            'account_id' => $newCategory->id,
            'debit' => 0,
            'credit' => 125000,
        ]);
        $this->assertSame('void', $expense->fresh()->status);
        $this->assertSame(0.0, (float) JournalLine::query()
            ->where('account_id', $oldCategory->id)
            ->sum('debit') - (float) JournalLine::query()
            ->where('account_id', $oldCategory->id)
            ->sum('credit'));
    }

    private function accounts(): array
    {
        return [
            $this->account('6101', 'Biaya Lama'),
            $this->account('6102', 'Biaya Baru'),
            $this->account('1111', 'Kas Utama', true),
        ];
    }

    private function account(string $code, string $name, bool $isCash = false): Account
    {
        return Account::create([
            'code' => $code,
            'name' => $name,
            'type' => $isCash ? 'asset' : 'expense',
            'is_cash' => $isCash,
            'is_active' => true,
        ]);
    }

    private function postedExpense(Account $category, Account $cash): CashExpense
    {
        $expense = CashExpense::create([
            'date' => '2026-08-27',
            'amount' => 125000,
            'expense_account_id' => $category->id,
            'cash_account_id' => $cash->id,
            'description' => 'Beli kebutuhan kantor',
            'status' => 'posted',
        ]);

        $journal = Journal::create([
            'date' => $expense->date,
            'description' => 'Cash Expense',
            'source_type' => 'cash_expense',
            'source_id' => $expense->id,
            'posted_at' => now(),
        ]);
        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $category->id,
            'debit' => 125000,
            'credit' => 0,
        ]);
        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $cash->id,
            'debit' => 0,
            'credit' => 125000,
        ]);
        $expense->update(['journal_id' => $journal->id]);

        return $expense->fresh();
    }
}
