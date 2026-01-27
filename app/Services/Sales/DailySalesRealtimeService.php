<?php

namespace App\Services\Sales;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class DailySalesRealtimeService
{
    /**
     * Apply effect shipment POSTED -> daily_item_sales += qty_scanned, then recalc ADS for affected items.
     * Idempotent via shipments.daily_sales_applied_at (locked row).
     *
     * IMPORTANT:
     * - Call this INSIDE the same DB::transaction where shipment is posted.
     */
    public function applyShipmentPosted(Shipment $shipment, int $adsDays = 30, bool $onlyActive = true): void
    {
        // lock shipment row to guarantee idempotent even under concurrent requests
        $locked = Shipment::query()
            ->whereKey($shipment->id)
            ->lockForUpdate()
            ->first();

        if (!$locked) {
            return;
        }

        // must be posted & not cancelled
        if (($locked->status ?? null) !== 'posted') {
            return;
        }

        if (!empty($locked->cancelled_at)) {
            return;
        }

        // idempotent guard
        if (!empty($locked->daily_sales_applied_at)) {
            return;
        }

        $locked->loadMissing(['lines']);

        // normalize to DATE only (safe if date is datetime)
        $shipDate = DB::selectOne("SELECT DATE(?) AS d", [$locked->date])->d;

        // aggregate qty per item
        $itemQty = [];
        foreach ($locked->lines as $line) {
            $qty = (int) ($line->qty_scanned ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $itemId = (int) $line->item_id;
            $itemQty[$itemId] = ($itemQty[$itemId] ?? 0) + $qty;
        }

        // mark applied even if empty (anti loop)
        if (empty($itemQty)) {
            $locked->daily_sales_applied_at = now();
            $locked->save();
            return;
        }

        $itemIds = array_keys($itemQty);

        // fetch hpp in 1 query
        $hppMap = DB::table('items')
            ->whereIn('id', $itemIds)
            ->pluck('hpp', 'id'); // [id => hpp]

        $now = now();

        // increment / insert daily_item_sales
        foreach ($itemQty as $itemId => $deltaQty) {
            $hpp = (float) ($hppMap[$itemId] ?? 0);
            $deltaVal = (float) $deltaQty * $hpp;

            $row = DB::table('daily_item_sales')
                ->whereDate('date', $shipDate)
                ->where('item_id', $itemId)
                ->first();

            if ($row) {
                DB::table('daily_item_sales')
                    ->where('id', $row->id)
                    ->update([
                        'qty_sold' => (float) $row->qty_sold + (float) $deltaQty,
                        'value_sold' => (float) $row->value_sold + $deltaVal,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('daily_item_sales')->insert([
                    'date' => $shipDate, // YYYY-MM-DD
                    'item_id' => $itemId,
                    'qty_sold' => (float) $deltaQty,
                    'value_sold' => $deltaVal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // flag applied
        $locked->daily_sales_applied_at = $now;
        $locked->save();

        // recalc ADS only for affected items
        $this->recalcAdsForItemIds($itemIds, $adsDays, $onlyActive);
    }

    /**
     * Reverse effect when shipment cancelled -> daily_item_sales -= qty_scanned, then recalc ADS.
     * Idempotent via shipments.daily_sales_reversed_at (locked row).
     *
     * IMPORTANT:
     * - Call this INSIDE the same DB::transaction where shipment is cancelled.
     */
    public function reverseShipmentCancelled(Shipment $locked, int $adsDays = 30, bool $onlyActive = true): void
    {
        // controller sudah lockForUpdate, jadi jangan lock lagi di sini

        if (empty($locked->cancelled_at)) {
            return;
        }

        if (empty($locked->daily_sales_applied_at)) {
            return;
        }

        if (!empty($locked->daily_sales_reversed_at)) {
            return;
        }

        $locked->loadMissing(['lines']);

        $shipDate = date('Y-m-d', strtotime((string) $locked->date));

        $itemQty = [];
        foreach ($locked->lines as $line) {
            $qty = (int) ($line->qty_scanned ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $itemId = (int) $line->item_id;
            $itemQty[$itemId] = ($itemQty[$itemId] ?? 0) + $qty;
        }

        $now = now();

        if (!$itemQty) {
            $locked->daily_sales_reversed_at = $now;
            $locked->daily_sales_applied_at = null; // recommended
            $locked->save();
            return;
        }

        $itemIds = array_keys($itemQty);

        $hppMap = DB::table('items')->whereIn('id', $itemIds)->pluck('hpp', 'id');

        foreach ($itemQty as $itemId => $deltaQty) {
            $hpp = (float) ($hppMap[$itemId] ?? 0);
            $deltaVal = (float) $deltaQty * $hpp;

            $row = DB::table('daily_item_sales')
                ->where('date', $shipDate)
                ->where('item_id', $itemId)
                ->first();

            if (!$row) {
                continue;
            }

            $newQty = (float) $row->qty_sold - (float) $deltaQty;
            $newVal = (float) $row->value_sold - (float) $deltaVal;

            if ($newQty <= 0.000001) {
                DB::table('daily_item_sales')->where('id', $row->id)->delete();
            } else {
                DB::table('daily_item_sales')->where('id', $row->id)->update([
                    'qty_sold' => max(0, $newQty),
                    'value_sold' => max(0, $newVal),
                    'updated_at' => $now,
                ]);
            }
        }

        $locked->daily_sales_reversed_at = $now;
        $locked->daily_sales_applied_at = null; // recommended
        $locked->save();

        $this->recalcAdsForItemIds($itemIds, $adsDays, $onlyActive);
    }

    /**
     * Recalc ADS for selected items only (fast).
     * ADS = SUM(qty_sold last N days) / N
     */
    public function recalcAdsForItemIds(array $itemIds, int $days = 30, bool $onlyActive = true): void
    {
        $days = max(1, (int) $days);
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        if (empty($itemIds)) {
            return;
        }

        $cutoff = DB::selectOne("SELECT date('now','-{$days} day') AS d")->d;
        $now = now();

        // reset ADS to 0 for affected items (optional but keeps consistent)
        $reset = DB::table('items')->whereIn('id', $itemIds);
        if ($onlyActive) {
            $reset->where('active', 1);
        }

        $reset->update([
            'avg_daily_sales' => 0,
            'avg_daily_sales_window' => $days,
            'avg_daily_sales_updated_at' => $now,
        ]);

        // aggregate
        $agg = DB::table('daily_item_sales')
            ->selectRaw('item_id, COALESCE(SUM(qty_sold),0) as qty_sum')
            ->whereDate('date', '>=', $cutoff)
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
                'avg_daily_sales_updated_at' => $now,
            ]);
        }
    }
}
