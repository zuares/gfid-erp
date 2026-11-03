<?php

namespace App\Console\Commands;

use App\Services\Accounting\ProductionAccountingAuditService;
use Illuminate\Console\Command;

class AuditProductionAccounting extends Command
{
    protected $signature = 'production:audit-accounting
        {--from= : Tanggal mulai YYYY-MM-DD}
        {--to= : Tanggal akhir YYYY-MM-DD}
        {--stage=* : Batasi stage audit}
        {--json : Cetak laporan JSON}
        {--limit=200 : Maksimum detail dokumen per stage}';

    protected $description = 'Audit read-only inventory, jurnal legacy, dan mirror akuntansi baru per tahap produksi.';

    public function handle(ProductionAccountingAuditService $audit): int
    {
        $rawStages = $this->option('stage') ?: [];
        $requestedStages = is_array($rawStages) ? $rawStages : [$rawStages];
        [$stages, $invalidStages] = $this->normalizeStages(
            $requestedStages,
            array_keys($audit->sourceDefinitions()),
        );

        if ($invalidStages !== []) {
            $report = [
                'scope' => [
                    'from' => $this->option('from') ?: null,
                    'to' => $this->option('to') ?: null,
                    'stages' => $requestedStages,
                    'standalone_required' => (bool) config('accounting.standalone_production_audit_required', false),
                ],
                'summary' => [
                    'status' => 'BLOCKED',
                    'invalid_stages' => $invalidStages,
                ],
                'message' => 'Stage audit tidak dikenal. Gunakan nama stage yang tersedia atau alias cutting.',
            ];

            if ($this->option('json')) {
                $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $this->error($report['message'].' Valid: '.implode(', ', array_keys($audit->sourceDefinitions())));
            }

            return self::FAILURE;
        }

        $report = $audit->report(
            $this->option('from') ?: null,
            $this->option('to') ?: null,
            $stages ?: null,
            max((int) $this->option('limit'), 1),
        );

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $report['summary']['status'] === 'PASS' ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Audit akuntansi produksi — READ ONLY');
        $this->line('Periode: '.($report['scope']['from'] ?: 'awal').' s/d '.($report['scope']['to'] ?: 'akhir'));
        $this->newLine();
        $this->table(
            ['Stage', 'Dokumen', 'Mutasi', 'Legacy Aktif', 'Legacy Missing', 'Mirror Aktif', 'Mirror Missing', 'Selisih', 'Status'],
            collect($report['rows'])->map(fn (array $row): array => [
                $row['label'],
                number_format((int) $row['document_count'], 0, ',', '.'),
                number_format((int) $row['movement_count'], 0, ',', '.'),
                number_format((int) $row['legacy_active_journal_count'], 0, ',', '.'),
                number_format((int) $row['legacy_missing_count'], 0, ',', '.'),
                number_format((int) $row['standalone_active_journal_count'], 0, ',', '.'),
                number_format((int) $row['standalone_missing_count'], 0, ',', '.'),
                $this->money((float) $row['stock_vs_legacy_variance']),
                $row['status'],
            ])->all(),
        );

        $this->newLine();
        $this->line('Status: '.$report['summary']['status']);
        $this->line('Mapping missing: '.number_format((int) $report['summary']['mapping_missing'], 0, ',', '.'));
        $this->line('Legacy journal missing: '.number_format((int) $report['summary']['legacy_missing'], 0, ',', '.'));
        $this->line('Mirror journal missing: '.number_format((int) $report['summary']['standalone_missing'], 0, ',', '.'));
        $this->line('Historical mirror gap: '.number_format((int) $report['summary']['standalone_historical_gaps'], 0, ',', '.'));
        $this->warn('Laporan ini tidak membuat jurnal, tidak mengubah stok, dan tidak melakukan backfill.');

        return $report['summary']['status'] === 'PASS' ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<int, string> $requested @param array<int, string> $valid */
    private function normalizeStages(array $requested, array $valid): array
    {
        $aliases = [
            'cutting' => ['cutting_material', 'cutting_qc'],
        ];
        $expanded = collect($requested)
            ->flatMap(fn (string $stage): array => $aliases[$stage] ?? [$stage])
            ->unique()
            ->values();
        $invalid = $expanded->diff($valid)->values()->all();

        return [$expanded->intersect($valid)->values()->all(), $invalid];
    }

    private function money(float $value): string
    {
        return 'Rp '.number_format($value, 2, ',', '.');
    }
}
