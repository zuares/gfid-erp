<?php

namespace App\Services\Accounting;

use App\Models\CashExpense;
use App\Models\CashExpenseReclassification;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashExpenseService
{
    public function post(CashExpense $expense): CashExpense
    {
        return DB::transaction(function () use ($expense) {

            $locked = CashExpense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'posted') {
                return $locked; // idempotent
            }

            if ($locked->status === 'void') {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi sudah VOID, tidak bisa diposting.',
                ]);
            }

            $this->validateBeforePost($locked);

            $journal = Journal::create([
                'date' => $locked->date,
                'description' => trim(($locked->description ?: 'Cash Expense') . ($locked->reference ? " (#{$locked->reference})" : '')),
                'source_type' => 'cash_expense',
                'source_id' => $locked->id,
                'posted_at' => now(),
            ]);

            // Debit expense
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->expense_account_id,
                'debit' => $locked->amount,
                'credit' => 0,
            ]);

            // Credit cash/bank
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->cash_account_id,
                'debit' => 0,
                'credit' => $locked->amount,
            ]);

            $this->assertBalanced($journal->id);

            $locked->update([
                'status' => 'posted',
                'journal_id' => $journal->id,
            ]);

            return $locked->fresh();
        });
    }

    public function reclassify(CashExpense $expense, int $toExpenseAccountId, string $reason, ?int $userId = null): CashExpense
    {
        return DB::transaction(function () use ($expense, $toExpenseAccountId, $reason, $userId) {
            $locked = CashExpense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'posted' || !$locked->journal_id) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transaksi POSTED yang bisa direklasifikasi.',
                ]);
            }

            $toAccount = Account::query()
                ->whereKey($toExpenseAccountId)
                ->where('type', 'expense')
                ->where('is_active', true)
                ->first();

            if (!$toAccount) {
                throw ValidationException::withMessages([
                    'to_expense_account_id' => 'Kategori tujuan harus akun biaya yang aktif.',
                ]);
            }

            $fromAccountId = (int) $locked->expense_account_id;
            if ($fromAccountId === (int) $toAccount->id) {
                throw ValidationException::withMessages([
                    'to_expense_account_id' => 'Kategori tujuan harus berbeda dari kategori saat ini.',
                ]);
            }

            $journal = Journal::create([
                'date' => $locked->date,
                'description' => 'REKLASIFIKASI: ' . ($locked->description ?: 'Cash Expense'),
                'source_type' => 'cash_expense_reclass',
                'source_id' => $locked->id,
                'posted_at' => now(),
                'created_by' => $userId ?? auth()->id(),
                'notes' => $reason,
            ]);

            // Pindahkan beban dari kategori lama ke kategori baru.
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $toAccount->id,
                'debit' => $locked->amount,
                'credit' => 0,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $fromAccountId,
                'debit' => 0,
                'credit' => $locked->amount,
            ]);

            $this->assertBalanced($journal->id);

            CashExpenseReclassification::create([
                'cash_expense_id' => $locked->id,
                'from_expense_account_id' => $fromAccountId,
                'to_expense_account_id' => $toAccount->id,
                'journal_id' => $journal->id,
                'amount' => $locked->amount,
                'reason' => $reason,
                'created_by' => $userId ?? auth()->id(),
            ]);

            // Field ini menyimpan kategori efektif terakhir. Jurnal awal tidak diubah.
            $locked->update(['expense_account_id' => $toAccount->id]);

            return $locked->fresh();
        });
    }

    public function void(CashExpense $expense, ?string $reason = null): CashExpense
    {
        return DB::transaction(function () use ($expense, $reason) {

            $locked = CashExpense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'void') {
                return $locked; // idempotent
            }

            if ($locked->status !== 'posted' || !$locked->journal_id) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transaksi POSTED yang bisa di-VOID.',
                ]);
            }

            $journal = Journal::create([
                'date' => $locked->date,
                'description' => 'REVERSAL: ' . ($locked->description ?: 'Cash Expense') . ($reason ? " | {$reason}" : ''),
                'source_type' => 'cash_expense_void',
                'source_id' => $locked->id,
                'posted_at' => now(),
            ]);

            // Debit cash/bank
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->cash_account_id,
                'debit' => $locked->amount,
                'credit' => 0,
            ]);

            // Credit expense
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->expense_account_id,
                'debit' => 0,
                'credit' => $locked->amount,
            ]);

            $this->assertBalanced($journal->id);

            $locked->update([
                'status' => 'void',
                'notes' => trim(($locked->notes ?? '') . "\nVOID reason: " . ($reason ?: '-')),
            ]);

            return $locked->fresh();
        });
    }

    private function validateBeforePost(CashExpense $expense): void
    {
        if ($expense->amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount harus > 0.']);
        }

        if ($expense->expense_account_id === $expense->cash_account_id) {
            throw ValidationException::withMessages(['account' => 'Akun biaya dan akun kas/bank tidak boleh sama.']);
        }
    }

    private function assertBalanced(int $journalId): void
    {
        $d = (float) JournalLine::where('journal_id', $journalId)->sum('debit');
        $c = (float) JournalLine::where('journal_id', $journalId)->sum('credit');

        if (abs($d - $c) > 0.01) {
            throw ValidationException::withMessages([
                'journal' => "Journal tidak balance. Debit={$d}, Credit={$c}",
            ]);
        }
    }
}
