<?php

namespace App\Services\Inventory;

use App\Models\InventoryMutation;
use App\Models\Lot;
use Illuminate\Support\Facades\DB;

class LotCostService
{
    /**
     * Tambah stok ke LOT (moving average per LOT).
     */
    public function addReceipt(int $lotId, float | int | string $qty, float | int | string $unitCost): Lot
    {
        return DB::transaction(function () use ($lotId, $qty, $unitCost) {

            /** @var Lot $lot */
            $lot = Lot::lockForUpdate()->findOrFail($lotId);

            $qty = $this->num($qty);
            $unitCost = $this->num($unitCost);

            if ($qty <= 0) {
                return $lot;
            }

            $incomingTotal = $qty * $unitCost;

            $oldQty = $this->num($lot->qty_onhand);
            $oldTotal = $this->num($lot->total_cost);

            $newQty = $oldQty + $qty;
            $newTotal = $oldTotal + $incomingTotal;

            $lot->qty_onhand = $newQty;
            $lot->total_cost = $newTotal;
            $lot->avg_cost = $newQty > 0 ? round($newTotal / $newQty, 4) : 0;

            // initial hanya jika LOT benar-benar masih kosong
            if ($oldQty == 0 && $oldTotal == 0) {
                $lot->initial_qty = $qty;
                $lot->initial_cost = $incomingTotal;
            }

            $lot->save();

            return $lot;
        });
    }

    /**
     * Konsumsi stok LOT (moving average).
     */
    public function consume(int $lotId, float | int | string $qty): Lot
    {
        return DB::transaction(function () use ($lotId, $qty) {

            /** @var Lot $lot */
            $lot = Lot::lockForUpdate()->findOrFail($lotId);

            $qty = $this->num($qty);
            if ($qty <= 0) {
                return $lot;
            }

            $oldQty = $this->num($lot->qty_onhand);
            $oldTotal = $this->num($lot->total_cost);
            $avg = $this->num($lot->avg_cost);

            if ($oldQty < $qty) {
                throw new \RuntimeException(
                    "Qty LOT tidak cukup. LOT {$lot->code}, saldo: {$oldQty}, minta: {$qty}"
                );
            }

            $costOut = $qty * $avg;

            $newQty = $oldQty - $qty;
            $newTotal = $oldTotal - $costOut;

            $lot->qty_onhand = $newQty;
            $lot->total_cost = $newTotal;
            $lot->avg_cost = $newQty > 0 ? round($newTotal / $newQty, 4) : $avg;

            if ($newQty == 0) {
                $lot->status = 'closed';
            }

            $lot->save();

            return $lot;
        });
    }

    /**
     * Ambil avg cost LOT.
     */
    public function getAvgCost(int $lotId): float
    {
        return (float) Lot::findOrFail($lotId)->avg_cost;
    }

    /**
     * Normalisasi angka dari input atau DB.
     */
    protected function num(float | int | string | null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // format Indonesia 1.234,56
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        // format ribuan 1.234.567
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            return (float) str_replace('.', '', $value);
        }

        return (float) $value;
    }

    /**
     * Rebuild ulang 1 LOT menggunakan semua mutasi bertipe LOT.
     */
    public function rebuildLotFromMutations(int $lotId): Lot
    {
        return DB::transaction(function () use ($lotId) {

            /** @var Lot $lot */
            $lot = Lot::lockForUpdate()->findOrFail($lotId);

            // RESET LOT
            $lot->qty_onhand = 0;
            $lot->total_cost = 0;
            $lot->avg_cost = 0;
            $lot->initial_qty = 0;
            $lot->initial_cost = 0;
            $lot->status = 'open';
            $lot->save();

            // ambil semua mutasi yg terkait LOT ini
            $mutations = InventoryMutation::query()
                ->where('lot_id', $lotId)
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            foreach ($mutations as $m) {

                // ⛔ SANGAT PENTING!
                // qty dari DB jangan lewat $this->num() (bisa salah tafsir 25.000 jadi 25000)
                $qty = (float) ($m->qty_change ?? 0);

                if ($m->direction === 'in') {

                    if ($qty <= 0) {
                        continue;
                    }

                    if ($m->unit_cost === null) {
                        continue;
                    }

                    $this->addReceipt($lotId, $qty, $m->unit_cost);

                } elseif ($m->direction === 'out') {

                    $qtyOut = abs($qty);
                    if ($qtyOut <= 0) {
                        continue;
                    }

                    $this->consume($lotId, $qtyOut);
                }
            }

            return $lot->fresh();
        });
    }

    /**
     * Rebuild ALL LOTs yang pernah dipakai mutasi.
     */
    public function rebuildAllLotsFromMutations(): void
    {
        DB::transaction(function () {

            $lotIds = InventoryMutation::query()
                ->whereNotNull('lot_id')
                ->distinct()
                ->pluck('lot_id')
                ->filter()
                ->values();

            foreach ($lotIds as $lotId) {
                $this->rebuildLotFromMutations($lotId);
            }
        });
    }
}
