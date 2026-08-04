<?php

namespace App\Services\Inventory;

use App\Models\Item;
use App\Services\Production\ProductionPriorityService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * InventoryIntelligenceService
 *
 * Lapisan tipis di atas ProductionPriorityService::inventorySnapshot().
 * Menggabungkan snapshot stok + laju jual dengan metadata item (finished_good)
 * lalu menurunkan metrik forecast realtime:
 *   - forecast_30   = ADS × 30
 *   - forecast_60   = ADS × 60 (khusus saran pengadaan FOB)
 *   - suggested_qty = saran produksi sesuai horizon untuk in-house, atau
 *                     saran pengadaan 60 hari untuk FOB
 *   - status        = sehat / menipis / kritis / stockout / no_demand
 *
 * Read-only. Tidak membuat tabel/kolom. Sumber kebenaran sama dgn modul Produksi.
 */
class InventoryIntelligenceService
{
    private const COVER_TARGET = 21;      // hari cover dianggap "aman" (selaras ProductionPriorityService)
    private const FORECAST_HORIZON = 30;  // horizon forecast (hari)
    private const PRODUCTION_HORIZONS = [30, 60];
    private const PROCUREMENT_HORIZON = 60; // horizon forecast pengadaan FOB (hari)
    private const PROCUREMENT_HORIZONS = [30, 60];
    private const COVER_CRITICAL = 7;     // di bawah ini = kritis
    private const WEIGHT_LAMBDA = 0.92;   // peluruhan harian wADS (half-life ~8-9 hari)

    public function __construct(private ProductionPriorityService $priority)
    {
    }

    /**
     * Baris per SKU (finished_good) = snapshot + metadata + metrik forecast.
     * Filter: item_id, category_id (diteruskan apa adanya ke snapshot).
     */
    public function rows(array $filters): Collection
    {
        $snapshot = $this->priority->inventorySnapshot($filters);
        if ($snapshot->isEmpty()) {
            return collect();
        }

        $items = Item::with(['category', 'activeCostSnapshot'])
            ->whereIn('id', $snapshot->keys())
            ->where('type', 'finished_good')
            ->get()
            ->keyBy('id');

        $series = $this->demandSeries($filters, self::FORECAST_HORIZON);
        $productionHorizon = $this->productionHorizon($filters);
        $procurementHorizon = $this->procurementHorizon($filters);

        return $items->map(function ($item) use ($snapshot, $series, $productionHorizon, $procurementHorizon) {
            $s = $snapshot->get($item->id);

            $readyRts = (float) ($s->ready_stock ?? 0);
            $readyAllocated = (float) ($s->ready_allocated ?? 0);
            $siapJahit = (float) ($s->siap_jahit ?? 0);
            $sedangJahit = (float) ($s->sedang_jahit ?? 0);
            $whPrd = (float) ($s->wh_prd ?? 0);
            $readyTotal = $readyRts + $whPrd;
            $wip = (float) ($s->wip_stock ?? 0);
            // wip_stock juga membawa WH-PRD. Gunakan hanya WIP proses di sini
            // agar readyTotal + WIP tidak menghitung WH-PRD dua kali.
            $wipProcess = max(0.0, $siapJahit + $sedangJahit);
            $availableStock = $readyTotal + $wipProcess;
            $ads = (float) ($s->ads ?? 0);
            $cover = $s->cover_days ?? null;
            $pipeCover = $s->pipe_cover_days ?? null;

            $forecast30 = round($ads * self::FORECAST_HORIZON, 1);
            $forecast60 = round($ads * self::PROCUREMENT_HORIZON, 1);
            $productionForecast = round($ads * $productionHorizon, 1);
            $procurementForecast = round($ads * $procurementHorizon, 1);
            $productionSuggested = $ads > 0
                ? max(0.0, round($productionForecast - $availableStock, 0))
                : 0.0;
            $procurementSuggested = $ads > 0
                ? max(0.0, round($procurementForecast - $availableStock, 0))
                : 0.0;
            $suggested = $item->is_made_in_house ? $productionSuggested : $procurementSuggested;
            $unitCost = $this->unitCost($item);
            $suggestedValue = round($suggested * $unitCost, 0);
            $availableValue = round($availableStock * $unitCost, 0);

            $vals = $series->get($item->id, array_fill(0, self::FORECAST_HORIZON, 0.0));
            $wads = $this->weightedAds($vals);
            $status = $this->healthStatus($readyTotal, $ads, $cover);

            return (object) [
                'item_id' => $item->id,
                'sku' => $item->code,
                'product' => $item->name,
                'category' => $item->category?->name ?? '-',
                'production_source' => $item->production_source,
                'production_source_label' => $item->production_source_label,
                'production_source_key' => $item->is_made_in_house ? 'own' : 'external',
                'rts_min_display' => $item->rts_min_display,
                'rts_max_display' => $item->rts_max_display,
                'ready' => $readyRts,
                'ready_allocated' => $readyAllocated,
                'wh_prd' => $whPrd,
                'ready_total' => $readyTotal,
                'wip' => $wip,
                'siap_jahit' => $siapJahit,
                'sedang_jahit' => $sedangJahit,
                'wip_process' => $wipProcess,
                'available_stock' => $availableStock,
                's7' => (float) ($s->s7 ?? 0),
                's14' => (float) ($s->s14 ?? 0),
                's30' => (float) ($s->s30 ?? 0),
                'ads' => round($ads, 2),
                'wads' => $wads,
                'ads_lift' => round($wads - $ads, 2),
                'cover_days' => $cover,
                'pipe_cover_days' => $pipeCover,
                'forecast_30' => $forecast30,
                'forecast_60' => $forecast60,
                'production_days' => $productionHorizon,
                'production_forecast' => $productionForecast,
                'procurement_days' => $procurementHorizon,
                'procurement_forecast' => $procurementForecast,
                'production_suggested_qty' => $productionSuggested,
                'procurement_suggested_qty' => $procurementSuggested,
                'suggested_qty' => $suggested,
                'unit_cost' => $unitCost,
                'suggested_value' => $suggestedValue,
                'available_value' => $availableValue,
                'eval_score' => $this->evalScore($ads, $cover, $wads, $status),
                'status' => $status,
            ];
        })
            ->sortBy('sku')
            ->values();
    }

