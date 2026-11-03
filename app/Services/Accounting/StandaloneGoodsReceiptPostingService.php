<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\PurchaseReceipt;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StandaloneGoodsReceiptPostingService
{
    public const SOURCE_TYPE = 'purchase_receipt_post';

    public function post(PurchaseReceipt $grn, array $legacyLines, ?string $description = null): ?StandaloneAccountingJournal
    {
        if (! config('accounting.standalone_dual_posting_grn_enabled', true)) {
            return null;
        }

        return DB::transaction(function () use ($grn, $legacyLines, $description): StandaloneAccountingJournal {
            $existing = StandaloneAccountingJournal::query()
                ->where('source_id', (int) $grn->id)
                ->where(function ($query): void {
                    $query
                        ->where('source_type', self::SOURCE_TYPE)
                        ->orWhere('source_type', 'like', self::SOURCE_TYPE.'_repost_%');
                })
                ->whereNull('voided_at')
                ->with('lines')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $lines = $this->mapLegacyLines($legacyLines);
            if (count($lines) < 2) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => 'Mirror GRN ke akuntansi baru tidak memiliki minimal dua baris jurnal.',
                ]);
            }

            $totalDebit = round((float) collect($lines)->sum('debit'), 2);
            $totalCredit = round((float) collect($lines)->sum('credit'), 2);
            if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) > 0.01) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => 'Mirror GRN ke akuntansi baru tidak balance.',
                ]);
            }

            $reference = $this->nextReference((int) $grn->id);
            $sourceType = $this->nextSourceType((int) $grn->id);
            $journal = StandaloneAccountingJournal::create([
                'date' => $grn->date,
                'reference' => $reference,
                'source_type' => $sourceType,
                'source_id' => (int) $grn->id,
                'description' => $description ?: "Mirror GRN {$grn->code}",
                'posted_at' => now(),
            ]);

            $journal->lines()->createMany($lines);

            return $journal->load('lines');
        });
    }

    /**
     * Void active mirror journals and create a voided reversal for audit trail.
     * Both records are excluded from active standalone ledger reports.
     */
    public function voidForSource(string $sourceType, int $sourceId): void
    {
        if (! config('accounting.standalone_dual_posting_grn_enabled', true)) {
            return;
        }

        DB::transaction(function () use ($sourceType, $sourceId): void {
            $journals = StandaloneAccountingJournal::query()
                ->where('source_id', $sourceId)
                ->where(function ($query) use ($sourceType): void {
                    $query
                        ->where('source_type', $sourceType)
                        ->orWhere('source_type', 'like', $sourceType.'_repost_%');
                })
                ->whereNull('voided_at')
                ->with('lines')
                ->lockForUpdate()
                ->get();

            foreach ($journals as $journal) {
                $journal->forceFill(['voided_at' => now()])->save();

                $reversal = StandaloneAccountingJournal::create([
                    'date' => $journal->date,
                    'reference' => $this->nextReversalReference((string) $journal->reference),
                    // source_type + source_id is unique. Give each reversal
                    // its own trace key while keeping the original GRN source
                    // identifiable through the prefix and description.
                    'source_type' => $sourceType.'_reversal_'.$journal->id,
                    'source_id' => $sourceId,
                    'description' => 'Reversal: '.($journal->description ?: 'GRN'),
                    'posted_at' => now(),
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

    /** @return array<int, array{account_id:int,debit:float,credit:float}> */
    private function mapLegacyLines(array $legacyLines): array
    {
        $legacyIds = collect($legacyLines)
            ->pluck('account_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $legacyCodes = Account::query()
            ->whereIn('id', $legacyIds)
            ->pluck('code', 'id');

        $merged = [];
        foreach ($legacyLines as $line) {
            $legacyCode = (string) ($legacyCodes[(int) ($line['account_id'] ?? 0)] ?? '');
            if ($legacyCode === '') {
                throw ValidationException::withMessages([
                    'standalone_accounting' => 'Akun legacy pada jurnal GRN tidak ditemukan.',
                ]);
            }

            $account = $this->standaloneAccountForLegacyCode($legacyCode);
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }
            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Baris akun legacy {$legacyCode} tidak valid.",
                ]);
            }

            $key = (string) $account->id;
            $merged[$key] ??= [
                'account_id' => (int) $account->id,
                'debit' => 0.0,
                'credit' => 0.0,
            ];
            $merged[$key]['debit'] = round($merged[$key]['debit'] + $debit, 2);
            $merged[$key]['credit'] = round($merged[$key]['credit'] + $credit, 2);
        }

        return array_values(array_filter(
            $merged,
            fn (array $line): bool => $line['debit'] > 0.0001 || $line['credit'] > 0.0001,
        ));
    }

    private function standaloneAccountForLegacyCode(string $legacyCode): StandaloneAccountingAccount
    {
        $mapping = StandaloneAccountingAccountMapping::query()
            ->where('legacy_account_code', $legacyCode)
            ->where('is_active', true)
            ->first();

        if (! $mapping) {
            throw ValidationException::withMessages([
                'standalone_accounting' => "Mapping akun legacy {$legacyCode} ke akuntansi baru belum tersedia.",
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

    private function nextReference(int $sourceId): string
    {
        $base = 'SA-GRN-'.$sourceId;
        if (! StandaloneAccountingJournal::where('reference', $base)->exists()) {
            return $base;
        }

        $attempt = 1;
        do {
            $reference = $base.'-R'.$attempt++;
        } while (StandaloneAccountingJournal::where('reference', $reference)->exists());

        return $reference;
    }

    private function nextSourceType(int $sourceId): string
    {
        if (! StandaloneAccountingJournal::where('source_type', self::SOURCE_TYPE)->where('source_id', $sourceId)->exists()) {
            return self::SOURCE_TYPE;
        }

        $attempt = 1;
        do {
            $sourceType = self::SOURCE_TYPE.'_repost_'.$attempt++;
        } while (StandaloneAccountingJournal::where('source_type', $sourceType)->where('source_id', $sourceId)->exists());

        return $sourceType;
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
