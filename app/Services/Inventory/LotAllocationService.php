<?php

namespace App\Services\Inventory;

use App\Models\InventoryMutation;
use App\Models\Lot;

/**
 * Allocates warehouse stock to traceable lots without changing source data.
 *
 * The caller must already be inside a transaction when the allocation is
 * consumed. Lot balances are calculated from inventory mutations so the
 * allocation follows the same ledger used by stock cards and reversals.
 */
class LotAllocationService
{
    private const EPS = 0.000001;

    /**
     * @return array<int, array{lot_id:int, lot_code:string, qty:float, unit_cost:float}>
     */
    public function allocateFifo(int $warehouseId, int $itemId, float $requiredQty): array
    {
        $requiredQty = round($requiredQty, 8);
        if ($requiredQty <= self::EPS) {
            return [];
        }

        $mutations = InventoryMutation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('item_id', $itemId)
            ->whereNotNull('lot_id')
            ->orderBy('date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        // Agregasi dilakukan di PHP agar hasilnya konsisten di SQLite lokal
        // dan database produksi, sekaligus mempertahankan urutan FIFO mutasi.
        $balances = $mutations
            ->groupBy('lot_id')
            ->map(function ($lotMutations): object {
                $first = $lotMutations->first();

                return (object) [
                    'lot_id' => (int) $first->lot_id,
                    'qty_available' => (float) $lotMutations->sum('qty_change'),
                    'first_movement_date' => $first->date,
                ];
            })
            ->filter(fn (object $balance): bool => $balance->qty_available > self::EPS)
            ->sortBy(fn (object $balance): string => (string) $balance->first_movement_date.'|'.str_pad((string) $balance->lot_id, 12, '0', STR_PAD_LEFT))
            ->values();

        if ($balances->isEmpty()) {
            throw new \RuntimeException(
                "Stok item #{$itemId} di gudang #{$warehouseId} tidak memiliki saldo lot yang dapat ditelusuri. "
                .'Auto BOM dihentikan; isi lot asal terlebih dahulu.'
            );
        }

        $lotIds = $balances->pluck('lot_id')->map(fn ($id): int => (int) $id)->all();
        $lots = Lot::query()
            ->whereIn('id', $lotIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $remaining = $requiredQty;
        $allocations = [];

        foreach ($balances as $balance) {
            if ($remaining <= self::EPS) {
                break;
            }

            $lotId = (int) $balance->lot_id;
            $lot = $lots->get($lotId);
            $available = max((float) $balance->qty_available, 0.0);
            $unitCost = (float) ($lot?->avg_cost ?? 0);

            if (! $lot || $unitCost <= self::EPS) {
                throw new \RuntimeException(
                    "Lot #{$lotId} untuk item #{$itemId} tidak memiliki HPP lot yang valid. Auto BOM dihentikan."
                );
            }

            $qty = min($available, $remaining);
            if ($qty <= self::EPS) {
                continue;
            }

            $allocations[] = [
                'lot_id' => $lotId,
                'lot_code' => (string) $lot->code,
                'qty' => round($qty, 8),
                'unit_cost' => round($unitCost, 4),
            ];
            $remaining = round($remaining - $qty, 8);
        }

        if ($remaining > self::EPS) {
            $allocated = round($requiredQty - $remaining, 8);
            throw new \RuntimeException(
                "Saldo lot item #{$itemId} tidak cukup untuk Auto BOM. "
                ."Butuh {$requiredQty}, lot terlacak hanya {$allocated}; kekurangan {$remaining}."
            );
        }

        return $allocations;
    }
}