    /**
     * Draft saran produksi: baris SKU dengan suggested_qty > 0.
     * Dipakai untuk slip cetak & export CSV. Read-only, turunan rows().
     *
     * @param  array      $filters  item_id, category_id (sama spt rows()).
     * @param  array<int> $itemIds  opsional: batasi ke daftar item_id tertentu.
     * @return Collection           diurutkan saran terbanyak lebih dulu.
     */
    public function productionDraft(array $filters, array $itemIds = []): Collection
    {
        return $this->rows($filters)
            ->where('production_source_key', 'own')
            ->when(!empty($itemIds), fn ($rows) => $rows->whereIn('item_id', $itemIds))
            ->filter(fn ($r) => $r->suggested_qty > 0)
            ->sortByDesc('suggested_qty')
            ->values();
    }

    /** Draft saran pengadaan FOB berbasis forecast 60 hari dan stok ready + WIP proses. */
    public function procurementDraft(array $filters, array $itemIds = []): Collection
    {
        return $this->rows($filters)
            ->where('production_source_key', 'external')
            ->when(!empty($itemIds), fn ($rows) => $rows->whereIn('item_id', $itemIds))
            ->filter(fn ($r) => $r->suggested_qty > 0)
            ->sortByDesc('suggested_qty')
            ->values();
    }

    private function procurementHorizon(array $filters): int
    {
        $requested = (int) ($filters['procurement_days'] ?? self::PROCUREMENT_HORIZON);

        return in_array($requested, self::PROCUREMENT_HORIZONS, true)
            ? $requested
            : self::PROCUREMENT_HORIZON;
    }

    private function productionHorizon(array $filters): int
    {
        $requested = (int) ($filters['production_days'] ?? self::FORECAST_HORIZON);

        return in_array($requested, self::PRODUCTION_HORIZONS, true)
            ? $requested
            : self::FORECAST_HORIZON;
    }

