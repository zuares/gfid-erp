<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\MarketplacePayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarketplacePayoutService
{
    /**
     * POST: Dr Bank / Cr 1302 Piutang Marketplace
     */
    public function post(MarketplacePayout $payout): MarketplacePayout
    {
        return DB::transaction(function () use ($payout) {
            $locked = MarketplacePayout::whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'posted') {
                return $locked;
            }

            if ($locked->status === 'void') {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi sudah VOID, tidak bisa diposting.',
                ]);
            }

            if ($locked->amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Amount harus > 0.']);
            }

            $piutangAccount = Account::where('code', '1302')->where('is_active', true)->first();
            if (! $piutangAccount) {
                throw ValidationException::withMessages([
                    'account' => 'Akun 1302 Piutang Marketplace tidak ditemukan.',
                ]);
            }

            $desc = trim(
                ($locked->description ?: "Penerimaan {$locked->marketplace_name}")
                . ($locked->reference ? " (#{$locked->reference})" : '')
            );

            $journal = Journal::create([
                'date'        => $locked->date,
                'description' => $desc,
                'source_type' => 'marketplace_payout',
                'source_id'   => $locked->id,
                'posted_at'   => now(),
            ]);

            // Dr Bank
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $locked->bank_account_id,
                'debit'      => $locked->amount,
                'credit'     => 0,
            ]);

            // Cr 1302 Piutang Marketplace
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $piutangAccount->id,
                'debit'      => 0,
                'credit'     => $locked->amount,
            ]);

            $locked->update([
                'status'     => 'posted',
                'journal_id' => $journal->id,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * VOID: reversal journal
     */
    public function void(MarketplacePayout $payout, ?string $reason = null): MarketplacePayout
    {
        return DB::transaction(function () use ($payout, $reason) {
            $locked = MarketplacePayout::whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'void') {
                return $locked;
            }

            if ($locked->status !== 'posted' || ! $locked->journal_id) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transaksi POSTED yang bisa di-VOID.',
                ]);
            }

            $original = Journal::with('lines')->findOrFail($locked->journal_id);

            $desc = 'REVERSAL: ' . ($locked->description ?: "Penerimaan {$locked->marketplace_name}")
                . ($reason ? " | {$reason}" : '');

            $rev = Journal::create([
                'date'        => $locked->date,
                'description' => $desc,
                'source_type' => 'marketplace_payout_void',
                'source_id'   => $locked->id,
                'posted_at'   => now(),
            ]);

            foreach ($original->lines as $ln) {
                JournalLine::create([
                    'journal_id' => $rev->id,
                    'account_id' => $ln->account_id,
                    'debit'      => (float) $ln->credit,
                    'credit'     => (float) $ln->debit,
                ]);
            }

            $locked->update([
                'status' => 'void',
                'notes'  => trim(($locked->notes ?? '') . "\nVOID: " . ($reason ?: '-')),
            ]);

            return $locked->fresh();
        });
    }
}
