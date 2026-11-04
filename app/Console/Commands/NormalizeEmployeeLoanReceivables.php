<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\EmployeeLoan;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\StandaloneEmployeeBalancePostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeEmployeeLoanReceivables extends Command
{
    protected $signature = 'accounting:normalize-employee-loan-receivables {--post : Terapkan reclass dan mirror histori}';

    protected $description = 'Normalisasi pinjaman karyawan lama ke akun 1308 dan mirror ke Akuntansi Baru; default hanya pratinjau.';

    public function handle(
        JournalService $journals,
        StandaloneEmployeeBalancePostingService $standalone,
    ): int {
        $canonical = Account::query()->where('code', JournalService::CODE_EMPLOYEE_LOAN)
            ->where('type', 'asset')->where('is_active', true)->first();
        if (! $canonical) {
            $this->error('Akun 1308 Piutang Pinjaman Karyawan harus aktif.');

            return self::FAILURE;
        }

        $loans = EmployeeLoan::query()
            ->with(['employee', 'receivableAccount', 'repayments'])
            ->whereIn('status', ['posted', 'settled'])
            ->orderBy('id')
            ->get();
        $rows = $loans->map(function (EmployeeLoan $loan) use ($canonical): array {
            $outstanding = round((float) $loan->principal_amount - (float) $loan->repayments
                ->where('status', 'posted')->sum('amount'), 2);

            return [
                'loan' => $loan,
                'needs_reclass' => (int) $loan->receivable_account_id !== (int) $canonical->id,
                'outstanding' => max(0, $outstanding),
            ];
        });

        $this->table(['Pinjaman', 'Karyawan', 'Akun asal', 'Sisa', 'Aksi'], $rows->map(fn (array $row): array => [
            '#'.$row['loan']->id,
            $row['loan']->employee?->name ?: '-',
            $row['loan']->receivableAccount?->code ?: '-',
            number_format($row['outstanding'], 2, ',', '.'),
            $row['needs_reclass'] ? 'Reclass ke 1308 + mirror' : 'Mirror histori',
        ])->all());

        if (! $this->option('post')) {
            $this->warn('Pratinjau saja. Gunakan --post untuk membuat reclass dan mirror histori.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows, $canonical, $journals, $standalone): void {
            foreach ($rows as $row) {
                /** @var EmployeeLoan $loan */
                $loan = EmployeeLoan::query()->whereKey($row['loan']->id)->lockForUpdate()->firstOrFail();
                $oldAccountId = (int) $loan->receivable_account_id;
                if ($oldAccountId !== (int) $canonical->id && $row['outstanding'] > 0) {
                    $oldAccount = Account::query()->whereKey($oldAccountId)->where('type', 'asset')->where('is_active', true)->firstOrFail();
                    $journal = $journals->post(
                        now()->toDateString(),
                        JournalService::SRC_EMPLOYEE_LOAN_RECEIVABLE_RECLASS,
                        $loan->id,
                        "Reclass piutang pinjaman karyawan #{$loan->id} ke akun 1308",
                        [
                            ['account_id' => $canonical->id, 'debit' => $row['outstanding'], 'credit' => 0],
                            ['account_id' => $oldAccount->id, 'debit' => 0, 'credit' => $row['outstanding']],
                        ],
                        [
                            'reference_no' => "EMP-LOAN-RECLASS-{$loan->id}",
                            'notes' => 'Normalisasi akun pinjaman karyawan historis tanpa mengubah jurnal pencairan asal.',
                            'created_by' => $loan->created_by,
                        ],
                    );
                    $standalone->syncLegacyJournal($journal->source_type, (int) $journal->source_id);
                }

                if ($oldAccountId !== (int) $canonical->id) {
                    $loan->forceFill(['receivable_account_id' => $canonical->id])->save();
                }
                $standalone->syncLoan($loan);
            }
        });

        $this->info('Normalisasi dan mirror histori pinjaman selesai.');

        return self::SUCCESS;
    }
}
