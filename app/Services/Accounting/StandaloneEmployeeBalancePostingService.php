<?php

namespace App\Services\Accounting;

use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;
use App\Models\EmployeeSavingsTransaction;
use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mirror saldo karyawan yang timbul di luar pembayaran payroll.
 *
 * Potongan pinjaman dan bonus tabungan dari payroll sudah termasuk dalam
 * jurnal pembayaran payroll. Service ini khusus pencairan pinjaman,
 * cicilan tunai langsung, serta penarikan tabungan.
 */
class StandaloneEmployeeBalancePostingService
{
    public function syncLoan(EmployeeLoan $loan): ?StandaloneAccountingJournal
    {
        return $this->syncFromLegacyJournal(
            JournalService::SRC_EMPLOYEE_LOAN,
            (int) $loan->id,
        );
    }

    public function syncRepayment(EmployeeLoanRepayment $repayment): ?StandaloneAccountingJournal
    {
        if ($repayment->source_type === EmployeeLoanRepayment::SOURCE_PAYROLL_DEDUCTION) {
            return null;
        }

        return $this->syncFromLegacyJournal(
            JournalService::SRC_EMPLOYEE_LOAN_REPAYMENT,
            (int) $repayment->id,
        );
    }

    public function syncSavingsPayment(EmployeeSavingsTransaction $transaction): ?StandaloneAccountingJournal
    {
        if ($transaction->source_type === EmployeeSavingsTransaction::SOURCE_PAYROLL_BONUS) {
            return null;
        }

        return $this->syncFromLegacyJournal(
            JournalService::SRC_EMPLOYEE_SAVINGS_PAYMENT,
            (int) $transaction->id,
        );
    }

    public function syncLegacyJournal(string $sourceType, int $sourceId): ?StandaloneAccountingJournal
    {
        if (! in_array($sourceType, $this->sourceTypes(), true)) {
            throw ValidationException::withMessages([
                'standalone_accounting' => 'Tipe sumber saldo karyawan tidak didukung.',
            ]);
        }

        return $this->syncFromLegacyJournal($sourceType, $sourceId);
    }

    /** Existing mirrors must remain voidable even if future posting is disabled. */
    public function voidLegacyJournal(Journal $legacy): void
    {
        if (! in_array($legacy->source_type, $this->sourceTypes(), true) || ! $legacy->source_id) {
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
                // Baris reversal hanya jejak audit, tidak dihitung lagi di saldo aktif.
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
        if (! config('accounting.standalone_dual_posting_employee_balances_enabled', true)) {
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
                    'standalone_accounting' => "Mirror saldo karyawan jurnal #{$legacy->id} tidak balance.",
                ]);
            }

            $journal = StandaloneAccountingJournal::create([
                'date' => $legacy->date,
                'reference' => 'SA-EMP-'.$legacy->id,
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
                    'standalone_accounting' => "Mapping Akuntansi Baru untuk akun {$legacyCode} belum aktif.",
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
            JournalService::SRC_EMPLOYEE_LOAN,
            JournalService::SRC_EMPLOYEE_LOAN_REPAYMENT,
            JournalService::SRC_EMPLOYEE_LOAN_RECEIVABLE_RECLASS,
            JournalService::SRC_EMPLOYEE_SAVINGS_PAYMENT,
        ];
    }

    private function reversalReference(string $reference): string
    {
        $base = mb_substr(($reference ?: 'SA-EMP').'-REV', 0, 70);
        $reference = $base;
        $suffix = 1;
        while (StandaloneAccountingJournal::query()->where('reference', $reference)->exists()) {
            $reference = $base.'-'.$suffix++;
        }

        return $reference;
    }
}
