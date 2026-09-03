<?php

namespace App\Console\Commands;

use App\Services\Production\ProductionFlowAuditService;
use Illuminate\Console\Command;

class AuditProductionFlow extends Command
{
    protected $signature = 'production:audit-flow
        {--bundle= : Audit satu cutting_job_bundle_id}
        {--since= : Audit cutting job mulai tanggal YYYY-MM-DD}
        {--json : Cetak laporan JSON}
        {--limit=200 : Maksimum temuan yang dicetak}';

    protected $description = 'Audit read-only alur cutting sampai QC sewing/reject per bundle.';

    public function handle(ProductionFlowAuditService $audit): int
    {
        $limit = max((int) $this->option('limit'), 1);
        $report = $audit->audit(
            $this->option('bundle') ? (int) $this->option('bundle') : null,
            $this->option('since') ?: null,
            $limit,
        );

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $report['summary']['status'] === 'PASS' ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Audit alur produksi — READ ONLY');
        $this->line('Scope bundle: ' . ($report['scope']['bundle_id'] ?? 'semua'));
        $this->line('Jumlah bundle: ' . ($report['summary']['bundles'] ?? 0));
        $this->newLine();

        if (!empty($report['issues'])) {
            $this->table(
                ['Severity', 'Code', 'Bundle', 'Item', 'Expected', 'Actual', 'Variance', 'Temuan'],
                collect($report['issues'])->map(fn ($row) => [
                    $row['severity'], $row['code'], $row['bundle_code'] ?: ('#' . $row['bundle_id']),
                    $row['item_code'], $this->qty($row['expected']), $this->qty($row['actual']),
                    $this->qty($row['variance']), $row['message'],
                ])->all()
            );

            $this->newLine();
            $this->warn('Jalur repair ada di field repair laporan JSON. Tidak ada data yang diubah.');
        } else {
            $this->info('Tidak ada mismatch per bundle.');
        }

        if (!empty($report['unassigned_movements'])) {
            $this->newLine();
            $this->warn('Mutasi produksi tanpa cutting_job_bundle_id:');
            $this->table(
                ['Source', 'Gudang', 'Jumlah Mutasi', 'Qty', 'Nilai'],
                collect($report['unassigned_movements'])->map(fn ($row) => [
                    $row['source_type'], $row['warehouse_code'] ?: '-', $row['mutation_count'],
                    $this->qty($row['qty']), $this->money($row['value']),
                ])->all()
            );
        }

        $this->newLine();
        $this->line('Status: ' . $report['summary']['status'] . ' — ' . $report['summary']['issues'] . ' temuan.');

        return $report['summary']['status'] === 'PASS' ? self::SUCCESS : self::FAILURE;
    }

    private function qty(float|int|string|null $value): string
    {
        return number_format((float) $value, 4, ',', '.');
    }

    private function money(float|int|string|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 2, ',', '.');
    }
}
