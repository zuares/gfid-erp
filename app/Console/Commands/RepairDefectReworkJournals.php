<?php

namespace App\Console\Commands;

use App\Models\SewingReturn;
use App\Services\Accounting\JournalService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RepairDefectReworkJournals extends Command
{
    protected $signature = 'accounting:repair-defect-rework-journals
                            {--apply : Void jurnal lama lalu posting ulang dengan Cr 1204}
                            {--force : Lewati konfirmasi saat apply}
                            {--limit= : Batasi jumlah dokumen yang diproses}';

    protected $description = 'Preview/koreksi jurnal setor ulang yang keluar dari REJ-SEW tetapi masih mengkredit 1202.';

    public function handle(JournalService $journalService): int
    {
        $rejectWarehouseId = DB::table('warehouses')
            ->where('code', 'REJ-SEW')
            ->value('id');

        if (!$rejectWarehouseId) {
            $this->error('Gudang REJ-SEW tidak ditemukan.');
            return self::FAILURE;
        }

        $sourceIds = DB::table('inventory_mutations')
            ->where('warehouse_id', $rejectWarehouseId)
            ->where('source_type', 'sewing_qc_out')
            ->where('qty_change', '<', 0)
            ->whereNotNull('source_id')
            ->distinct()
            ->orderBy('source_id')
            ->pluck('source_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $rows = $this->candidateRows($sourceIds);
        $repairRows = $rows->where('status', 'REPAIR')->values();

        $this->info($this->option('apply')
            ? 'MODE APPLY: void jurnal lama dan posting ulang setor ulang'
            : 'MODE DRY-RUN: preview saja');
        $this->newLine();
        $this->table(
            ['Return', 'Source ID', 'Journal', 'Qty', 'Nilai', 'Status'],
            $rows->map(fn (array $row) => [
                $row['return_code'],
                $row['source_id'],
                $row['journal_id'] ?: '-',
                number_format($row['qty'], 3, ',', '.'),
                'Rp ' . number_format($row['value'], 2, ',', '.'),
                $row['status'],
            ])->all()
        );

        if ($repairRows->isEmpty()) {
            $this->info('Tidak ada jurnal setor ulang yang perlu dikoreksi.');
            return self::SUCCESS;
        }

        if (!$this->option('apply')) {
            $this->newLine();
            $this->warn('Belum ada data yang diubah. Untuk eksekusi gunakan:');
            $this->line('  php artisan accounting:repair-defect-rework-journals --apply');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Koreksi jurnal yang berstatus REPAIR?', false)) {
            $this->warn('Dibatalkan. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        $limit = $this->option('limit') !== null
            ? max((int) $this->option('limit'), 0)
            : null;
        $toApply = $limit !== null && $limit > 0
            ? $repairRows->take($limit)
            : $repairRows;

        $repaired = 0;
        $errors = 0;

        foreach ($toApply as $row) {
            try {
                DB::transaction(function () use ($journalService, $row): void {
                    $journalService->voidById(
                        (int) $row['journal_id'],
                        'Koreksi setor ulang dari REJ-SEW: pindahkan kredit dari 1202 ke 1204.'
                    );

                    $return = SewingReturn::query()->findOrFail((int) $row['source_id']);
                    $posted = $journalService->postSewingReturnOk($return);

                    if (!$posted) {
                        throw new \RuntimeException('Jurnal koreksi tidak berhasil diposting ulang.');
                    }
                });

                $repaired++;
            } catch (\Throwable $e) {
                $errors++;
                $this->error("Gagal {$row['return_code']}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('Koreksi selesai.');
        $this->line('Berhasil: ' . number_format($repaired, 0, ',', '.'));
        $this->line('Gagal: ' . number_format($errors, 0, ',', '.'));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function candidateRows(Collection $sourceIds): Collection
    {
        return $sourceIds->map(function (int $sourceId): array {
            $return = DB::table('sewing_returns')
                ->where('id', $sourceId)
                ->first(['id', 'code']);

            $movement = DB::table('inventory_mutations')
                ->where('source_type', 'sewing_qc_out')
                ->where('source_id', $sourceId)
                ->where('qty_change', '<', 0)
                ->selectRaw('SUM(ABS(qty_change)) as qty, SUM(ABS(COALESCE(total_cost, 0))) as value')
                ->first();

            $journal = DB::table('journals')
                ->where('source_type', JournalService::SRC_SEWING_RETURN_OK)
                ->where('source_id', $sourceId)
                ->whereNull('voided_at')
                ->first(['id']);

            $hasDefectCredit = false;
            if ($journal) {
                $hasDefectCredit = DB::table('journal_lines as jl')
                    ->join('accounts as a', 'a.id', '=', 'jl.account_id')
                    ->where('jl.journal_id', $journal->id)
                    ->where('a.code', JournalService::CODE_INV_DEFECT)
                    ->where('jl.credit', '>', 0)
                    ->exists();
            }

            return [
                'source_id' => $sourceId,
                'return_code' => $return?->code ?: 'SR-' . $sourceId,
                'journal_id' => $journal?->id ? (int) $journal->id : null,
                'qty' => (float) ($movement->qty ?? 0),
                'value' => (float) ($movement->value ?? 0),
                'status' => !$journal
                    ? 'MISSING JOURNAL'
                    : ($hasDefectCredit ? 'OK' : 'REPAIR'),
            ];
        })->values();
    }
}
