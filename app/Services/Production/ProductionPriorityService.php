<?php

namespace App\Services\Production;

use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ProductionPriorityService
 *
 * Skor prioritas produksi (0–100) per SKU dari 5 faktor:
 *   1. Cover ready stock  (35)  — ready ÷ laju jual; makin tipis makin urgent
 *   2. Cover pipeline     (20)  — (ready+WIP) ÷ laju jual; WIP yang sudah jalan menurunkan urgensi
 *   3. Kekuatan demand    (20)  — laju jual relatif terhadap SKU tersibuk
 *   4. Deadline           (15)  — deadline terdekat dari production_movements (overdue = maksimum)
 *   5. Umur produksi      (10)  — WIP jahit yang menua (macet) menambah urgensi
 *
 * Semua agregasi dilakukan di SQL (4 query), penggabungan di memori. Read-only.
 */
class ProductionPriorityService
{
    private const COVER_TARGET = 21;     // hari cover dianggap "aman"
    private const DEADLINE_WINDOW = 14;  // hari deadline mulai dihitung urgent
    private const AGE_MAX = 14;          // umur WIP penalti penuh

    public function priorityList(array $f, int $limit = 100): Collection
    {
        $snapshot = $this->inventorySnapshot($f); // item_id => {ready_stock,siap_jahit,sedang_jahit,wip_stock,s7,s14,s30,ads,cover_days,pipe_cover_days}
        $deadlines = $this->nearestDeadline($f);  // item_id => date
        $aging = $this->maxWipAge($f);            // item_id => int days

        $ids = $snapshot->keys();
        if ($ids->isEmpty()) {
            return collect();
        }

        $items = Item::with('category')
            ->whereIn('id', $ids)
            ->where('type', 'finished_good')
            ->where('production_source', Item::PRODUCTION_IN_HOUSE)
            ->get()
            ->keyBy('id');

        $today = Carbon::today();

        // laju jual tertinggi di antara finished_good (untuk skor demand relatif)
        $maxAds = $items->keys()->reduce(fn($m, $id) => max($m, (float) ($snapshot->get($id)?->ads ?? 0.0)), 0.0);

        $rows = $items->map(function ($item) use ($snapshot, $deadlines, $aging, $maxAds, $today) {
            $id = $item->id;
            $snap = $snapshot->get($id);
            $isMadeInHouse = $item->production_source === Item::PRODUCTION_IN_HOUSE;

            $ready = (float) ($snap->ready_stock ?? 0);
            $siapJahit = (float) ($snap->siap_jahit ?? 0);
            $sedangJahit = (float) ($snap->sedang_jahit ?? 0);
            $whPrd = (float) ($snap->wh_prd ?? 0);
            $wip = (float) ($snap->wip_stock ?? 0);
            $s7 = (float) ($snap->s7 ?? 0);
            $s14 = (float) ($snap->s14 ?? 0);
            $s30 = (float) ($snap->s30 ?? 0);
            $ads = (float) ($snap->ads ?? 0);

            $cover = $snap->cover_days ?? null;
            $pipeCover = $snap->pipe_cover_days ?? null;

            $deadline = $deadlines->get($id);
            // signed: positif = masih ada waktu, <=0 = overdue
            $daysToDeadline = $deadline ? (int) $today->diffInDays(Carbon::parse($deadline)->startOfDay(), false) : null;

            $age = $aging->get($id); // int|null

            // ---- skor per faktor ----
            $scoreCover = $ads <= 0 ? 0 : max(0, min(35, (1 - min($cover, self::COVER_TARGET) / self::COVER_TARGET) * 35));
            $scorePipe = $ads <= 0 ? 0 : max(0, min(20, (1 - min($pipeCover, self::COVER_TARGET) / self::COVER_TARGET) * 20));
            $scoreDemand = $maxAds > 0 ? min(20, $ads / $maxAds * 20) : 0;
            $scoreDeadline = 0;
            if ($daysToDeadline !== null) {
                if ($daysToDeadline <= 0) {
                    $scoreDeadline = 15;
                } elseif ($daysToDeadline < self::DEADLINE_WINDOW) {
                    $scoreDeadline = (1 - $daysToDeadline / self::DEADLINE_WINDOW) * 15;
                }
            }
            $scoreAge = 0;
            if ($age !== null && $age > 0) {
                $scoreAge = min(10, $age / self::AGE_MAX * 10);
            }

            $score = round($scoreCover + $scorePipe + $scoreDemand + $scoreDeadline + $scoreAge, 1);
            $score = max(0, min(100, $score));

            $grade = $score >= 70 ? 'Kritis' : ($score >= 50 ? 'Tinggi' : ($score >= 30 ? 'Sedang' : 'Rendah'));

            return (object) [
                'item_id' => $id,
                'sku' => $item->code,
                'product' => $item->name,
                'category' => $item->category?->name ?? '-',
                'is_made_in_house' => $isMadeInHouse,
                'production_source' => $item->production_source_label,
                'production_source_key' => $isMadeInHouse ? 'own' : 'external',
                'ready' => $ready,
                'siap_jahit' => $siapJahit,
                'sedang_jahit' => $sedangJahit,
                'wh_prd' => $whPrd,
                'wip' => $wip,
                's7' => $s7,
                's14' => $s14,
                's30' => $s30,
                'ads' => round($ads, 2),
                'cover_days' => $cover,
                'pipe_cover_days' => $pipeCover,
                'deadline' => $deadline,
                'days_to_deadline' => $daysToDeadline,
                'age_days' => $age,
                'score' => $score,
                'grade' => $grade,
                'reason' => $this->reason($cover, $daysToDeadline, $age, $wip, $ads),
            ];
        })
            ->filter(fn($r) => $r->ready > 0 || $r->wip > 0 || $r->s7 > 0 || $r->s30 > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return $rows;
    }

    /**
     * Snapshot stok + laju jual + cover per item (read-only, tanpa skor/deadline/aging).
     * Sumber kebenaran bersama untuk priorityList() dan dashboard inventory.
     * Filter: item_id, category_id. Window penjualan tetap 7/14/30 hari.
     *
     * @return Collection keyBy item_id => {item_id, ready_stock, siap_jahit, sedang_jahit, wh_prd, wip_stock, s7, s14, s30, ads, cover_days, pipe_cover_days}
     */
    public function inventorySnapshot(array $filters): Collection
    {
        $stock = $this->stockPivot($filters);   // item_id => (ready, wip)
        $sales = $this->salesWindows($filters); // item_id => (s7,s14,s30)

        $ids = $stock->keys()->merge($sales->keys())->unique()->values();

        return $ids->mapWithKeys(function ($id) use ($stock, $sales) {
            $st = $stock->get($id);
            $sa = $sales->get($id);

            $ready = (float) ($st->ready ?? 0);
            $siapJahit = (float) ($st->siap_jahit ?? 0);
            $sedangJahit = (float) ($st->sedang_jahit ?? 0);
            $whPrd = (float) ($st->wh_prd ?? 0);
            $wip = (float) ($st->wip ?? 0);
            $s7 = (float) ($sa->s7 ?? 0);
            $s14 = (float) ($sa->s14 ?? 0);
            $s30 = (float) ($sa->s30 ?? 0);

            // laju jual per item (prefer 30h, fallback 7h) — raw, tak dibulatkan
            $ads = $s30 > 0 ? $s30 / 30 : ($s7 > 0 ? $s7 / 7 : 0.0);

            return [$id => (object) [
                'item_id' => $id,
                'ready_stock' => $ready,
                'siap_jahit' => $siapJahit,
                'sedang_jahit' => $sedangJahit,
                'wh_prd' => $whPrd,
                'wip_stock' => $wip,
                's7' => $s7,
                's14' => $s14,
                's30' => $s30,
                'ads' => $ads,
                'cover_days' => $ads > 0 ? round($ready / $ads, 1) : null,
                'pipe_cover_days' => $ads > 0 ? round(($ready + $wip) / $ads, 1) : null,
            ]];
        });
    }

    /** Alasan utama prioritas (faktor dominan, untuk ditampilkan). */
    private function reason(?float $cover, ?int $dd, ?int $age, float $wip, float $ads): string
    {
        $parts = [];
        if ($dd !== null && $dd <= 0) {
            $parts[] = 'deadline lewat';
        } elseif ($dd !== null && $dd < self::DEADLINE_WINDOW) {
            $parts[] = "deadline {$dd} hr";
        }
        if ($ads > 0 && $cover !== null && $cover < 7) {
            $parts[] = "cover {$cover} hr";
        }
        if ($wip <= 0 && $ads > 0) {
            $parts[] = 'belum ada WIP';
        }
        if ($age !== null && $age >= self::AGE_MAX) {
            $parts[] = "WIP menua {$age} hr";
        }
        return $parts ? implode(' · ', $parts) : 'stok cukup';
    }

    // ==========================================================
    // AGREGASI (SQL)
    // ==========================================================

    /** Ready (WH-RTS) + detail pipeline produksi per item. */
    private function stockPivot(array $f): Collection
    {
        $q = DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->whereIn('w.code', ['WH-RTS', 'WIP-CUT', 'WIP-SEW', 'WH-PRD']);

        if (!empty($f['item_id'])) {
            $q->where('s.item_id', $f['item_id']);
        }
        if (!empty($f['category_id'])) {
            $q->join('items as i', 'i.id', '=', 's.item_id')->where('i.item_category_id', $f['category_id']);
        }

        return $q->groupBy('s.item_id')
            ->selectRaw("
                s.item_id,
                COALESCE(SUM(CASE WHEN w.code='WH-RTS' THEN s.qty END),0) as ready,
                COALESCE(SUM(CASE WHEN w.code='WIP-CUT' THEN s.qty END),0) as siap_jahit,
                COALESCE(SUM(CASE WHEN w.code='WIP-SEW' THEN s.qty END),0) as sedang_jahit,
                COALESCE(SUM(CASE WHEN w.code='WH-PRD' THEN s.qty END),0) as wh_prd,
                COALESCE(SUM(CASE WHEN w.code IN ('WIP-CUT','WIP-SEW','WH-PRD') THEN s.qty END),0) as wip
            ")
            ->get()
            ->keyBy('item_id');
    }

    /** Penjualan 7/14/30 hari per item. */
    private function salesWindows(array $f): Collection
    {
        $today = Carbon::today();
        $d7 = $today->copy()->subDays(6)->toDateString();
        $d14 = $today->copy()->subDays(13)->toDateString();
        $d30 = $today->copy()->subDays(29)->toDateString();

        $q = DB::table('daily_item_sales as d')
            ->whereBetween('d.date', [$d30, $today->toDateString()]);

        if (!empty($f['item_id'])) {
            $q->where('d.item_id', $f['item_id']);
        }
        if (!empty($f['category_id'])) {
            $q->join('items as i', 'i.id', '=', 'd.item_id')->where('i.item_category_id', $f['category_id']);
        }

        return $q->groupBy('d.item_id')
            ->selectRaw("
                d.item_id,
                COALESCE(SUM(CASE WHEN d.date >= ? THEN d.qty_sold END),0) as s7,
                COALESCE(SUM(CASE WHEN d.date >= ? THEN d.qty_sold END),0) as s14,
                COALESCE(SUM(d.qty_sold),0) as s30
            ", [$d7, $d14])
            ->get()
            ->keyBy('item_id');
    }

    /** Deadline terdekat (belum lewat jauh) per item dari production_movements. */
    private function nearestDeadline(array $f): Collection
    {
        $q = DB::table('production_movements')
            ->whereNotNull('deadline')
            ->where('deadline', '>=', Carbon::today()->subDays(30)->toDateString());

        if (!empty($f['item_id'])) {
            $q->where('item_id', $f['item_id']);
        }

        return $q->groupBy('item_id')
            ->selectRaw('item_id, MIN(deadline) as deadline')
            ->pluck('deadline', 'item_id');
    }

    /** Umur maksimum WIP sewing outstanding per item (hari). */
    private function maxWipAge(array $f): Collection
    {
        $q = DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->whereNull('p.voided_at')
            ->where('pl.status', 'in_progress')
            ->whereRaw('pl.qty_bundle - COALESCE(pl.qty_returned_ok,0) - COALESCE(pl.qty_returned_reject,0) > 0');

        if (!empty($f['item_id'])) {
            $q->where('pl.finished_item_id', $f['item_id']);
        }

        $today = Carbon::today();

        return $q->select('pl.finished_item_id as item_id', 'p.date')
            ->get()
            ->groupBy('item_id')
            ->map(function ($g) use ($today) {
                return (int) $g->max(fn($r) => $r->date ? Carbon::parse($r->date)->startOfDay()->diffInDays($today) : 0);
            });
    }
}
