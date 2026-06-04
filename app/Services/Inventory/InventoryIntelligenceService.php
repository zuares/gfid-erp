<?php

namespace App\Services\Inventory;

use App\Models\Item;
use App\Services\Production\ProductionPriorityService;
use Illuminate\Support\Collection;

/**
 * InventoryIntelligenceService
 *
 * Lapisan tipis di atas ProductionPriorityService::inventorySnapshot().
 * Menggabungkan snapshot stok + laju jual dengan metadata item (finished_good)
 * lalu menurunkan metrik forecast realtime:
 *   - forecast_30   = ADS × 30
 *   - suggested_qty = max(0, (COVER_TARGET × ADS) − ready − wip)
 *   - status        = sehat / menipis / kritis / stockout / no_demand
 *
 * Read-only. Tidak membuat tabel/kolom. Sumber kebenaran sama dgn modul Produksi.
 */
class InventoryIntelligenceService
{
    private const COVER_TARGET = 21;      // hari cover dianggap "aman" (selaras ProductionPriorityService)
    private const FORECAST_HORIZON = 30;  // horizon forecast (hari)
    private const COVER_CRITICAL = 7;     // di bawah ini = kritis

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

        $items = Item::with('category')
            ->whereIn('id', $snapshot->keys())
            ->where('type', 'finished_good')
            ->get()
            ->keyBy('id');

        return $items->map(function ($item) use ($snapshot) {
            $s = $snapshot->get($item->id);

            $ready = (float) $s->ready_stock;
            $wip = (float) $s->wip_stock;
            $ads = (float) $s->ads;
            $cover = $s->cover_days;          // null bila ads 0
            $pipeCover = $s->pipe_cover_days; // null bila ads 0

            $forecast = round($ads * self::FORECAST_HORIZON, 1);
            $suggested = $ads > 0
                ? max(0.0, round(self::COVER_TARGET * $ads - $ready - $wip, 0))
                : 0.0;

            return (object) [
                'item_id' => $item->id,
                'sku' => $item->code,
                'product' => $item->name,
                'category' => $item->category?->name ?? '-',
                'ready' => $ready,
                'wip' => $wip,
                's7' => (float) $s->s7,
                's14' => (float) $s->s14,
                's30' => (float) $s->s30,
                'ads' => round($ads, 2),
                'cover_days' => $cover,
                'pipe_cover_days' => $pipeCover,
                'forecast_30' => $forecast,
                'suggested_qty' => $suggested,
                'status' => $this->healthStatus($ready, $ads, $cover),
            ];
        })
            ->sortBy('sku')
            ->values();
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
        return [
            'sku_total' => $rows->count(),
            'sku_demand' => $rows->where('status', '!=', 'no_demand')->count(),
            'stockout' => $rows->where('status', 'stockout')->count(),
            'kritis' => $rows->where('status', 'kritis')->count(),
            'menipis' => $rows->where('status', 'menipis')->count(),
            'sehat' => $rows->where('status', 'sehat')->count(),
            'below_target' => $rows->whereIn('status', ['stockout', 'kritis', 'menipis'])->count(),
            'total_ready' => (float) $rows->sum('ready'),
            'total_wip' => (float) $rows->sum('wip'),
            'total_suggested' => (float) $rows->sum('suggested_qty'),
            'tightest_cover' => $rows->whereNotNull('cover_days')->min('cover_days'),
        ];
    }
}