    private function unitCost(Item $item): float
    {
        $activeSnapshot = $item->relationLoaded('activeCostSnapshot')
            ? $item->getRelation('activeCostSnapshot')
            : null;

        $candidates = $item->is_made_in_house
            ? [
                $activeSnapshot?->unit_cost,
                $item->base_unit_cost,
                $item->hpp,
                $item->last_purchase_price,
            ]
            : [
                $item->last_purchase_price,
                $activeSnapshot?->unit_cost,
                $item->base_unit_cost,
                $item->hpp,
            ];

        foreach ($candidates as $candidate) {
            if ((float) $candidate > 0) {
                return (float) $candidate;
            }
        }

        return 0.0;
    }

    /**
     * Deret penjualan harian per SKU (zero-filled) untuk grafik tren.
     * Filter: item_id, category_id (kategori join ke items.item_category_id, selaras snapshot).
     *
     * @return Collection item_id => array<float> panjang $days, urut tanggal lama→baru.
     */
    public function demandSeries(array $filters, int $days = 30): Collection
    {
        $today = Carbon::today();
        $start = $today->copy()->subDays($days - 1)->toDateString();

        $q = DB::table('daily_item_sales as d')
            ->whereBetween('d.date', [$start, $today->toDateString()]);

        if (!empty($filters['item_id'])) {
            $q->where('d.item_id', $filters['item_id']);
        }
        if (!empty($filters['category_id'])) {
            $q->join('items as i', 'i.id', '=', 'd.item_id')
                ->where('i.item_category_id', $filters['category_id']);
        }

        $byItem = $q->groupBy('d.item_id', 'd.date')
            ->selectRaw('d.item_id, d.date, SUM(d.qty_sold) as qty')
            ->get()
            ->groupBy('item_id');

        $axis = collect(range($days - 1, 0))
            ->map(fn ($back) => $today->copy()->subDays($back)->toDateString());

        return $byItem->map(function ($rowsForItem) use ($axis) {
            $byDate = $rowsForItem->keyBy('date');
            return $axis->map(fn ($d) => (float) ($byDate->get($d)->qty ?? 0))->all();
        });
    }

    /**
     * Baris tren per SKU = metadata rows() + deret harian + arah Week-over-Week.
     * Arah: qty 7 hari terakhir vs 7 hari sebelumnya (hari ke-8..14).
     *   - prev7=0 & last7>0  → 'new' (tak ada pembanding)
     *   - Δ% > +10 → 'up' ; Δ% < −10 → 'down' ; selain itu 'flat'
     */
    public function trendRows(array $filters): Collection
    {
        $rows = $this->rows($filters);
        if ($rows->isEmpty()) {
            return collect();
        }

        $series = $this->demandSeries($filters, 30);

        return $rows->map(function ($r) use ($series) {
            $vals = $series->get($r->item_id, array_fill(0, 30, 0.0));
            $n = count($vals);
            $last7 = array_sum(array_slice($vals, max(0, $n - 7), 7));
            $prev7 = array_sum(array_slice($vals, max(0, $n - 14), 7));

            if ($prev7 <= 0 && $last7 <= 0) {
                $direction = 'flat';
                $delta = 0.0;
            } elseif ($prev7 <= 0) {
                $direction = 'new';
                $delta = null;
            } else {
                $delta = round(($last7 - $prev7) / $prev7 * 100, 1);
                $direction = $delta > 10 ? 'up' : ($delta < -10 ? 'down' : 'flat');
            }

            return (object) [
                'item_id' => $r->item_id,
                'sku' => $r->sku,
                'product' => $r->product,
                'category' => $r->category,
                'status' => $r->status,
                'ads' => $r->ads,
                'wads' => $r->wads,
                'ads_lift' => $r->ads_lift,
                'eval_score' => $r->eval_score,
                'ads7' => round($r->s7 / 7, 2),
                'ads14' => round($r->s14 / 14, 2),
                'ads30' => round($r->s30 / 30, 2),
                'last7' => $last7,
                'prev7' => $prev7,
                'delta_pct' => $delta,
                'direction' => $direction,
                'series' => array_map(fn ($v) => round((float) $v, 2), $vals),
            ];
        })->values();
    }

