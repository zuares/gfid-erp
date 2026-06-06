<?php

namespace App\Services\Accounting;

use App\Models\CashReceipt;
use App\Models\Journal;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashReceiptService
{
    public function post(CashReceipt $receipt): CashReceipt
    {
        return DB::transaction(function () use ($receipt) {
            $locked = CashReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'posted') {
                return $locked;
            }

            if ($locked->status === 'void') {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi sudah VOID, tidak bisa diposting.',
                ]);
            }

            $this->validateBeforePost($locked);

            $journal = Journal::create([
                'date' => $locked->date,
                'description' => trim(($locked->description ?: 'Cash Receipt') . ($locked->reference ? " (#{$locked->reference})" : '')),
                'source_type' => 'cash_receipt',
                'source_id' => $locked->id,
                'posted_at' => now(),
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->cash_account_id,
                'debit' => $locked->amount,
                'credit' => 0,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->source_account_id,
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

    public function void(CashReceipt $receipt, ?string $reason = null): CashReceipt
    {
        return DB::transaction(function () use ($receipt, $reason) {
            $locked = CashReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'void') {
                return $locked;
            }

            if ($locked->status !== 'posted' || !$locked->journal_id) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transaksi POSTED yang bisa di-VOID.',
                ]);
            }

            $journal = Journal::create([
                'date' => $locked->date,
                'description' => 'REVERSAL: ' . ($locked->description ?: 'Cash Receipt') . ($reason ? " | {$reason}" : ''),
                'source_type' => 'cash_receipt_void',
                'source_id' => $locked->id,
                'posted_at' => now(),
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->source_account_id,
                'debit' => $locked->amount,
                'credit' => 0,
            ]);

            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->cash_account_id,
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

    private function validateBeforePost(CashReceipt $receipt): void
    {
        if ($receipt->amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount harus > 0.']);
        }

        if ($receipt->source_account_id === $receipt->cash_account_id) {
            throw ValidationException::withMessages(['account' => 'Akun sumber dan akun kas/bank tidak boleh sama.']);
        }
    }

    private function assertBalanced(int $journalId): void
    {
        $debit = (float) JournalLine::where('journal_id', $journalId)->sum('debit');
        $credit = (float) JournalLine::where('journal_id', $journalId)->sum('credit');

        if (abs($debit - $credit) > 0.01) {
            throw ValidationException::withMessages([
                'journal' => "Journal tidak balance. Debit={$debit}, Credit={$credit}",
            ]);
        }
    }
}
