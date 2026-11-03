<?php

namespace App\Services\Accounting;

use App\Models\InventoryAdjustment;
use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mirrors the posted inventory-adjustment journal into Akuntansi Baru.
 *
 * The legacy journal remains the source of posting rules. This service only
 * maps its account codes into the standalone chart, which keeps both ledgers
 * auditable and prevents a second inventory mutation from being created.
 */
class StandaloneInventoryAdjustmentPostingService
{
    public const SOURCE_TYPE = 'inventory_adjustment';

    public function post(InventoryAdjustment $adjustment): ?StandaloneAccountingJournal
    {
        if (! config('accounting.standalone_dual_posting_inventory_adjustment_enabled', true)) {
            return null;
        }

        return DB::transaction(function () use ($adjustment): ?StandaloneAccountingJournal {
            $existing = StandaloneAccountingJournal::query()
                ->where('source_type', self::SOURCE_TYPE)
                ->where('source_id', (int) $adjustment->id)
                ->whereNull('voided_at')
                ->with('lines')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $legacy = Journal::query()
                ->with('lines.account')
                ->where('source_type', self::SOURCE_TYPE)
                ->where('source_id', (int) $adjustment->id)
                ->whereNull('voided_at')
                ->latest('id')
                ->first();

            // The inventory transaction may have committed before the legacy
            // journal is posted. A retry will mirror it once it exists.
            if (! $legacy) {
                return null;
            }

            $lines = $this->mapLegacyLines($legacy);
            $this->assertBalanced($lines);

            $journal = StandaloneAccountingJournal::create([
                'date' => $legacy->date,
                'reference' => 'SA-INV-ADJ-'.(int) $adjustment->id,
                'source_type' => self::SOURCE_TYPE,
                'source_id' => (int) $adjustment->id,
                'description' => 'Mirror: '.($legacy->description ?: 'Penyesuaian stok '.$adjustment->code),
                'posted_at' => now(),
            ]);

            $journal->lines()->createMany($lines);

            return $journal->load('lines');
        });
    }

    /**
     * Void the active mirror and leave a voided reversal for audit history.
     */
    public function voidForSource(int $sourceId): void
    {
        if (! config('accounting.standalone_dual_posting_inventory_adjustment_enabled', true)) {
            return;
        }

        DB::transaction(function () use ($sourceId): void {
            $journals = StandaloneAccountingJournal::query()
                ->where('source_type', self::SOURCE_TYPE)
                ->where('source_id', $sourceId)
                ->whereNull('voided_at')
                ->with('lines')
                ->lockForUpdate()
                ->get();

            foreach ($journals as $journal) {
                $journal->forceFill(['voided_at' => now()])->save();

                $reversal = StandaloneAccountingJournal::create([
                    'date' => $journal->date,
                    'reference' => $journal->reference.'-REV-'.(int) $journal->id,
                    'source_type' => self::SOURCE_TYPE.'_reversal_'.$journal->id,
                    'source_id' => $sourceId,
                    'description' => 'Reversal: '.($journal->description ?: 'Penyesuaian stok'),
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

    /**
     * @return array<int, array{account_id:int,debit:float,credit:float}>
     */
    private function mapLegacyLines(Journal $legacy): array
    {
        $mapped = [];

        foreach ($legacy->lines as $line) {
            $legacyCode = (string) ($line->account?->code ?? '');
            if ($legacyCode === '') {
                throw ValidationException::withMessages([
                    'standalone_accounting' => 'Akun legacy pada jurnal penyesuaian stok tidak ditemukan.',
                ]);
            }

            $account = $this->standaloneAccountForLegacyCode($legacyCode);
            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);

            if ($debit <= 0 && $credit <= 0) {
                continue;
            }
            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Baris akun legacy {$legacyCode} tidak valid.",
                ]);
            }

            $side = $debit > 0 ? 'debit' : 'credit';
            $key = $account->id.':'.$side;
            $mapped[$key] ??= [
                'account_id' => (int) $account->id,
                'debit' => 0.0,
                'credit' => 0.0,
            ];
            $mapped[$key][$side] = round($mapped[$key][$side] + ($debit > 0 ? $debit : $credit), 2);
        }

        return array_values(array_filter(
            $mapped,
            fn (array $line): bool => $line['debit'] > 0.0001 || $line['credit'] > 0.0001,
        ));
    }

    /** @param array<int, array{account_id:int,debit:float,credit:float}> $lines */
    private function assertBalanced(array $lines): void
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'standalone_accounting' => 'Mirror penyesuaian stok harus memiliki minimal dua baris jurnal.',
            ]);
        }

        $debit = round((float) collect($lines)->sum('debit'), 2);
        $credit = round((float) collect($lines)->sum('credit'), 2);
        if ($debit <= 0 || abs($debit - $credit) > 0.01) {
            throw ValidationException::withMessages([
                'standalone_accounting' => 'Mirror penyesuaian stok ke akuntansi baru tidak balance.',
            ]);
        }
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
}
