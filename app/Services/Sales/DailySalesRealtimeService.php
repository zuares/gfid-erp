<?php

namespace App\Services\Sales;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class DailySalesRealtimeService
{
    /**
     * Apply effect shipment POSTED -> daily_item_sales += qty_scanned, then recalc ADS for affected items.
     * Idempotent via shipments.daily_sales_applied_at
     */
    public function applyShipmentPosted(Shipment $shipment, int $adsDays = 30, bool $onlyActive = true): void
    {
        // idempotent guard
        if (!empty($shipment->daily_sales_applied_at)) {
            return;
        }

        $shipment->loadMissing(['lines', 'lines.item']);

        $date = $shipment->date; // assume YYYY-MM-DD (atau datetime, tetap aman)
        $itemQty = [];

        foreach ($shipment->lines as $line) {
            $qty = (int) ($line->qty_scanned ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $itemId = (int) $line->item_id;
            $itemQty[$itemId] = ($itemQty[$itemId] ?? 0) + $qty;
        }

        if (empty($itemQty)) {
            // tetap tandai applied supaya gak muter terus
            $shipment->daily_sales_applied_at = now();
            $shipment->save();
            return;
        }

        // 1) upsert-add ke daily_item_sales (SQLite friendly: select then update/insert)
        foreach ($itemQty as $itemId => $deltaQty) {
            // nilai value_sold pakai HPP sekarang (lebih simple); kalau mau akurat historis, simpan unit cost di line saat posting
            $hpp = (float) (DB::table('items')->where('id', $itemId)->value('hpp') ?? 0);

            $row = DB::table('daily_item_sales')
                ->whereDate('date', $date)
                ->where('item_id', $itemId)
                ->first();

            if ($row) {
                DB::table('daily_item_sales')
                    ->where('id', $row->id)
                    ->update([
                        'qty_sold' => (float) $row->qty_sold + $deltaQty,
                        'value_sold' => (float) $row->value_sold + ($deltaQty * $hpp),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('daily_item_sales')->insert([
                    'date' => $date,
                    'item_id' => $itemId,
                    'qty_sold' => (float) $deltaQty,
                    'value_sold' => (float) ($deltaQty * $hpp),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2) tandai applied
        $shipment->daily_sales_applied_at = now();
        $shipment->save();

        // 3) recalc ADS hanya untuk item yang kena
        $this->recalcAdsForItemIds(array_keys($itemQty), $adsDays, $onlyActive);
    }

    /**
     * Reverse effect when shipment cancelled -> daily_item_sales -= qty_scanned, then recalc ADS.
     * Idempotent via shipments.daily_sales_reversed_at
     */
    public function reverseShipmentCancelled(Shipment $shipment, int $adsDays = 30, bool $onlyActive = true): void
    {
        // kalau belum pernah applied, ngapain reverse
        if (empty($shipment->daily_sales_applied_at)) {
            return;
        }

        // idempotent guard
        if (!empty($shipment->daily_sales_reversed_at)) {
            return;
        }

        $shipment->loadMissing(['lines']);

        $date = $shipment->date;
        $itemQty = [];

        foreach ($shipment->lines as $line) {
            $qty = (int) ($line->qty_scanned ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $itemId = (int) $line->item_id;
            $itemQty[$itemId] = ($itemQty[$itemId] ?? 0) + $qty;
        }

        if (empty($itemQty)) {
            $shipment->daily_sales_reversed_at = now();
            $shipment->save();
            return;
        }

        foreach ($itemQty as $itemId => $deltaQty) {
            $hpp = (float) (DB::table('items')->where('id', $itemId)->value('hpp') ?? 0);

            $row = DB::table('daily_item_sales')
                ->whereDate('date', $date)
                ->where('item_id', $itemId)
                ->first();

            if (!$row) {
                continue;
            }

            $newQty = max(0, (float) $row->qty_sold - $deltaQty);
            $newVal = max(0, (float) $row->value_sold - ($deltaQty * $hpp));

            if ($newQty <= 0) {
                DB::table('daily_item_sales')->where('id', $row->id)->delete();
            } else {
                DB::table('daily_item_sales')->where('id', $row->id)->update([
                    'qty_sold' => $newQty,
                    'value_sold' => $newVal,
                    'updated_at' => now(),
                ]);
            }
        }

        $shipment->daily_sales_reversed_at = now();
        $shipment->save();

        $this->recalcAdsForItemIds(array_keys($itemQty), $adsDays, $onlyActive);
    }

    /**
     * Recalc ADS for selected items only (fast).
     */
    public function recalcAdsForItemIds(array $itemIds, int $days = 30, bool $onlyActive = true): void
    {
        $days = max(1, (int) $days);

        // cutoff sqlite
        $cutoff = DB::selectOne("SELECT date('now','-{$days} day') AS d")->d;

        // reset to 0 for affected items first
        $reset = DB::table('items')->whereIn('id', $itemIds);
        if ($onlyActive) {
            $reset->where('active', 1);
        }

        $reset->update([
            'avg_daily_sales' => 0,
            'avg_daily_sales_window' => $days,
            'avg_daily_sales_updated_at' => now(),
        ]);

        // aggregate qty_sum for affected items
        $agg = DB::table('daily_item_sales')
            ->selectRaw('item_id, SUM(qty_sold) as qty_sum')
            ->where('date', '>=', $cutoff)
            ->whereIn('item_id', $itemIds)
            ->groupBy('item_id')
            ->get();

        foreach ($agg as $r) {
            $ads = ((float) ($r->qty_sum ?? 0)) / $days;

            $q = DB::table('items')->where('id', (int) $r->item_id);
            if ($onlyActive) {
                $q->where('active', 1);
            }

            $q->update([
                'avg_daily_sales' => $ads,
                'avg_daily_sales_window' => $days,
                'avg_daily_sales_updated_at' => now(),
            ]);
        }
    }
}
