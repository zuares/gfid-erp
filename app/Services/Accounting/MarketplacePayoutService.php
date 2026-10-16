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
    private const MARKETPLACE_CLEARING_ACCOUNT_CODE = '1302';
    private const MARKETPLACE_PAYOUT_PENDING_ACCOUNT_CODE = '2301';

    /**
     * POST pencairan wallet.
     *
     * Match the payout against an existing positive marketplace receivable
     * first. Any amount that arrives before its final settlement is kept in a
     * temporary liability so the clearing asset cannot become negative.
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

            $marketplaceClearingAccount = Account::where('code', self::MARKETPLACE_CLEARING_ACCOUNT_CODE)
                ->where('is_active', true)
                ->first();
            if (! $marketplaceClearingAccount) {
                throw ValidationException::withMessages([
                    'account' => 'Akun 1302 Saldo Marketplace / Clearing tidak ditemukan.',
                ]);
            }

            $marketplacePayoutPendingAccount = Account::where(
                'code',
                (string) config(
                    'marketplace.accounting_accounts.marketplace_payout_pending',
                    self::MARKETPLACE_PAYOUT_PENDING_ACCOUNT_CODE,
                ),
            )
                ->where('is_active', true)
                ->first();
            if (! $marketplacePayoutPendingAccount) {
                throw ValidationException::withMessages([
                    'account' => 'Akun 2301 Payout Marketplace Belum Direkonsiliasi tidak ditemukan.',
                ]);
            }

            $amount = round((float) $locked->amount, 2);
            $availableClearing = $this->positiveBalance($marketplaceClearingAccount->id);
            $matchedAmount = min($amount, $availableClearing);
            $pendingAmount = round($amount - $matchedAmount, 2);

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

            if ($matchedAmount > 0) {
                // Cr 1302 Saldo Marketplace / Clearing
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $marketplaceClearingAccount->id,
                    'debit'      => 0,
                    'credit'     => $matchedAmount,
                ]);
            }

            if ($pendingAmount > 0) {
                // Cr 2301 Payout Marketplace Belum Direkonsiliasi
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $marketplacePayoutPendingAccount->id,
                    'debit'      => 0,
                    'credit'     => $pendingAmount,
                ]);
            }

            $locked->update([
                'status'     => 'posted',
                'journal_id' => $journal->id,
            ]);

            return $locked->fresh();
        });
    }

    private function positiveBalance(int $accountId): float
    {
        $balance = (float) JournalLine::query()
            ->where('account_id', $accountId)
            ->whereHas('journal', fn ($query) => $query->whereNull('voided_at'))
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance');

        return max(round($balance, 2), 0);
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
