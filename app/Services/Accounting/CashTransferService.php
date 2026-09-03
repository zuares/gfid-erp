<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\CashTransfer;
use App\Models\Journal;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashTransferService
{
    public function post(CashTransfer $transfer): CashTransfer
    {
        return DB::transaction(function () use ($transfer) {
            $locked = CashTransfer::query()
                ->whereKey($transfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'posted') {
                return $locked;
            }

            if ($locked->status === 'void') {
                throw ValidationException::withMessages([
                    'status' => 'Transfer sudah VOID, tidak bisa diposting.',
                ]);
            }

            $this->validateBeforePost($locked);

            $journal = Journal::create([
                'date' => $locked->date,
                'description' => trim(($locked->description ?: 'Transfer Kas/Bank') . ($locked->reference ? " (#{$locked->reference})" : '')),
                'source_type' => 'cash_transfer',
                'source_id' => $locked->id,
                'posted_at' => now(),
                'created_by' => $locked->created_by ?? auth()->id(),
                'reference_no' => $locked->reference,
                'notes' => $locked->notes,
            ]);

            // Uang masuk ke akun tujuan.
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->to_cash_account_id,
                'debit' => $locked->amount,
                'credit' => 0,
            ]);

            // Uang keluar dari akun asal.
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->from_cash_account_id,
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

    public function void(CashTransfer $transfer, ?string $reason = null): CashTransfer
    {
        return DB::transaction(function () use ($transfer, $reason) {
            $locked = CashTransfer::query()
                ->whereKey($transfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'void') {
                return $locked;
            }

            if ($locked->status !== 'posted' || !$locked->journal_id) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transfer POSTED yang bisa di-VOID.',
                ]);
            }

            $journal = Journal::create([
                'date' => $locked->date,
                'description' => 'REVERSAL: ' . ($locked->description ?: 'Transfer Kas/Bank') . ($reason ? " | {$reason}" : ''),
                'source_type' => 'cash_transfer_void',
                'source_id' => $locked->id,
                'posted_at' => now(),
                'created_by' => auth()->id(),
                'reference_no' => $locked->reference,
                'notes' => trim(($locked->notes ?? '') . ($reason ? "\nVOID reason: {$reason}" : '')),
            ]);

            // Balikkan jurnal: debit akun asal.
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->from_cash_account_id,
                'debit' => $locked->amount,
                'credit' => 0,
            ]);

            // Balikkan jurnal: kredit akun tujuan.
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->to_cash_account_id,
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

    private function validateBeforePost(CashTransfer $transfer): void
    {
        if ((float) $transfer->amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal transfer harus lebih besar dari 0.',
            ]);
        }

        if ((int) $transfer->from_cash_account_id === (int) $transfer->to_cash_account_id) {
            throw ValidationException::withMessages([
                'account' => 'Akun asal dan akun tujuan harus berbeda.',
            ]);
        }

        $accountIds = [$transfer->from_cash_account_id, $transfer->to_cash_account_id];
        $validCount = Account::query()
            ->whereIn('id', $accountIds)
            ->where('is_cash', true)
            ->where('is_active', true)
            ->count();

        if ($validCount !== 2) {
            throw ValidationException::withMessages([
                'account' => 'Akun asal dan tujuan harus akun kas/bank yang aktif.',
            ]);
        }
    }

    private function assertBalanced(int $journalId): void
    {
        $debit = (float) JournalLine::query()->where('journal_id', $journalId)->sum('debit');
        $credit = (float) JournalLine::query()->where('journal_id', $journalId)->sum('credit');

        if (abs($debit - $credit) > 0.01) {
            throw ValidationException::withMessages([
                'journal' => "Journal tidak balance. Debit={$debit}, Credit={$credit}",
            ]);
        }
    }
}