    /** Hitung jumlah SKU naik / turun / datar (untuk chip ringkas tab tren). */
    public function trendSummary(Collection $trendRows): array
    {
        return [
            'up' => $trendRows->whereIn('direction', ['up', 'new'])->count(),
            'down' => $trendRows->where('direction', 'down')->count(),
            'flat' => $trendRows->where('direction', 'flat')->count(),
        ];
    }

    /**
     * Weighted ADS: laju jual harian berbobot, hari terbaru paling berat.
     * wADS = Σ(qty_d · λ^age) / Σ(λ^age), age=0 utk hari ini. λ=0.92 → half-life ~8 hari.
     * Saat penjualan datar → wADS ≈ ADS; saat akselerasi → wADS > ADS.
     *
     * @param array<float> $series urut tanggal lama→baru (index terakhir = hari ini).
     */
    private function weightedAds(array $series): float
    {
        $n = count($series);
        if ($n === 0) {
            return 0.0;
        }

        $num = 0.0;
        $den = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $w = self::WEIGHT_LAMBDA ** (($n - 1) - $i);
            $num += $series[$i] * $w;
            $den += $w;
        }

        return $den > 0 ? round($num / $den, 2) : 0.0;
    }

    /**
     * Skor index evaluasi 0–100 (makin tinggi = makin butuh perhatian).
     * Komposit: 60% urgensi cover (vs target 21hr) + 40% momentum (wADS vs ADS).
     * stockout → 100 ; no_demand / ads 0 → 0.
     */
    private function evalScore(float $ads, ?float $cover, float $wads, string $status): int
    {
        if ($status === 'no_demand' || $ads <= 0) {
            return 0;
        }
        if ($status === 'stockout') {
            return 100;
        }

        $urgency = $cover === null
            ? 0.0
            : max(0.0, min(1.0, 1 - $cover / self::COVER_TARGET));
        $liftRatio = $ads > 0 ? ($wads - $ads) / $ads : 0.0;
        $momentum = max(0.0, min(1.0, 0.5 + $liftRatio));

        return (int) max(0, min(100, round(100 * (0.6 * $urgency + 0.4 * $momentum))));
    }

    /** Klasifikasi kesehatan stok per SKU. */
    private function healthStatus(float $ready, float $ads, ?float $cover): string
    {
        if ($ads <= 0) {
            return 'no_demand';
        }
        if ($ready <= 0) {
            return 'stockout';
        }
        if ($cover !== null && $cover < self::COVER_CRITICAL) {
            return 'kritis';
        }
        if ($cover !== null && $cover < self::COVER_TARGET) {
            return 'menipis';
        }
        return 'sehat';
    }

    /** KPI ringkas untuk tab Executive Summary (dihitung dari rows()). */
    public function summary(Collection $rows): array
    {
        $covered = $rows->whereNotNull('cover_days');

        return [
            'sku_total' => $rows->count(),
            'sku_demand' => $rows->where('status', '!=', 'no_demand')->count(),
            'sku_no_demand' => $rows->where('status', 'no_demand')->count(),
            'stockout' => $rows->where('status', 'stockout')->count(),
            'kritis' => $rows->where('status', 'kritis')->count(),
            'menipis' => $rows->where('status', 'menipis')->count(),
            'sehat' => $rows->where('status', 'sehat')->count(),
            'below_target' => $rows->whereIn('status', ['stockout', 'kritis', 'menipis'])->count(),
            'total_rts' => (float) $rows->sum('ready'),
            'total_wh_prd' => (float) $rows->sum('wh_prd'),
            'total_ready' => (float) $rows->sum('ready_total'),
            'total_wip' => (float) $rows->sum('wip'),
            'total_suggested' => (float) $rows->sum('suggested_qty'),
            'total_suggested_value' => (float) $rows->sum('suggested_value'),
            'total_available_value' => (float) $rows->sum('available_value'),
            'tightest_cover' => $rows->whereNotNull('cover_days')->min('cover_days'),
            'avg_cover' => $covered->isNotEmpty() ? round((float) $covered->avg('cover_days'), 1) : null,
        ];
    }
}
