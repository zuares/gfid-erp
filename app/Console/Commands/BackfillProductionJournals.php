<?php

namespace App\Console\Commands;

use App\Services\Accounting\ProductionJournalAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillProductionJournals extends Command
{
    protected $signature = 'accounting:backfill-production-journals
        {--apply : Buat jurnal yang belum ada. Default hanya preview}
        {--force : Lewati konfirmasi saat apply}
        {--source=* : Batasi source jurnal produksi yang ingin diproses}
        {--limit= : Batasi jumlah dokumen yang diproses per source}';

    protected $description = 'Backfill jurnal otomatis produksi dari inventory_mutations. Idempotent dan default dry-run.';

    public function handle(ProductionJournalAuditService $audit): int
    {
        $apply = (bool) $this->option('apply');
        $sources = $this->option('source') ?: null;
        $limit = $this->option('limit') !== null ? max((int) $this->option('limit'), 0) : null;

        $rows = $audit->auditRows($sources);

        if ($rows->isEmpty()) {
            $this->error('Source tidak dikenal. Pilihan: ' . implode(', ', array_keys($audit->sourceDefinitions())));
            return self::FAILURE;
        }

        $this->info($apply ? 'MODE APPLY: membuat jurnal yang belum ada' : 'MODE DRY-RUN: preview saja');
        if ($limit !== null && $limit > 0) {
            $this->line('Limit per source: ' . number_format($limit, 0, ',', '.'));
        }

        $this->newLine();
        $this->table(
            ['Source', 'Dokumen', 'Jurnal Aktif', 'Belum Ada', 'Nilai Movement', 'Efek Jurnal'],
            $rows->map(fn($row) => [
                $row['key'],
                number_format((int) $row['document_count'], 0, ',', '.'),
                number_format((int) $row['active_journal_count'], 0, ',', '.'),
                number_format((int) $row['missing_count'], 0, ',', '.'),
                $this->money((float) $row['amount']),
                $row['effect'],
            ])->all()
        );

        $totalMissing = (int) $rows->sum('missing_count');
        if ($totalMissing <= 0) {
            $this->info('Tidak ada jurnal produksi yang perlu di-backfill.');
            return self::SUCCESS;
        }

        if (!$apply) {
            $this->newLine();
            $this->warn('Belum ada data yang diubah. Untuk eksekusi:');
            $this->line('   php artisan accounting:backfill-production-journals --apply');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Lanjut buat jurnal untuk dokumen yang belum ada?', false)) {
            $this->warn('Dibatalkan. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $row) {
            $ids = $audit->missingSourceIds($row['key']);
            if ($limit !== null && $limit > 0) {
                $ids = $ids->take($limit);
            }

            if ($ids->isEmpty()) {
                continue;
            }

            $this->line('');
            $this->line("Backfill {$row['key']} (" . number_format($ids->count(), 0, ',', '.') . ' dokumen)');
            $bar = $this->output->createProgressBar($ids->count());
            $bar->start();

            foreach ($ids as $id) {
                try {
                    $journal = $audit->postMissing($row['key'], (int) $id);
                    $journal ? $created++ : $skipped++;
                } catch (\Throwable $e) {
                    $errors++;
                    Log::warning('Backfill jurnal produksi gagal', [
                        'source' => $row['key'],
                        'source_id' => (int) $id,
                        'message' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->newLine();
        $this->info('Backfill selesai.');
        $this->line('Jurnal dibuat/ketemu: ' . number_format($created, 0, ',', '.'));
        $this->line('Skip tanpa amount/model: ' . number_format($skipped, 0, ',', '.'));
        $this->line('Error: ' . number_format($errors, 0, ',', '.'));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function money(float $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}
