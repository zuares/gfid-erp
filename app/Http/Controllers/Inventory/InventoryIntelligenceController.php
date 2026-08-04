<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Services\Inventory\InventoryIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Inventory Intelligence Dashboard (Phase 1).
 *
 * Pola: index() shell + data() JSON per-tab (lazy async). Read-only.
 * Sumber metrik: InventoryIntelligenceService (di atas inventorySnapshot).
 *
 * Tab:
 *  - summary  : KPI kesehatan stok (Executive Summary).
 *  - health   : tabel kesehatan stok per SKU (ready/wip/ads/cover/status).
 *  - forecast : ADS, forecast 30 hari, saran produksi per SKU.
 *  - procurement: ADS, forecast 60 hari, saran pengadaan FOB per SKU.
 */
class InventoryIntelligenceController extends Controller
{
    private const TABS = ['summary', 'health', 'forecast', 'procurement', 'trend'];

    private const TREND_RANGES = [30, 60, 90];

    public function __construct(private InventoryIntelligenceService $service)
    {
    }

    /** Shell dashboard: render kerangka + HANYA tab awal. */
    public function index(Request $request): View
    {
        $initialTab = $request->input('tab');
        if (!in_array($initialTab, self::TABS, true)) {
            $initialTab = 'summary';
        }

        $filters = $this->resolveFilters($request);

        $selectedItem = $filters['item_id'] ? Item::find($filters['item_id']) : null;

        return view('inventory.intelligence.index', array_merge(
            [
                'filters' => $filters,
                'initialTab' => $initialTab,
                'initialPartial' => $this->partialFor($initialTab),
                'categoryOptions' => ItemCategory::where('active', 1)->orderBy('name')->get(),
                'selectedItemLabel' => $selectedItem ? $selectedItem->code . ' — ' . $selectedItem->name : '',
            ],
            $this->tabData($initialTab, $filters),
        ));
    }

    /** API: HTML satu tab + meta (AJAX lazy-load & filter). */
    public function data(Request $request): JsonResponse
    {
        $tab = $request->input('tab');
        if (!in_array($tab, self::TABS, true)) {
            return response()->json(['message' => 'Tab tidak dikenal.'], 422);
        }

        $filters = $this->resolveFilters($request);
        
        $selectedItem = $filters['item_id'] ? Item::find($filters['item_id']) : null;
        
        $data = array_merge(
            $this->tabData($tab, $filters),
            [
                'categoryOptions' => ItemCategory::where('active', 1)->orderBy('name')->get(),
                'filters' => $filters,
                'selectedItemLabel' => $selectedItem ? $selectedItem->code . ' — ' . $selectedItem->name : '',
            ]
        );

        $html = view($this->partialFor($tab), $data)->render();

        return response()->json([
            'tab' => $tab,
            'html' => $html,
            'meta' => [
                'item_id' => $filters['item_id'],
                'category_id' => $filters['category_id'],
            ],
        ]);
    }

    /**
     * Slip cetak saran produksi (Production Action).
     * Halaman berdiri sendiri (print-friendly), read-only, mengikuti filter aktif.
     */
    public function slip(Request $request): View
    {
        $filters = $this->resolveFilters($request);
        $itemIds = $this->resolveItemIds($request);
        $source = $this->resolveDraftSource($request);
        $isProcurement = $source === 'external';
        $rows = $isProcurement
            ? $this->service->procurementDraft($filters, $itemIds)
            : $this->service->productionDraft($filters, $itemIds);

        $category = $filters['category_id'] ? ItemCategory::find($filters['category_id']) : null;
        $item = $filters['item_id'] ? Item::find($filters['item_id']) : null;

        return view('inventory.intelligence.slip', [
            'rows' => $rows,
            'printedAt' => now(),
            'fileName' => ($isProcurement ? 'saran-pengadaan-fob-' : 'saran-produksi-') . now()->format('Ymd-Hi'),
            'categoryLabel' => $category?->name,
            'itemLabel' => $item ? $item->code . ' — ' . $item->name : null,
            'skuCount' => $rows->count(),
            'totalSuggested' => (float) $rows->sum('suggested_qty'),
            'draftType' => $isProcurement ? 'procurement' : 'production',
            'forecastDays' => $isProcurement ? 60 : 30,
            'forecastField' => $isProcurement ? 'forecast_60' : 'forecast_30',
            'suggestionLabel' => $isProcurement ? 'Saran Pengadaan FOB' : 'Saran Produksi',
        ]);
    }

    /** Export CSV saran produksi (UTF-8 BOM agar rapi di Excel). Mengikuti filter aktif. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->resolveFilters($request);
        $itemIds = $this->resolveItemIds($request);
        $source = $this->resolveDraftSource($request);
        $isProcurement = $source === 'external';
        $rows = $isProcurement
            ? $this->service->procurementDraft($filters, $itemIds)
            : $this->service->productionDraft($filters, $itemIds);

        $fileName = ($isProcurement ? 'saran-pengadaan-fob-' : 'saran-produksi-') . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows, $isProcurement) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM
            fputcsv($out, [
                'SKU', 'Produk', 'Kategori', 'Jual/hari', 'Ready', 'WIP proses', 'Stok tersedia',
                'Cover (hari)', $isProcurement ? 'Forecast 60hr' : 'Forecast 30hr',
                $isProcurement ? 'Saran Pengadaan FOB' : 'Saran Produksi', 'Status',
            ]);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->sku,
                    $r->product,
                    $r->category,
                    $r->ads,
                    $r->ready_total,
                    $r->wip_process,
                    $r->available_stock,
                    $r->cover_days,
                    $isProcurement ? $r->forecast_60 : $r->forecast_30,
                    $r->suggested_qty,
                    $r->status,
                ]);
            }
            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** Parse filter (item_id, category_id). Tidak ada rentang tanggal (window snapshot tetap). */
    private function resolveFilters(Request $request): array
    {
        return [
            'item_id' => $request->input('item_id') ?: null,
            'category_id' => $request->input('category_id') ?: null,
        ];
    }

    /** Parse daftar item_id terpilih (CSV "1,2,3") untuk slip/export. Kosong = semua suggested>0. */
    private function resolveItemIds(Request $request): array
    {
        $raw = (string) $request->input('item_ids', '');
        if ($raw === '') {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Mode draft untuk tombol slip/export: default tetap saran produksi. */
    private function resolveDraftSource(Request $request): string
    {
        return $request->input('source') === 'external' ? 'external' : 'own';
    }

    /** Nama blade partial untuk sebuah tab. */
    private function partialFor(string $tab): string
    {
        return 'inventory.intelligence.partials._' . $tab;
    }

    /**
     * Data per tab. rows() di-cache 300s per kombinasi filter
     * (data ADS/stok bergerak harian, bukan per-detik).
     */
    private function tabData(string $tab, array $filters): array
    {
        if ($tab === 'trend') {
            $rows = Cache::remember(
                'inv-intel:trend:' . md5(json_encode($filters)),
                300,
                fn () => $this->service->trendRows($filters),
            );

            return [
                'rows' => $rows,
                'trendSummary' => $this->service->trendSummary($rows),
            ];
        }

        $rows = Cache::remember(
            'inv-intel:rows:' . md5(json_encode($filters)),
            300,
            fn () => $this->service->rows($filters),
        );

        return match ($tab) {
            'health' => ['rows' => $rows],
            'forecast' => ['rows' => $rows],
            'procurement' => ['rows' => $rows],
            default => [
                'summary' => $this->service->summary($rows),
                'rows' => $rows,
            ],
        };
    }
}
