<?php

namespace App\Services\Costing;

use App\Models\Item;
use App\Models\ItemCostSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ActiveHppService
{
    /**
     * Sinkronkan HPP aktif item berdasarkan aturan:
     * - GRN posted terakhir yang masuk stok → harga GRN terakhir.
     * - Tidak ada GRN → HPP awal master item.
     */
    public function sync(Item $item, ?int $excludeGrnId = null): array
    {
        $meta = $item->activeUnitCostMeta($excludeGrnId);
        $unitCost = round((float) ($meta['unit_cost'] ?? 0), 2);

        if ($unitCost <= 0) {
            return ['changed' => false, 'reason' => 'no_cost', 'meta' => $meta];
        }

        return DB::transaction(function () use ($item, $meta, $unitCost): array {
            $active = ItemCostSnapshot::query()
                ->where('item_id', $item->id)
                ->whereNull('warehouse_id')
                ->where('is_active', true)
                ->orderByDesc('snapshot_date')
                ->orderByDesc('id')
                ->first();

            $sameSource = $active
                && (string) $active->reference_type === (string) $meta['reference_type']
                && (int) ($active->reference_id ?? 0) === (int) ($meta['reference_id'] ?? 0)
                && abs((float) $active->unit_cost - $unitCost) < 0.0001;

            $itemChanged = abs((float) $item->hpp - $unitCost) >= 0.0001;
            if ($sameSource && !$itemChanged) {
                return ['changed' => false, 'reason' => 'already_synced', 'meta' => $meta];
            }

            ItemCostSnapshot::query()
                ->where('item_id', $item->id)
                ->whereNull('warehouse_id')
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $snapshotDate = !empty($meta['date'])
                ? Carbon::parse($meta['date'])->toDateString()
                : Carbon::now()->toDateString();

            ItemCostSnapshot::create([
                'item_id' => $item->id,
                'warehouse_id' => null,
                'snapshot_date' => $snapshotDate,
                'reference_type' => $meta['reference_type'],
                'reference_id' => $meta['reference_id'],
                'qty_basis' => 0,
                'rm_unit_cost' => $unitCost,
                'cutting_unit_cost' => 0,
                'sewing_unit_cost' => 0,
                'finishing_unit_cost' => 0,
                'packaging_unit_cost' => 0,
                'overhead_unit_cost' => 0,
                'unit_cost' => $unitCost,
                'notes' => $meta['notes'],
                'is_active' => true,
                'created_by' => auth()->id() ?? null,
            ]);

            $item->update(['hpp' => $unitCost]);

            return ['changed' => true, 'reason' => 'synced', 'meta' => $meta];
        });
    }

    public function syncAll(?int $onlyItemId = null, bool $dryRun = false): array
    {
        $summary = ['checked' => 0, 'changed' => 0, 'from_grn' => 0, 'from_master' => 0, 'no_cost' => 0];

        Item::query()
            ->when($onlyItemId, fn ($q) => $q->whereKey($onlyItemId))
            ->orderBy('id')
            ->chunkById(100, function ($items) use (&$summary, $dryRun): void {
                foreach ($items as $item) {
                    $summary['checked']++;
                    $meta = $item->activeUnitCostMeta();
                    $unitCost = (float) ($meta['unit_cost'] ?? 0);
                    if ($unitCost <= 0) {
                        $summary['no_cost']++;
                        continue;
                    }

                    $summary[$meta['reference_type'] === 'purchase_receipt' ? 'from_grn' : 'from_master']++;
                    if (!$dryRun) {
                        $result = $this->sync($item);
                        if ($result['changed']) {
                            $summary['changed']++;
                        }
                    }
                }
            });

        return $summary;
    }
}
