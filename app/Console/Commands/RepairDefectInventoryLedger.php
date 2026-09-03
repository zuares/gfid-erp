<?php

namespace App\Console\Commands;

use App\Services\Accounting\JournalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairDefectInventoryLedger extends Command
{
    protected $signature = 'accounting:repair-defect-inventory-ledger
                            {--reject-journal=708 : Journal reject-only yang harus di-void}
                            {--opening-journal=867 : Journal opening balance yang baris 1204-nya harus dibalik}
                            {--apply : Terapkan koreksi jurnal}
                            {--force : Lewati konfirmasi saat apply}';

    protected $description = 'Preview/koreksi jurnal barang cacat yang salah posting. Tidak mengubah stok fisik.';

    public function handle(JournalService $journalService): int
    {
        $rejectJournalId = (int) $this->option('reject-journal');
        $openingJournalId = (int) $this->option('opening-journal');

        $reject = DB::table('journals')->where('id', $rejectJournalId)->first();
        $opening = DB::table('journals')->where('id', $openingJournalId)->first();

        if (!$reject || !$opening) {
            $this->error('Journal target tidak ditemukan.');
            return self::FAILURE;
        }

        if ($reject->source_type !== JournalService::SRC_SEWING_RETURN_REJECT
            || (int) $reject->source_id !== 65) {
            $this->error("Journal reject #{$rejectJournalId} bukan target source 65 sewing_return_reject.");
            return self::FAILURE;
        }

        $opening1204 = DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $openingJournalId)
            ->where('a.code', JournalService::CODE_INV_DEFECT)
            ->selectRaw('SUM(jl.debit) as debit, SUM(jl.credit) as credit, COUNT(*) as line_count')
            ->first();

        $openingAmount = round((float) ($opening1204->debit ?? 0), 2);
        $openingCredit = round((float) ($opening1204->credit ?? 0), 2);

        if ($opening->source_type !== 'opening_balance_batch' || $openingAmount <= 0 || $openingCredit > 0) {
            $this->error("Journal opening #{$openingJournalId} tidak memiliki baris debit 1204 yang valid untuk dibalik.");
            return self::FAILURE;
        }

        $accounts = DB::table('accounts')
            ->whereIn('code', ['1204', '3101'])
            ->where('is_active', 1)
            ->pluck('id', 'code');

        if (!$accounts->has('1204') || !$accounts->has('3101')) {
            $this->error('Akun 1204 atau 3101 tidak ditemukan / tidak aktif.');
            return self::FAILURE;
        }

        $rejectLines = DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_id', $rejectJournalId)
            ->select('a.code', 'a.name', 'jl.debit', 'jl.credit')
            ->orderBy('a.code')
            ->get();

        $hasRejectDebit = $rejectLines->contains(fn ($line) => $line->code === '1204' && (float) $line->debit > 0);
        $hasWipCredit = $rejectLines->contains(fn ($line) => $line->code === '1202' && (float) $line->credit > 0);

        if (!$hasRejectDebit || !$hasWipCredit) {
            $this->error("Journal reject #{$rejectJournalId} tidak memiliki pola Dr 1204 / Cr 1202 yang diharapkan.");
            return self::FAILURE;
        }

        $existingCorrection = DB::table('journals')
            ->where('source_type', JournalService::SRC_DEFECT_OPENING_BALANCE_CORRECTION)
            ->where('source_id', $openingJournalId)
            ->whereNull('voided_at')
            ->first(['id']);

        $this->info($this->option('apply')
            ? 'MODE APPLY: koreksi jurnal barang cacat'
            : 'MODE DRY-RUN: preview saja');
        $this->newLine();
        $this->table(
            ['Target', 'Journal', 'Status', 'Nilai', 'Tindakan'],
            [
                [
                    'Reject-only source 65',
                    $rejectJournalId,
                    $reject->voided_at ? 'SUDAH VOID' : 'AKAN VOID',
                    'lihat lines',
                    'Void jurnal tanpa mengubah stok',
                ],
                [
                    'Opening balance 1204',
                    $openingJournalId,
                    $existingCorrection ? 'SUDAH DIKOREKSI' : 'AKAN DIKOREKSI',
                    'Rp ' . number_format($openingAmount, 2, ',', '.'),
                    'Dr 3101 / Cr 1204',
                ],
            ]
        );

        $this->line('Jurnal reject lines:');
        $this->table(
            ['Akun', 'Nama', 'Debit', 'Credit'],
            $rejectLines->map(fn ($line) => [
                $line->code,
                $line->name,
                'Rp ' . number_format((float) $line->debit, 2, ',', '.'),
                'Rp ' . number_format((float) $line->credit, 2, ',', '.'),
            ])->all()
        );

        if (!$this->option('apply')) {
            $this->newLine();
            $this->warn('Belum ada data yang diubah. Untuk eksekusi gunakan:');
            $this->line('  php artisan accounting:repair-defect-inventory-ledger --apply');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Terapkan koreksi jurnal ini?', false)) {
            $this->warn('Dibatalkan. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        DB::transaction(function () use (
            $journalService,
            $reject,
            $rejectJournalId,
            $opening,
            $openingJournalId,
            $openingAmount,
            $accounts,
            $existingCorrection
        ): void {
            if (!$reject->voided_at) {
                $journalService->voidById(
                    $rejectJournalId,
                    'Koreksi reject-only source 65: mutasi hanya keluar-masuk di REJ-SEW.'
                );
            }

            if (!$existingCorrection) {
                $journalService->post(
                    (string) $opening->date,
                    JournalService::SRC_DEFECT_OPENING_BALANCE_CORRECTION,
                    $openingJournalId,
                    'Koreksi opening balance Persediaan Barang Cacat #' . $openingJournalId,
                    [
                        [
                            'account_id' => (int) $accounts['3101'],
                            'debit' => $openingAmount,
                            'credit' => 0,
                        ],
                        [
                            'account_id' => (int) $accounts['1204'],
                            'debit' => 0,
                            'credit' => $openingAmount,
                        ],
                    ],
                    [
                        'notes' => 'Opening balance 1204 tidak memiliki stok opening opname di gudang reject.',
                    ]
                );
            }
        });

        $this->newLine();
        $this->info('Koreksi selesai. Stok fisik tidak diubah.');

        return self::SUCCESS;
    }
}
