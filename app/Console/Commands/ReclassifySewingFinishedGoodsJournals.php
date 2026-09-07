<?php

namespace App\Console\Commands;

use App\Models\Journal;
use App\Models\SewingReturn;
use App\Services\Accounting\JournalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReclassifySewingFinishedGoodsJournals extends Command
{
    protected $signature = 'accounting:reclassify-sewing-fg
        {--from=2026-09-01 : Tanggal mulai, format YYYY-MM-DD}
        {--to= : Tanggal akhir, format YYYY-MM-DD}
        {--apply : Void jurnal lama dan posting ulang ke 1203}
        {--force : Lewati konfirmasi saat apply}
        {--limit= : Batasi jumlah pasangan dokumen yang diproses}';

    protected $description = 'Reclassify jurnal setoran jahit/QC lolos dari WIP ke Persediaan Barang Jadi.';

    public function handle(JournalService $journalService): int
    {
        $from = (string) ($this->option('from') ?: '2026-09-01');
        $to = $this->option('to') ? (string) $this->option('to') : null;

        if (!$this->validDate($from, 'from') || !$this->validDate($to, 'to')) {
            return self::FAILURE;
        }

        if ($to !== null && $to < $from) {
            $this->error('Tanggal --to tidak boleh lebih kecil dari --from.');
            return self::FAILURE;
        }

        $fgAccountId = DB::table('accounts')->where('code', JournalService::CODE_INV_FG)->value('id');
        if (!$fgAccountId) {
            $this->error('Akun 1203 Persediaan Barang Jadi tidak ditemukan.');
            return self::FAILURE;
        }

        $candidates = $this->candidates($from, $to);
        $limit = $this->option('limit') !== null ? max((int) $this->option('limit'), 0) : null;
        if ($limit !== null && $limit > 0) {
            $candidates = $candidates->take($limit)->values();
        }

        $rows = $candidates->map(function (array $candidate) use ($fgAccountId): array {
            $journal = Journal::query()
                ->where('source_type', $candidate['journal_source_type'])
                ->where('source_id', $candidate['source_id'])
                ->whereNull('voided_at')
                ->with('lines')
                ->first();

            $correct = $journal && $journal->lines->contains(function ($line) use ($fgAccountId) {
                return (int) $line->account_id === (int) $fgAccountId && (float) $line->debit > 0;
            });

            return [
                ...$candidate,
                'journal_id' => $journal?->id,
                'status' => $correct ? 'OK' : ($journal ? 'RECLASSIFY' : 'MISSING'),
            ];
        });

        $this->info($this->option('apply')
            ? 'MODE APPLY: jurnal lama akan di-void dan diposting ulang ke akun 1203.'
            : 'MODE DRY-RUN: preview saja, belum ada data yang diubah.');
        $this->line('Periode: ' . $from . ' s/d ' . ($to ?: 'hari ini'));
        $this->newLine();

        if ($rows->isEmpty()) {
            $this->info('Tidak ada setoran jahit/QC lolos pada periode tersebut.');
            return self::SUCCESS;
        }

        $this->table(
            ['Return', 'Tanggal', 'Proses', 'Jurnal', 'Nilai', 'Status'],
            $rows->map(fn (array $row) => [
                $row['return_code'],
                $row['date'],
                $row['process'],
                $row['journal_id'] ?: '-',
                $this->money($row['value']),
                $row['status'],
            ])->all()
        );

        $toRepair = $rows->whereIn('status', ['RECLASSIFY', 'MISSING'])->values();
        if ($toRepair->isEmpty()) {
            $this->info('Semua jurnal pada periode tersebut sudah menggunakan akun 1203.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Dokumen yang perlu diperbaiki: ' . number_format($toRepair->count(), 0, ',', '.'));

        if (!$this->option('apply')) {
            $this->warn('Belum ada perubahan. Setelah review, jalankan:');
            $this->line('  php artisan accounting:reclassify-sewing-fg --from=' . $from . ' --apply');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Lanjut void/repost jurnal di atas?', false)) {
            $this->warn('Dibatalkan. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        $reclassified = 0;
        $posted = 0;
        $errors = 0;

        foreach ($toRepair as $row) {
            try {
                DB::transaction(function () use ($journalService, $row, &$reclassified, &$posted): void {
                    if ($row['journal_id']) {
                        $journalService->voidById(
                            (int) $row['journal_id'],
                            'Reclassify setoran jahit/QC lolos ke Persediaan Barang Jadi 1203.'
                        );
                        $reclassified++;
                    }

                    $return = SewingReturn::query()->findOrFail((int) $row['source_id']);
                    $journal = $row['process'] === 'rework'
                        ? $journalService->postSewingReworkOk($return)
                        : $journalService->postSewingReturnOk($return);

                    if (!$journal) {
                        throw new \RuntimeException('Posting ulang tidak menghasilkan jurnal.');
                    }

                    $posted++;
                });
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('Reclassify jurnal setoran jahit ke barang jadi gagal', [
                    'source_id' => (int) $row['source_id'],
                    'process' => $row['process'],
                    'message' => $e->getMessage(),
                ]);
                $this->error($row['return_code'] . ': ' . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Repair selesai.');
        $this->line('Jurnal lama di-void: ' . number_format($reclassified, 0, ',', '.'));
        $this->line('Jurnal baru diposting: ' . number_format($posted, 0, ',', '.'));
        $this->line('Error: ' . number_format($errors, 0, ',', '.'));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function candidates(string $from, ?string $to)
    {
        return DB::table('inventory_mutations as im')
            ->join('sewing_returns as sr', 'sr.id', '=', 'im.source_id')
            ->where('im.qty_change', '>', 0)
            ->whereIn('im.source_type', [
                JournalService::SRC_SEWING_RETURN_OK,
                'sewing_qc_in',
                JournalService::SRC_SEWING_REWORK_OK,
            ])
            ->whereDate('sr.date', '>=', $from)
            ->when($to, fn ($query) => $query->whereDate('sr.date', '<=', $to))
            ->select('sr.id as source_id', 'sr.code as return_code', 'sr.date', 'im.source_type')
            ->distinct()
            ->orderBy('sr.date')
            ->orderBy('sr.id')
            ->get()
            ->map(fn ($row) => [
                'source_id' => (int) $row->source_id,
                'return_code' => (string) $row->return_code,
                'date' => (string) $row->date,
                'process' => $row->source_type === JournalService::SRC_SEWING_REWORK_OK ? 'rework' : 'normal',
                'journal_source_type' => $row->source_type === JournalService::SRC_SEWING_REWORK_OK
                    ? JournalService::SRC_SEWING_REWORK_OK
                    : JournalService::SRC_SEWING_RETURN_OK,
                'value' => (float) DB::table('inventory_mutations')
                    ->where('source_id', (int) $row->source_id)
                    ->where('source_type', $row->source_type)
                    ->where('qty_change', '>', 0)
                    ->sum(DB::raw('ABS(total_cost)')),
            ])
            ->unique(fn (array $row) => $row['journal_source_type'] . ':' . $row['source_id'])
            ->values();
    }

    private function validDate(?string $value, string $option): bool
    {
        if (!$value) {
            return true;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->error("Option --{$option} harus format YYYY-MM-DD.");
            return false;
        }

        try {
            Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            $this->error("Option --{$option} bukan tanggal yang valid.");
            return false;
        }

        return true;
    }

    private function money(float $value): string
    {
        return 'Rp ' . number_format($value, 2, ',', '.');
    }
}
