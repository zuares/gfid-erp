<?php

namespace App\Services\Inventory;

use App\Models\Lot;
use Illuminate\Support\Facades\DB;

class LotCostService
{
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

            if ($oldQty == 0 && $oldTotal == 0) {
                $lot->initial_qty = $qty;
                $lot->initial_cost = $incomingTotal;
            }

            $lot->save();

            return $lot;
        });
    }

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
                throw new \RuntimeException("Qty LOT tidak cukup. LOT {$lot->code}, saldo: {$oldQty}, minta: {$qty}");
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

    public function getAvgCost(int $lotId): float
    {
        $lot = Lot::findOrFail($lotId);
        return (float) $lot->avg_cost;
    }

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

        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float) $value;
        }

        return (float) $value;
    }
}
