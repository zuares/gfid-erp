<?php

namespace App\Services\Inventory;

use App\Exceptions\AutoBomLotShortageException;
use App\Models\InventoryMutation;
use App\Models\Item;
use App\Models\Lot;
use App\Models\Warehouse;

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
            throw new AutoBomLotShortageException(
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
            $item = Item::query()->find($itemId, ['id', 'code', 'name', 'unit']);
            $warehouse = Warehouse::query()->find($warehouseId, ['id', 'code', 'name']);
            $itemLabel = trim(implode(' — ', array_filter([
                $item?->code,
                $item?->name,
            ]))) ?: "Item #{$itemId}";
            $warehouseLabel = trim(implode(' — ', array_filter([
                $warehouse?->code,
                $warehouse?->name,
            ]))) ?: "Gudang #{$warehouseId}";
            $unit = $item?->unit ?: 'unit';

            throw new AutoBomLotShortageException(
                "Auto BOM belum dapat diposting karena stok kelengkapan kurang.\n"
                ."Item: {$itemLabel}\n"
                ."Gudang: {$warehouseLabel}\n"
                .'Kebutuhan QC: '.self::formatQty($requiredQty)." {$unit}\n"
                .'Saldo lot yang dapat dipakai: '.self::formatQty($allocated)." {$unit}\n"
                .'Kekurangan: '.self::formatQty($remaining)." {$unit}\n\n"
                .'Tindakan: tambahkan stok dengan lot asal di gudang tersebut, atau sesuaikan Qty OK/BOM. '
                .'Saldo fisik tanpa lot tidak dapat dipakai untuk Auto BOM. Auto BOM tidak diposting; stok dan jurnal tidak berubah.',
                item: $itemLabel,
                warehouse: $warehouseLabel,
                required: self::formatQty($requiredQty)." {$unit}",
                available: self::formatQty($allocated)." {$unit}",
                shortage: self::formatQty($remaining)." {$unit}",
                itemId: $itemId,
                shortageQty: $remaining,
            );
        }

        return $allocations;
    }

    private static function formatQty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 8, ',', '.'), '0'), ',');
    }
}
