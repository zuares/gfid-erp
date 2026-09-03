<?php

namespace App\Console\Commands;

use App\Models\SewingReturn;
use App\Services\Accounting\JournalService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RepairDefectRejectJournals extends Command
{
    protected $signature = 'accounting:repair-defect-reject-journals
                            {--source-id=* : Batasi source sewing return tertentu}
                            {--limit= : Batasi jumlah dokumen}
                            {--apply : Void/repost journal yang salah atau membuat yang hilang}
                            {--force : Lewati konfirmasi saat apply}';

    protected $description = 'Audit/backfill jurnal reject berdasarkan HPP reject aktual, bukan total QC.';

    public function handle(JournalService $journalService): int
    {
        $sourceIds = $this->sourceIds();
        if ($sourceIds->isEmpty()) {
            $this->info('Tidak ada mutasi reject yang perlu diperiksa.');
            return self::SUCCESS;
        }

        $rows = $this->auditRows($sourceIds);
        $limit = $this->option('limit') !== null
            ? max((int) $this->option('limit'), 0)
            : null;
        if ($limit !== null && $limit > 0) {
            $rows = $rows->take($limit)->values();
        }

        $actionRows = $rows->whereIn('status', ['MISSING', 'REPAIR'])->values();

        $this->info($this->option('apply')
            ? 'MODE APPLY: backfill/koreksi jurnal reject'
            : 'MODE DRY-RUN: preview saja');
        $this->newLine();
        $this->table(
            ['Return', 'Source ID', 'Journal', 'HPP Reject', 'HPP Journal', 'Status'],
            $rows->map(fn (array $row) => [
                $row['return_code'],
                $row['source_id'],
                $row['journal_id'] ?: '-',
                $this->money($row['expected']),
                $this->money($row['actual']),
                $row['status'],
            ])->all()
        );

        if ($actionRows->isEmpty()) {
            $this->info('Semua jurnal reject sudah sesuai HPP reject aktual.');
            return self::SUCCESS;
        }

        if (!$this->option('apply')) {
            $this->newLine();
            $this->warn('Belum ada data yang diubah. Untuk eksekusi gunakan:');
            $this->line('  php artisan accounting:repair-defect-reject-journals --apply');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Terapkan backfill/koreksi jurnal reject?', false)) {
            $this->warn('Dibatalkan. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        $createdOrRepaired = 0;
        $errors = 0;

        foreach ($actionRows as $row) {
            try {
                DB::transaction(function () use ($journalService, $row): void {
                    if ($row['status'] === 'REPAIR' && $row['journal_id']) {
                        $journalService->voidById(
                            (int) $row['journal_id'],
                            'Koreksi HPP reject: jurnal harus memakai qty reject aktual, bukan total QC.'
                        );
                    }

                    $return = SewingReturn::query()->findOrFail((int) $row['source_id']);
                    $posted = $journalService->postSewingReturnReject($return);

                    if (!$posted) {
                        throw new \RuntimeException('Tidak ada mutasi HPP reject yang bisa diposting.');
                    }
                });

                $createdOrRepaired++;
            } catch (\Throwable $e) {
                $errors++;
                $this->error("Gagal {$row['return_code']}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('Backfill/koreksi selesai.');
        $this->line('Berhasil: ' . number_format($createdOrRepaired, 0, ',', '.'));
        $this->line('Gagal: ' . number_format($errors, 0, ',', '.'));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function sourceIds(): Collection
    {
        $requested = collect($this->option('source-id'))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $query = DB::table('inventory_mutations')
            ->whereNotNull('source_id')
            ->where(function ($query): void {
                $query
                    ->where(function ($query): void {
                        $query->where('source_type', 'sewing_qc_reject')
                            ->where('qty_change', '>', 0);
                    })
                    ->orWhere(function ($query): void {
                        $query->where('source_type', JournalService::SRC_SEWING_RETURN_REJECT)
                            ->where('qty_change', '!=', 0);
                    });
            })
            ->distinct()
            ->orderBy('source_id')
            ->pluck('source_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return $requested->isEmpty()
            ? $query
            : $query->intersect($requested)->values();
    }

    private function auditRows(Collection $sourceIds): Collection
    {
        return $sourceIds->map(function (int $sourceId): array {
            $return = DB::table('sewing_returns')->where('id', $sourceId)->first(['code']);
            $expected = $this->expectedDefectDebit($sourceId);

            $journal = DB::table('journals')
                ->where('source_type', JournalService::SRC_SEWING_RETURN_REJECT)
                ->where('source_id', $sourceId)
                ->whereNull('voided_at')
                ->first(['id']);

            $actual = $journal
                ? round((float) DB::table('journal_lines as jl')
                    ->join('accounts as a', 'a.id', '=', 'jl.account_id')
                    ->where('jl.journal_id', $journal->id)
                    ->where('a.code', JournalService::CODE_INV_DEFECT)
                    ->sum('jl.debit'), 2)
                : 0.0;

            $status = !$journal
                ? 'MISSING'
                : (abs($expected - $actual) > 0.01 ? 'REPAIR' : 'OK');

            return [
                'source_id' => $sourceId,
                'return_code' => $return?->code ?: 'SR-' . $sourceId,
                'journal_id' => $journal?->id ? (int) $journal->id : null,
                'expected' => $expected,
                'actual' => $actual,
                'status' => $status,
            ];
        })->values();
    }

    private function expectedDefectDebit(int $sourceId): float
    {
        $outCost = round((float) DB::table('inventory_mutations')
            ->where('source_type', JournalService::SRC_SEWING_RETURN_REJECT)
            ->where('source_id', $sourceId)
            ->where('qty_change', '<', 0)
            ->sum(DB::raw('ABS(COALESCE(total_cost, 0))')), 2);

        $inCost = round((float) DB::table('inventory_mutations')
            ->where('source_type', JournalService::SRC_SEWING_RETURN_REJECT)
            ->where('source_id', $sourceId)
            ->where('qty_change', '>', 0)
            ->sum(DB::raw('ABS(COALESCE(total_cost, 0))')), 2);

        if ($outCost > 0 || $inCost > 0) {
            return $outCost > 0 ? $outCost : $inCost;
        }

        return round((float) DB::table('inventory_mutations')
            ->where('source_type', 'sewing_qc_reject')
            ->where('source_id', $sourceId)
            ->where('qty_change', '>', 0)
            ->sum(DB::raw('ABS(COALESCE(total_cost, 0))')), 2);
    }

    private function money(float $value): string
    {
        return 'Rp ' . number_format($value, 2, ',', '.');
    }
}
