<?php

namespace App\Services\Accounting;

use App\Models\CashExpense;
use App\Models\Journal;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashExpenseService
{
    private const EXCLUDED_BALANCE_SOURCES = [
        'opening_balance_void',
        'opening_balance_batch_void',
    ];

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

        $cashBalance = $this->cashBalance((int) $expense->cash_account_id);
        $amount = (float) $expense->amount;

        if ($cashBalance + 0.01 < $amount) {
            $expense->loadMissing('cashAccount');
            $accountName = $expense->cashAccount?->name ?? 'Kas/Bank';

            throw ValidationException::withMessages([
                'cash_account_id' => $accountName . ' tidak cukup. Saldo tersedia Rp ' .
                    number_format($cashBalance, 0, ',', '.') . ', pengeluaran Rp ' .
                    number_format($amount, 0, ',', '.') . '.',
            ]);
        }
    }

    private function cashBalance(int $accountId): float
    {
        return (float) JournalLine::query()
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->where('journal_lines.account_id', $accountId)
            ->whereNull('journals.voided_at')
            ->whereNotIn('journals.source_type', self::EXCLUDED_BALANCE_SOURCES)
            ->selectRaw('COALESCE(SUM(journal_lines.debit - journal_lines.credit), 0) as balance')
            ->value('balance');
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
