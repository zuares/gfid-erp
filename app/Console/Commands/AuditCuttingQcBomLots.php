<?php

namespace App\Console\Commands;

use App\Models\InventoryMutation;
use App\Services\Accounting\JournalService;
use Illuminate\Console\Command;

class AuditCuttingQcBomLots extends Command
{
    protected $signature = 'production:audit-bom-lots
        {--job= : Batasi satu Cutting Job ID}
        {--json : Cetak laporan JSON}';

    protected $description = 'Audit read-only trace lot pada pemakaian BOM sewing_supply QC Cutting.';

    public function handle(): int
    {
        $query = InventoryMutation::query()
            ->where('source_type', JournalService::SRC_CUTTING_QC_SEWING_SUPPLY)
            ->where('direction', 'out')
            ->with(['lot:id,code,item_id,avg_cost', 'item:id,code,name,unit'])
            ->orderBy('source_id')
            ->orderBy('id');

        if ($this->option('job') !== null) {
            $query->where('source_id', (int) $this->option('job'));
        }

        $mutations = $query->get();
        $rows = $mutations
            ->groupBy('source_id')
            ->map(function ($items, $sourceId): array {
                $missing = $items->filter(fn (InventoryMutation $mutation): bool => ! $mutation->lot_id);

                return [
                    'job_id' => (int) $sourceId,
                    'mutation_count' => $items->count(),
                    'lot_filled_count' => $items->whereNotNull('lot_id')->count(),
                    'lot_missing_count' => $missing->count(),
                    'qty_out' => round(abs((float) $items->sum('qty_change')), 4),
                    'value_out' => round(abs((float) $items->sum('total_cost')), 2),
                    'lots' => $items->map(fn (InventoryMutation $mutation): array => [
                        'mutation_id' => (int) $mutation->id,
                        'item_code' => (string) ($mutation->item?->code ?? '-'),
                        'item_name' => (string) ($mutation->item?->name ?? '-'),
                        'lot_id' => $mutation->lot_id ? (int) $mutation->lot_id : null,
                        'lot_code' => (string) ($mutation->lot?->code ?? '-'),
                        'qty' => round(abs((float) $mutation->qty_change), 4),
                        'unit_cost' => round((float) $mutation->unit_cost, 4),
                        'total_cost' => round(abs((float) $mutation->total_cost), 2),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        $missingCount = (int) collect($rows)->sum('lot_missing_count');
        $report = [
            'scope' => [
                'job_id' => $this->option('job') !== null ? (int) $this->option('job') : null,
                'source_type' => JournalService::SRC_CUTTING_QC_SEWING_SUPPLY,
                'read_only' => true,
            ],
            'summary' => [
                'jobs' => count($rows),
                'mutations' => $mutations->count(),
                'lot_filled' => $mutations->whereNotNull('lot_id')->count(),
                'lot_missing' => $missingCount,
                'status' => $missingCount === 0 ? 'PASS' : 'REVIEW_REQUIRED',
            ],
            'rows' => $rows,
            'message' => $missingCount === 0
                ? 'Semua pemakaian Auto BOM QC Cutting memiliki lot asal.'
                : 'Ada pemakaian Auto BOM tanpa lot asal. Tidak ada backfill atau perubahan data yang dilakukan.',
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->info('Audit lot Auto BOM QC Cutting — READ ONLY');
            $this->table(
                ['Job', 'Mutasi', 'Lot terisi', 'Lot kosong', 'Qty out', 'Nilai', 'Status'],
                collect($rows)->map(fn (array $row): array => [
                    $row['job_id'],
                    $row['mutation_count'],
                    $row['lot_filled_count'],
                    $row['lot_missing_count'],
                    number_format($row['qty_out'], 4, ',', '.'),
                    'Rp '.number_format($row['value_out'], 2, ',', '.'),
                    $row['lot_missing_count'] === 0 ? 'PASS' : 'REVIEW',
                ])->all(),
            );
            $this->line('Status: '.$report['summary']['status']);
            $this->warn('Laporan ini read-only; tidak melakukan backfill, posting, atau perubahan stok.');
        }

        return $missingCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
