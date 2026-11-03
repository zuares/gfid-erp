<?php

namespace App\Services\Accounting;

use App\Models\PurchasePayment;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StandalonePurchasePaymentPostingService
{
    public function post(PurchasePayment $payment, ?string $description = null): ?StandaloneAccountingJournal
    {
        if (! config('accounting.standalone_dual_posting_enabled', false)) {
            return null;
        }

        $allowedTypes = config('accounting.standalone_dual_posting_types', ['dp', 'payment', 'dp_apply']);
        if (! in_array((string) $payment->type, $allowedTypes, true)) {
            return null;
        }

        return DB::transaction(function () use ($payment, $description): StandaloneAccountingJournal {
            $payment->loadMissing(['purchaseOrder', 'cashAccount', 'paymentMethod']);

            $existing = StandaloneAccountingJournal::query()
                ->where('source_type', JournalService::SRC_PURCHASE_PAYMENT)
                ->where('source_id', (int) $payment->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ((int) $payment->standalone_journal_id !== (int) $existing->id) {
                    $payment->forceFill(['standalone_journal_id' => $existing->id])->save();
                }

                return $existing;
            }

            $amount = round((float) $payment->amount, 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => 'Nominal mirror akuntansi baru harus lebih dari 0.',
                ]);
            }

            $poCode = $payment->purchaseOrder?->code ?? '-';
            $lines = match ((string) $payment->type) {
                'dp' => [
                    ['account' => $this->accountForLegacyCode('1151'), 'debit' => $amount, 'credit' => 0],
                    ['account' => $this->cashAccountFor($payment), 'debit' => 0, 'credit' => $amount],
                ],
                'payment' => [
                    ['account' => $this->accountForLegacyCode('2101'), 'debit' => $amount, 'credit' => 0],
                    ['account' => $this->cashAccountFor($payment), 'debit' => 0, 'credit' => $amount],
                ],
                'dp_apply' => [
                    ['account' => $this->accountForLegacyCode('2101'), 'debit' => $amount, 'credit' => 0],
                    ['account' => $this->accountForLegacyCode('1151'), 'debit' => 0, 'credit' => $amount],
                ],
                default => throw ValidationException::withMessages([
                    'standalone_accounting' => 'Tipe pembayaran belum didukung untuk mirror akuntansi baru.',
                ]),
            };

            $journal = StandaloneAccountingJournal::create([
                'date' => $payment->date,
                'reference' => 'SA-PURCHASE-PAYMENT-'.$payment->id,
                'source_type' => JournalService::SRC_PURCHASE_PAYMENT,
                'source_id' => (int) $payment->id,
                'description' => $description ?: "Mirror pembayaran pembelian {$poCode}",
                'posted_at' => now(),
            ]);

            $journal->lines()->createMany(array_map(
                fn (array $line): array => [
                    'account_id' => $line['account']->id,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ],
                $lines,
            ));

            $payment->forceFill(['standalone_journal_id' => $journal->id])->save();

            return $journal;
        });
    }

    public function voidForSource(string $sourceType, int $sourceId): void
    {
        if (! config('accounting.standalone_dual_posting_enabled', false)) {
            return;
        }

        DB::transaction(function () use ($sourceType, $sourceId): void {
            $journals = StandaloneAccountingJournal::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->whereNull('voided_at')
                ->with('lines')
                ->lockForUpdate()
                ->get();

            foreach ($journals as $journal) {
                $journal->forceFill(['voided_at' => now()])->save();

                $reversal = StandaloneAccountingJournal::create([
                    'date' => $journal->date,
                    'reference' => $this->nextReversalReference((string) $journal->reference),
                    // source_type + source_id is unique, so each reversal
                    // gets its own trace key while retaining the payment ID.
                    'source_type' => $sourceType.'_reversal_'.$journal->id,
                    'source_id' => $sourceId,
                    'description' => 'Reversal: '.($journal->description ?: 'Pembayaran pembelian'),
                    'posted_at' => now(),
                    // Reversal is an audit record only. It must not affect
                    // active balances during this migration/trial phase.
                    'voided_at' => now(),
                ]);

                $reversal->lines()->createMany(
                    $journal->lines->map(fn ($line): array => [
                        'account_id' => (int) $line->account_id,
                        'debit' => (float) $line->credit,
                        'credit' => (float) $line->debit,
                    ])->all(),
                );
            }
        });
    }

    private function cashAccountFor(PurchasePayment $payment): StandaloneAccountingAccount
    {
        $legacyCode = (string) ($payment->cashAccount?->code ?? '');
        if ($legacyCode === '') {
            throw ValidationException::withMessages([
                'standalone_accounting' => 'Akun kas/bank lama belum tersedia untuk mirror akuntansi baru.',
            ]);
        }

        return $this->accountForLegacyCode($legacyCode);
    }

    private function accountForLegacyCode(string $legacyCode): StandaloneAccountingAccount
    {
        $mapping = StandaloneAccountingAccountMapping::query()
            ->where('legacy_account_code', $legacyCode)
            ->where('is_active', true)
            ->first();

        if (! $mapping) {
            throw ValidationException::withMessages([
                'standalone_accounting' => "Mapping akun lama {$legacyCode} ke akuntansi baru belum tersedia.",
            ]);
        }

        $account = StandaloneAccountingAccount::query()
            ->where('code', $mapping->standalone_account_code)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'standalone_accounting' => "Akun baru {$mapping->standalone_account_code} untuk {$legacyCode} belum aktif.",
            ]);
        }

        return $account;
    }

    private function nextReversalReference(string $originalReference): string
    {
        $base = $originalReference.'-REV';
        $reference = $base;
        $attempt = 1;

        while (StandaloneAccountingJournal::where('reference', $reference)->exists()) {
            $reference = $base.'-'.$attempt++;
        }

        return $reference;
    }
}
