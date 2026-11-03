<?php

namespace App\Services\Accounting;

use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mirrors legacy opening-balance journals into Akuntansi Baru.
 *
 * The legacy opening journal remains the source event. The standalone journal
 * is an auditable mirror and uses the legacy journal id as its idempotency key.
 */
class StandaloneOpeningBalancePostingService
{
    public const SOURCE_TYPE = 'opening_balance';
    private const LEGACY_SOURCE_TYPES = [
        'opening_balance',
        'opening_balance_batch',
    ];

    public function post(Journal $legacy): ?StandaloneAccountingJournal
    {
        if (! config('accounting.standalone_dual_posting_opening_balance_enabled', true)) {
            return null;
        }

        if (! in_array($legacy->source_type, self::LEGACY_SOURCE_TYPES, true)) {
            return null;
        }

        return DB::transaction(function () use ($legacy): ?StandaloneAccountingJournal {
            $existing = StandaloneAccountingJournal::query()
                ->where('source_type', self::SOURCE_TYPE)
                ->where('source_id', (int) $legacy->id)
                ->whereNull('voided_at')
                ->with('lines')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $legacy->loadMissing('lines.account');
            $lines = $this->mapLegacyLines($legacy);
            $this->assertBalanced($lines);

            $journal = StandaloneAccountingJournal::create([
                'date' => $legacy->date,
                'reference' => 'SA-OPENING-'.(int) $legacy->id,
                'source_type' => self::SOURCE_TYPE,
                'source_id' => (int) $legacy->id,
                'description' => 'Mirror: '.($legacy->description ?: 'Saldo awal'),
                'posted_at' => now(),
            ]);

            $journal->lines()->createMany($lines);

            return $journal->load('lines');
        });
    }

    /**
     * Void the active mirror and keep a voided reversal for audit history.
     */
    public function voidForSource(int $legacyJournalId): void
    {
        if (! config('accounting.standalone_dual_posting_opening_balance_enabled', true)) {
            return;
        }

        DB::transaction(function () use ($legacyJournalId): void {
            $journals = StandaloneAccountingJournal::query()
                ->where('source_type', self::SOURCE_TYPE)
                ->where('source_id', $legacyJournalId)
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
                    'source_id' => $legacyJournalId,
                    'description' => 'Reversal: '.($journal->description ?: 'Saldo awal'),
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
                    'standalone_accounting' => 'Akun legacy pada saldo awal tidak ditemukan.',
                ]);
            }

            $mapping = StandaloneAccountingAccountMapping::query()
                ->where('legacy_account_code', $legacyCode)
                ->where('is_active', true)
                ->first();

            if (! $mapping) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Mapping akun legacy {$legacyCode} untuk saldo awal belum tersedia.",
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

            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);
            if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                throw ValidationException::withMessages([
                    'standalone_accounting' => "Baris akun legacy {$legacyCode} pada saldo awal tidak valid.",
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

        return array_values($mapped);
    }

    /** @param array<int, array{account_id:int,debit:float,credit:float}> $lines */
    private function assertBalanced(array $lines): void
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'standalone_accounting' => 'Mirror saldo awal harus memiliki minimal dua baris jurnal.',
            ]);
        }

        $debit = round((float) collect($lines)->sum('debit'), 2);
        $credit = round((float) collect($lines)->sum('credit'), 2);
        if ($debit <= 0 || abs($debit - $credit) > 0.01) {
            throw ValidationException::withMessages([
                'standalone_accounting' => 'Mirror saldo awal ke Akuntansi Baru tidak balance.',
            ]);
        }
    }
}
