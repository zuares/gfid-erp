<?php

namespace App\Console\Commands;

use App\Services\Accounting\StandaloneProductionPostingService;
use App\Services\Inventory\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Memindahkan reject sebelum jahit historis yang terlanjur masuk REJ-SEW
 * ke REJ-CUT tanpa mengubah mutasi/jurnal sumber.
 *
 * Default hanya preview. Apply membuat pasangan mutasi koreksi yang
 * idempotent berdasarkan inventory_mutation sumber.
 */
class RepairPreSewingRejectLocation extends Command
{
    public const SOURCE = 'sewing_pickup_reject_location_correction';

    protected $signature = 'production:repair-pre-sewing-reject-location
                            {--apply : Terapkan koreksi stok; default hanya preview}
                            {--json : Tampilkan hasil dalam JSON}';

    protected $description = 'Koreksi reject sebelum jahit lama dari REJ-SEW ke REJ-CUT.';

    public function handle(InventoryService $inventory): int
    {
        $rejSewId = DB::table('warehouses')->where('code', 'REJ-SEW')->value('id');
        $rejCutId = DB::table('warehouses')->where('code', 'REJ-CUT')->value('id');

        if (! $rejSewId || ! $rejCutId) {
            return $this->finish([
                'status' => 'BLOCKED',
                'reason' => 'Gudang REJ-SEW dan REJ-CUT wajib tersedia.',
                'candidates' => [],
            ], self::FAILURE);
        }

        $candidates = $this->candidates((int) $rejSewId);
        $payload = [
            'status' => $candidates['blocked'] !== [] ? 'BLOCKED' : ($candidates['rows'] === [] ? 'CLEAN' : 'CANDIDATES'),
            'mode' => $this->option('apply') ? 'APPLY' : 'READ_ONLY',
            'source_type' => StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT_LEGACY,
            'correction_source_type' => self::SOURCE,
            'from' => 'REJ-SEW',
            'to' => 'REJ-CUT',
            'candidates' => $candidates['rows'],
            'blocked' => $candidates['blocked'],
            'summary' => [
                'count' => count($candidates['rows']),
                'qty' => round(array_sum(array_column($candidates['rows'], 'qty')), 6),
                'value' => round(array_sum(array_column($candidates['rows'], 'value')), 2),
            ],
        ];

        if ($candidates['blocked'] !== []) {
            return $this->finish($payload, self::FAILURE);
        }

        if (! $this->option('apply') || $candidates['rows'] === []) {
            return $this->finish($payload, self::SUCCESS);
        }

        DB::transaction(function () use ($inventory, $rejSewId, $rejCutId, $candidates): void {
            foreach ($candidates['rows'] as $candidate) {
                $original = DB::table('inventory_mutations')
                    ->where('id', (int) $candidate['mutation_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $original || (float) $original->qty_change <= 0) {
                    continue;
                }

                if (DB::table('inventory_mutations')
                    ->where('source_type', self::SOURCE)
                    ->where('source_id', (int) $original->id)
                    ->exists()) {
                    continue;
                }

                $qty = (float) $candidate['qty'];
                $unitCost = max((float) ($original->unit_cost ?? 0), 0);
                if ($qty <= 0.000001 || $unitCost <= 0.000001) {
                    throw new \RuntimeException("Mutasi #{$original->id} tidak memiliki qty/HPP yang valid.");
                }

                $notes = "Koreksi lokasi reject sebelum jahit mutasi #{$original->id}: REJ-SEW → REJ-CUT";

                $inventory->stockOut(
                    warehouseId: (int) $rejSewId,
                    itemId: (int) $original->item_id,
                    qty: $qty,
                    date: now()->toDateString(),
                    sourceType: self::SOURCE,
                    sourceId: (int) $original->id,
                    sourceLineId: $original->source_line_id ? (int) $original->source_line_id : null,
                    notes: $notes,
                    allowNegative: false,
                    lotId: $original->lot_id ? (int) $original->lot_id : null,
                    unitCostOverride: $unitCost,
                    affectLotCost: false,
                    cuttingJobBundleId: $original->cutting_job_bundle_id ? (int) $original->cutting_job_bundle_id : null,
                );

                $inventory->stockIn(
                    warehouseId: (int) $rejCutId,
                    itemId: (int) $original->item_id,
                    qty: $qty,
                    date: now()->toDateString(),
                    sourceType: self::SOURCE,
                    sourceId: (int) $original->id,
                    sourceLineId: $original->source_line_id ? (int) $original->source_line_id : null,
                    notes: $notes,
                    lotId: $original->lot_id ? (int) $original->lot_id : null,
                    unitCost: $unitCost,
                    affectLotCost: false,
                    cuttingJobBundleId: $original->cutting_job_bundle_id ? (int) $original->cutting_job_bundle_id : null,
                );
            }
        });

        $payload['status'] = 'APPLIED';
        $payload['message'] = 'Koreksi lokasi reject berhasil diterapkan dan aman diulang.';

        return $this->finish($payload, self::SUCCESS);
    }

    /** @return array{rows: array<int,array<string,mixed>>, blocked: array<int,array<string,mixed>>} */
    private function candidates(int $rejSewId): array
    {
        $rows = [];
        $blocked = [];

        $originals = DB::table('inventory_mutations as im')
            ->where('im.source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT_LEGACY)
            ->where('im.warehouse_id', $rejSewId)
            ->where('im.qty_change', '>', 0)
            ->orderBy('im.id')
            ->get();

        foreach ($originals as $original) {
            if (DB::table('inventory_mutations')
                ->where('source_type', self::SOURCE)
                ->where('source_id', (int) $original->id)
                ->exists()) {
                continue;
            }

            // Kurangi reversal lama bila transaksi historis pernah di-VOID.
            $voidQty = (float) DB::table('inventory_mutations')
                ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_REJECT_LEGACY.'_void')
                ->where('source_id', (int) $original->source_id)
                ->where('source_line_id', $original->source_line_id)
                ->where('warehouse_id', $rejSewId)
                ->where('item_id', (int) $original->item_id)
                ->sum('qty_change');
            $qty = round((float) $original->qty_change + $voidQty, 6);

            if ($qty <= 0.000001) {
                continue;
            }

            $stock = (float) DB::table('inventory_stocks')
                ->where('warehouse_id', $rejSewId)
                ->where('item_id', (int) $original->item_id)
                ->value('qty');

            if ($stock + 0.000001 < $qty) {
                $blocked[] = [
                    'mutation_id' => (int) $original->id,
                    'item_id' => (int) $original->item_id,
                    'qty' => $qty,
                    'stock_rej_sew' => $stock,
                    'reason' => 'Stok REJ-SEW tidak cukup untuk koreksi.',
                ];

                continue;
            }

            $unitCost = max((float) ($original->unit_cost ?? 0), 0);
            if ($unitCost <= 0.000001) {
                $blocked[] = [
                    'mutation_id' => (int) $original->id,
                    'item_id' => (int) $original->item_id,
                    'qty' => $qty,
                    'reason' => 'HPP mutasi sumber tidak tersedia.',
                ];

                continue;
            }

            $rows[] = [
                'mutation_id' => (int) $original->id,
                'pickup_id' => (int) $original->source_id,
                'pickup_line_id' => $original->source_line_id ? (int) $original->source_line_id : null,
                'item_id' => (int) $original->item_id,
                'bundle_id' => $original->cutting_job_bundle_id ? (int) $original->cutting_job_bundle_id : null,
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'value' => round($qty * $unitCost, 2),
            ];
        }

        return compact('rows', 'blocked');
    }

    private function finish(array $payload, int $exitCode): int
    {
        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $exitCode;
        }

        $mode = $payload['mode'] ?? 'READ_ONLY';
        $this->info("Status: {$payload['status']} ({$mode})");
        if (! empty($payload['reason'])) {
            $this->error($payload['reason']);
        }
        if (! empty($payload['blocked'])) {
            $this->table(['Mutasi', 'Item', 'Qty', 'Stok REJ-SEW', 'Alasan'], array_map(
                fn (array $row): array => [$row['mutation_id'], $row['item_id'], $row['qty'], $row['stock_rej_sew'] ?? '-', $row['reason']],
                $payload['blocked'],
            ));
        }
        if (isset($payload['summary'])) {
            $this->line("Kandidat: {$payload['summary']['count']} · Qty: {$payload['summary']['qty']} · Nilai: Rp {$payload['summary']['value']}");
        }

        return $exitCode;
    }
}
