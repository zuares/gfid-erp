<?php

namespace App\Services\Accounting;

use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Optional mirror for supplier-loan journals during the accounting migration.
 *
 * The feature is intentionally disabled by default. Account mappings are read
 * at runtime and are not seeded here, so enabling this before the mappings are
 * approved fails safely instead of guessing an account.
 */
class StandaloneSupplierLoanPostingService
{
    public function supportsSourceType(string $sourceType): bool
    {
        return in_array($sourceType, $this->sourceTypes(), true);
    }

    public function syncLegacyJournal(string $sourceType, int $sourceId): ?StandaloneAccountingJournal
    {
        if (! $this->supportsSourceType($sourceType)) {
            throw ValidationException::withMessages([
                'standalone_accounting' => 'Tipe sumber pinjaman supplier tidak didukung.',
            ]);
        }

        return $this->syncFromLegacyJournal($sourceType, $sourceId);
    }

    /** Existing mirrors remain voidable even if future posting is disabled. */
    public function voidLegacyJournal(Journal $legacy): void
    {
        if (! $this->supportsSourceType((string) $legacy->source_type) || ! $legacy->source_id) {
            return;
        }

        DB::transaction(function () use ($legacy): void {
            $journal = StandaloneAccountingJournal::query()
                ->where('source_type', $legacy->source_type)
                ->where('source_id', $legacy->source_id)
                ->whereNull('voided_at')
                ->with('lines')
                ->lockForUpdate()
                ->first();

            if (! $journal) {
                return;
            }

            $journal->forceFill(['voided_at' => now()])->save();

            $reversal = StandaloneAccountingJournal::create([
                'date' => $journal->date,
                'reference' => $this->reversalReference((string) $journal->reference),
                'source_type' => $journal->source_type.'_reversal_'.$journal->id,
                'source_id' => $journal->source_id,
                'description' => 'Reversal: '.($journal->description ?: $journal->source_type),
                'posted_at' => now(),
                // Reversal hanya jejak audit dan tidak masuk saldo aktif.
                'voided_at' => now(),
            ]);

            $reversal->lines()->createMany($journal->lines->map(fn ($line): array => [
                'account_id' => (int) $line->account_id,
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
            ])->all());
        });
    }

    private function syncFromLegacyJournal(string $sourceType, int $sourceId): ?StandaloneAccountingJournal
    {
        if (! config('accounting.standalone_dual_posting_supplier_loans_enabled', false)) {
            return null;
        }

        return DB::transaction(function () use ($sourceType, $sourceId): ?StandaloneAccountingJournal {
            $legacy = Journal::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->whereNull('voided_at')
                ->with('lines.account')
                ->lockForUpdate()
                ->first();

            if (! $legacy) {
                return null;
            }

            $existing = StandaloneAccountingJournal::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $lines = $this->mappedLines($legacy);
            $debit = round((float) collect($lines)->sum('debit'), 2);
            $credit = round((float) collect($lines)->sum('credit'), 2);

            if ($debit <= 0 || abs($debit - $credit) > 0.01) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Mirror supplier loan jurnal #{$legacy->id} tidak balance.",
                ]);
            }

            $journal = StandaloneAccountingJournal::create([
                'date' => $legacy->date,
                'reference' => 'SA-SUPPLIER-LOAN-'.$legacy->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => 'Mirror: '.($legacy->description ?: $sourceType),
                'posted_at' => now(),
            ]);
            $journal->lines()->createMany($lines);

            return $journal;
        });
    }

    /** @return array<int, array{account_id:int,debit:float,credit:float}> */
    private function mappedLines(Journal $legacy): array
    {
        $merged = [];

        foreach ($legacy->lines as $line) {
            $legacyCode = (string) $line->account?->code;
            $mapping = StandaloneAccountingAccountMapping::query()
                ->where('legacy_account_code', $legacyCode)
                ->where('is_active', true)
                ->first();

            if (! $mapping) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Mapping Akuntansi Baru untuk akun {$legacyCode} belum tersedia.",
                ]);
            }

            $account = StandaloneAccountingAccount::query()
                ->where('code', $mapping->standalone_account_code)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Akun Akuntansi Baru {$mapping->standalone_account_code} belum aktif.",
                ]);
            }

            $key = (int) $account->id;
            $merged[$key] ??= ['account_id' => $key, 'debit' => 0.0, 'credit' => 0.0];
            $merged[$key]['debit'] += (float) $line->debit;
            $merged[$key]['credit'] += (float) $line->credit;
        }

        return collect($merged)
            ->map(fn (array $line): array => [
                'account_id' => $line['account_id'],
                'debit' => round($line['debit'], 2),
                'credit' => round($line['credit'], 2),
            ])
            ->filter(fn (array $line): bool => $line['debit'] > 0 || $line['credit'] > 0)
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function sourceTypes(): array
    {
        return [
            JournalService::SRC_SUPPLIER_LOAN,
            JournalService::SRC_SUPPLIER_LOAN_REPAYMENT,
            JournalService::SRC_SUPPLIER_LOAN_APPLY,
        ];
    }

    private function reversalReference(string $reference): string
    {
        $base = mb_substr(($reference ?: 'SA-SUPPLIER-LOAN').'-REV', 0, 70);
        $candidate = $base;
        $suffix = 1;

        while (StandaloneAccountingJournal::query()->where('reference', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }
}
