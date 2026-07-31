<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ItemCostSnapshot;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryStockController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * Apply scope gudang berdasarkan role.
     * - owner: bebas
     * - admin: hanya WH-RTS, WH-TRANSIT, WIP-SEW
     * - operating: semua kecuali WH-RTS
     */
    private function scopeByRole($query, ?string $role): mixed
    {
        if ($role === 'admin') {
            return $query->whereIn('warehouses.code', ['WH-RTS', 'WH-TRANSIT', 'WIP-SEW']);
        }

        if ($role === 'operating') {
            return $query->where('warehouses.code', '!=', 'WH-RTS');
        }

        return $query; // owner / default
    }

    /**
     * Dropdown gudang yang muncul mengikuti role (biar rapih)
     */
    private function getWarehousesForDropdown(?string $role)
    {
        $q = Warehouse::orderBy('name');

        if ($role === 'operating') {
            $q->where('code', '!=', 'WH-RTS');
        }

        return $q->get();
    }

    // ==========================================================
    // ✅ NEW (UPDATED) : STOK PER ITEM (AJAX + JSON pagination)
    // ==========================================================
    // app/Http/Controllers/Inventory/InventoryStockController.php (misal)

    public function items(Request $request)
    {
        $user = auth()->user();
        $role = strtolower(trim((string) ($user?->role ?? '')));
        $isOwner = $role === 'owner';
        $canViewAllWarehouses = $isOwner || $role === 'admin';

        // =========================
        // Filters & sorting
        // =========================
        $searchRaw = trim((string) $request->input('search', ''));
        $search = strtoupper($searchRaw);

        $sort = (string) $request->input('sort', 'code');
        $dir = strtolower((string) $request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSort = $isOwner
        ? ['code', 'value', 'total', 'fg', 'wip', 'ads', 'cover']
        : ['code', 'total', 'fg', 'wip', 'ads', 'cover']; // Admin can sort by ads/cover

        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'code';
        }

        // =========================
        // ✅ Owner & Admin: list gudang + selected warehouse_id
        // =========================
        $warehouses = collect();
        $warehouseId = null;

        if ($canViewAllWarehouses) {
            $warehouses = Warehouse::query()
                ->where('active', 1) // ✅ kolomnya active
                ->orderByRaw("
                CASE
                    WHEN code = 'FG' THEN 1
                    WHEN code = 'WH-RTS' THEN 2
                    WHEN code = 'WH-PRD' THEN 3
                    WHEN code LIKE 'WIP-%' THEN 4
                    WHEN code LIKE 'REJ-%' OR code = 'REJECT' THEN 5
                    ELSE 6
                END
            ")
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'type']);

            if ($request->has('warehouse_id')) {
                $warehouseId = $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null;
            } else {
                if ($role === 'admin') {
                    $rts = $warehouses->firstWhere('code', 'WH-RTS');
                    $warehouseId = $rts ? $rts->id : null;
                }
            }

            if ($warehouseId && !$warehouses->firstWhere('id', $warehouseId)) {
                $warehouseId = null;
            }
        }

        // =========================
        // STEP 1: base stock per item+warehouse
        // =========================
        $base = DB::table('inventory_mutations as m')
            ->join('warehouses as w', 'w.id', '=', 'm.warehouse_id')
            ->leftJoin('inventory_stocks as st', function($join) {
                $join->on('st.item_id', '=', 'm.item_id')
                     ->on('st.warehouse_id', '=', 'm.warehouse_id');
            });

        // 🔒 Role scope
        if ($role === 'operating') {
            $base->where(function ($q) {
                $q->where('w.code', 'WH-PRD')
                    ->orWhere('w.code', 'LIKE', 'WIP-%');
            });
        } elseif ($canViewAllWarehouses && $warehouseId) {
            // ✅ Filter gudang
            $base->where('m.warehouse_id', $warehouseId);
        }

        $base->selectRaw('
        m.item_id,
        w.code AS wh_code,
        SUM(m.qty_change) AS qty,
        MAX(st.allocated_qty) AS allocated_qty
    ')
            ->groupBy('m.item_id', 'w.code');

        // =========================
        // STEP 2: aggregate per item
        // =========================
        $q = DB::query()
            ->fromSub($base, 's')
            ->join('items', 'items.id', '=', 's.item_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.item_category_id')
            ->where('items.active', 1);

        // Search
        if ($search !== '') {
            $terms = array_values(array_filter(preg_split('/[\s,;|]+/', $search)));
            $q->where(function ($qq) use ($terms) {
                foreach ($terms as $t) {
                    $like = "%{$t}%";
                    $qq->orWhere('items.code', 'like', $like)
                        ->orWhere('items.name', 'like', $like);
                }
            });
        }

        // ✅ Mapping FG/WIP sesuai gudangmu
        $q->selectRaw('
        s.item_id,
        items.code AS item_code,
        items.name AS item_name,
        item_categories.id AS category_id,
        item_categories.name AS category_name,

        COALESCE(SUM(s.qty),0) AS total_qty,
        COALESCE(SUM(s.allocated_qty),0) AS allocated_qty,
        COALESCE(SUM(
            CASE
                WHEN UPPER(TRIM(s.wh_code)) <> \'RM\' THEN s.qty
                ELSE 0
            END
        ),0) AS value_qty,

        COALESCE(SUM(
            CASE
                WHEN s.wh_code = \'FG\' THEN s.qty
                WHEN s.wh_code = \'WH-TRANSIT\' THEN s.qty
                WHEN s.wh_code LIKE \'WH-%\' THEN s.qty
                ELSE 0
            END
        ),0) AS fg_qty,

        COALESCE(SUM(
            CASE WHEN s.wh_code LIKE \'WIP-%\' THEN s.qty ELSE 0 END
        ),0) AS wip_qty,

        COALESCE(items.hpp,0) AS hpp_per_unit,
        COALESCE(SUM(
            CASE
                WHEN UPPER(TRIM(s.wh_code)) <> \'RM\' THEN s.qty
                ELSE 0
            END
        ),0) * COALESCE(items.hpp,0) AS stock_value,

        COALESCE(items.avg_daily_sales,0) AS ads,

        CASE
            WHEN COALESCE(items.avg_daily_sales,0) > 0
            THEN COALESCE(SUM(s.qty),0) / COALESCE(items.avg_daily_sales,0)
            ELSE NULL
        END AS coverage_days
    ')
            ->groupBy(
                's.item_id',
                'items.code',
                'items.name',
                'item_categories.id',
                'item_categories.name',
                'items.hpp',
                'items.avg_daily_sales'
            );

        // Sorting
        switch ($sort) {
            case 'value':
                $q->orderBy('stock_value', $dir)->orderBy('items.code', 'asc');
                break;
            case 'total':
                $q->orderBy('total_qty', $dir)->orderBy('items.code', 'asc');
                break;
            case 'fg':
                $q->orderBy('fg_qty', $dir)->orderBy('items.code', 'asc');
                break;
            case 'wip':
                $q->orderBy('wip_qty', $dir)->orderBy('items.code', 'asc');
                break;
            case 'ads':
                $q->orderBy('ads', $dir)->orderBy('items.code', 'asc');
                break;
            case 'cover':
                $q->orderBy('coverage_days', $dir)->orderBy('items.code', 'asc');
                break;
            default:
                $q->orderBy('items.code', $dir);
                break;
        }

        // =========================
        // Owner summary (all filtered rows, not only current page)
        // =========================
        $hppSummary = null;
        $hppByCategory = [];

        if ($isOwner) {
            $summaryRows = (clone $q)->get();
            $totalQty = (float) $summaryRows->sum('total_qty');
            $totalValue = (float) $summaryRows->sum('stock_value');
            $valueQty = (float) $summaryRows->sum('value_qty');

            $hppSummary = [
                'total_qty' => $totalQty,
                'total_value' => $totalValue,
                'value_qty' => $valueQty,
                'avg_hpp_weighted' => $valueQty > 0 ? ($totalValue / $valueQty) : 0.0,
                'avg_ads' => $summaryRows->count() ? (float) $summaryRows->avg(fn($r) => (float) ($r->ads ?? 0)) : 0.0,
            ];

            $hppByCategory = $summaryRows
                ->groupBy(fn($r) => $r->category_name ?? 'Uncategorized')
                ->map(function ($grp, $catName) {
                    $qty = (float) $grp->sum('total_qty');
                    $val = (float) $grp->sum('stock_value');
                    $valueQty = (float) $grp->sum('value_qty');
                    return [
                        'category' => (string) $catName,
                        'total_qty' => $qty,
                        'total_value' => $val,
                        'value_qty' => $valueQty,
                        'avg_hpp_weighted' => $valueQty > 0 ? ($val / $valueQty) : 0.0,
                    ];
                })
                ->values()
                ->all();
        }

        $stocks = $q->paginate(50)->appends($request->query());
        $rows = $stocks->getCollection();

        // =========================
        // JSON (AJAX)
        // =========================
        $accept = (string) ($request->header('accept') ?? '');
        $forceJson = str_contains(strtolower($accept), 'application/json');

        if ($request->wantsJson() || $request->expectsJson() || $request->ajax() || $forceJson) {
            $payloadRows = $rows->map(function ($r) use ($isOwner, $role) {
                return [
                    'item_id' => (int) $r->item_id,
                    'item_code' => (string) $r->item_code,
                    'item_name' => (string) $r->item_name,
                    'category' => [
                        'id' => $r->category_id ? (int) $r->category_id : null,
                        'name' => $r->category_name ? (string) $r->category_name : null,
                    ],
                    'total_qty' => (float) $r->total_qty,
                    'allocated_qty' => (float) $r->allocated_qty,
                    'available_qty' => (float) $r->total_qty - (float) $r->allocated_qty,
                    'available_stock' => (float) $r->total_qty - (float) $r->allocated_qty,
                    'fg_qty' => (float) $r->fg_qty,
                    'wip_qty' => (float) $r->wip_qty,

                    'hpp_per_unit' => $isOwner ? (float) $r->hpp_per_unit : null,
                    'stock_value' => $isOwner ? (float) $r->stock_value : null,
                    'ads' => ($isOwner || $role === 'admin') ? (float) $r->ads : null,
                    'coverage_days' => ($isOwner || $role === 'admin') ? ($r->coverage_days !== null ? (float) $r->coverage_days : null) : null,

                    'locations_url' => route('inventory.stocks.item_locations', $r->item_id),
                ];
            })->values()->all();

            $resp = [
                'ok' => true,
                'meta' => [
                    'total' => $stocks->total(),
                    'from' => $stocks->firstItem() ?? 0,
                    'per_page' => $stocks->perPage(),
                    'current_page' => $stocks->currentPage(),
                    'last_page' => $stocks->lastPage(),
                ],
                'rows' => $payloadRows,
                'pagination_html' => (string) $stocks->links(),
            ];

            if ($isOwner) {
                $resp['hpp_summary'] = $hppSummary;
                $resp['hpp_by_category'] = array_values($hppByCategory);
            }

            return response()->json($resp);
        }

        // =========================
        // Blade
        // =========================
        return view('inventory.stocks.items', [
            'stocks' => $stocks,
            'filters' => [
                'search' => $searchRaw,
                'sort' => $sort,
                'dir' => $dir,
                'warehouse_id' => $warehouseId,
            ],
            'warehouses' => $warehouses,
            'hppSummary' => $hppSummary,
            'hppByCategory' => $hppByCategory,
        ]);
    }

    /**
     * JSON cek stok tersedia untuk 1 item.
     *
     * Query:
     * - item_id atau item_code
     * - warehouse_id opsional
     */
    public function available(Request $request): JsonResponse
    {
        $itemId = $request->integer('item_id');
        $itemCode = trim((string) $request->input('item_code', ''));
        $warehouseId = $request->filled('warehouse_id')
            ? (int) $request->input('warehouse_id')
            : null;

        $itemQuery = Item::query()->with('category');

        if ($itemId) {
            $itemQuery->whereKey($itemId);
        } elseif ($itemCode !== '') {
            $itemQuery->where('code', $itemCode);
        } else {
            return response()->json([
                'ok' => false,
                'message' => 'item_id atau item_code wajib diisi.',
            ], 422);
        }

        $item = $itemQuery->first();

        if (! $item) {
            return response()->json([
                'ok' => false,
                'message' => 'Item tidak ditemukan.',
            ], 404);
        }

        $stockQuery = DB::table('inventory_stocks as s')
            ->where('s.item_id', $item->id);

        if ($warehouseId) {
            $warehouse = Warehouse::select('id', 'code', 'name')->find($warehouseId);

            if (! $warehouse) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Warehouse tidak ditemukan.',
                ], 404);
            }

            $stockQuery->where('s.warehouse_id', $warehouseId);
        } else {
            $warehouse = null;
        }

        $totals = $stockQuery->selectRaw('
            COALESCE(SUM(s.qty), 0) AS qty,
            COALESCE(SUM(s.allocated_qty), 0) AS allocated_qty,
            COALESCE(SUM(s.qty - s.allocated_qty), 0) AS available_qty
        ')->first();

        $qty = (float) ($totals->qty ?? 0);
        $allocatedQty = (float) ($totals->allocated_qty ?? 0);
        $availableQty = (float) ($totals->available_qty ?? ($qty - $allocatedQty));

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => (int) $item->id,
                'code' => (string) $item->code,
                'name' => (string) $item->name,
                'category' => $item->category?->name,
            ],
            'warehouse' => $warehouse ? [
                'id' => (int) $warehouse->id,
                'code' => (string) $warehouse->code,
                'name' => (string) $warehouse->name,
            ] : null,
            'stock' => [
                'qty' => $qty,
                'allocated_qty' => $allocatedQty,
                'available_qty' => $availableQty,
            ],
        ]);
    }

    // ==========================================================
    // ✅ SYNC HPP : tarik HPP master (snapshot aktif) ke kolom items.hpp
    //    Tujuannya menyelaraskan valuasi stok (qty × items.hpp) dengan
    //    "HPP Aktif" di Master Item. COST-NEUTRAL:
    //    - HANYA menulis kolom items.hpp (HPP referensi/statis).
    //    - TIDAK menyentuh Lot.avg_cost / total_cost / jurnal.
    // ==========================================================
    public function syncHpp(Request $request)
    {
        $updated = 0;
        $scanned = 0;

        DB::transaction(function () use (&$updated, &$scanned) {
            // Snapshot aktif global (warehouse_id NULL) — ambil yang terbaru per item.
            $snapshots = ItemCostSnapshot::query()
                ->active()
                ->whereNull('warehouse_id')
                ->where('unit_cost', '>', 0)
                ->orderBy('item_id')
                ->orderByDesc('snapshot_date')
                ->orderByDesc('id')
                ->get(['item_id', 'unit_cost']);

            $seen = [];

            foreach ($snapshots as $snap) {
                // ambil snapshot terbaru saja per item (sesuai urutan di atas)
                if (isset($seen[$snap->item_id])) {
                    continue;
                }
                $seen[$snap->item_id] = true;
                $scanned++;

                // hanya update kalau beda → hemat & jelas hitungannya
                $affected = Item::where('id', $snap->item_id)
                    ->where(function ($q) use ($snap) {
                        $q->where('hpp', '!=', $snap->unit_cost)
                            ->orWhereNull('hpp');
                    })
                    ->update(['hpp' => $snap->unit_cost]);

                $updated += $affected;
            }
        });

        $msg = "Sync HPP selesai: {$updated} item diperbarui dari HPP master (dari {$scanned} item ber-snapshot). Valuasi stok kini mengikuti HPP master.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'updated' => $updated, 'scanned' => $scanned, 'message' => $msg]);
        }

        return redirect()
            ->route('inventory.stocks.items')
            ->with('success', $msg);
    }

    // ==========================================================
    // ✅ OLD (LEGACY) : STOK PER ITEM (non-aggregate, per stock row)
    //    (Tidak dihapus, cuma dipindah jadi method lain)
    // ==========================================================
    public function itemsLegacy(Request $request)
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $items = Item::where('active', 1)
            ->with('category')
            ->orderBy('code')
            ->get();

        $warehouseId = $request->input('warehouse_id');
        $itemId = $request->input('item_id');
        $hasBalanceOnly = $request->boolean('has_balance_only', true);
        $search = $request->input('search');

        $query = InventoryStock::query()
            ->with(['item.category', 'warehouse']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($itemId) {
            $query->where('item_id', $itemId);
        }

        if ($hasBalanceOnly) {
            $query->where('qty', '!=', 0);
        }

        if ($search) {
            $like = '%' . trim($search) . '%';
            $query->whereHas('item', function ($q) use ($like) {
                $q->where('code', 'like', $like)
                    ->orWhere('name', 'like', $like);
            });
        }

        $stocks = $query
            ->orderBy('warehouse_id')
            ->orderBy('item_id')
            ->paginate(50)
            ->withQueryString();

        return view('inventory.stocks.items', [
            'stocks' => $stocks,
            'warehouses' => $warehouses,
            'items' => $items,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'has_balance_only' => $hasBalanceOnly,
                'search' => $search,
            ],
        ]);
    }

    /**
     * STOK PER LOT (saldo LOT > 0 dari inventory_mutations)
     */
    public function lots(Request $request)
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $items = Item::where('active', 1)
            ->with('category')
            ->orderBy('code')
            ->get();

        $warehouseId = $request->input('warehouse_id');
        $itemId = $request->input('item_id');
        $lotSearch = $request->input('lot_search');
        $itemSearch = $request->input('item_search');

        $lotStocks = $this->inventory->getAvailableLots(
            warehouseId: $warehouseId ? (int) $warehouseId : null,
            itemId: $itemId ? (int) $itemId : null,
        );

        if ($lotSearch) {
            $needle = mb_strtolower(trim($lotSearch));
            $lotStocks = $lotStocks->filter(function ($row) use ($needle) {
                $code = mb_strtolower($row->lot->code ?? '');
                return str_contains($code, $needle);
            });
        }

        if ($itemSearch) {
            $needle = mb_strtolower(trim($itemSearch));
            $lotStocks = $lotStocks->filter(function ($row) use ($needle) {
                $item = $row->lot->item ?? null;
                if (!$item) {
                    return false;
                }

                $code = mb_strtolower($item->code ?? '');
                $name = mb_strtolower($item->name ?? '');
                return str_contains($code, $needle) || str_contains($name, $needle);
            });
        }

        $lotStocks = $lotStocks
            ->sortBy([
                fn($a, $b) => $a->warehouse->name <=> $b->warehouse->name,
                fn($a, $b) => ($a->lot->item->code ?? '') <=> ($b->lot->item->code ?? ''),
                fn($a, $b) => ($a->lot->code ?? '') <=> ($b->lot->code ?? ''),
            ])
            ->values();

        return view('inventory.stocks.lots', [
            'lotStocks' => $lotStocks,
            'warehouses' => $warehouses,
            'items' => $items,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'lot_search' => $lotSearch,
                'item_search' => $itemSearch,
            ],
        ]);
    }

    /**
     * JSON lokasi stok per item (untuk modal)
     */
    public function itemLocations(Item $item, Request $request)
    {
        $user = auth()->user();
        $role = strtolower(trim((string) ($user?->role ?? '')));
        $isOwner = $role === 'owner';

        $warehouseId = $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null;

        $q = DB::table('inventory_mutations as m')
            ->join('warehouses as w', 'w.id', '=', 'm.warehouse_id')
            ->where('m.item_id', $item->id);

        // 🔒 Role scope (samakan dengan items())
        if ($role === 'operating') {
            $q->where(function ($qq) {
                $qq->where('w.code', 'WH-PRD')
                    ->orWhere('w.code', 'LIKE', 'WIP-%');
            });
        } elseif ($role === 'admin') {
            $q->where('w.code', 'WH-RTS');
        }
        // owner / role lain: tanpa pembatasan gudang

        // Filter gudang spesifik (kalau ada)
        if ($warehouseId) {
            $q->where('m.warehouse_id', $warehouseId);
        }

        $rows = $q->selectRaw('
            w.id,
            w.code,
            w.name,
            SUM(m.qty_change) AS qty
        ')
            ->groupBy('w.id', 'w.code', 'w.name')
            ->havingRaw('SUM(m.qty_change) <> 0')
            ->orderBy('w.code')
            ->get()
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'code' => (string) $row->code,
                'name' => (string) $row->name,
                'qty' => (float) $row->qty,
            ])
            ->values();

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $item->id,
                'code' => (string) $item->code,
                'name' => (string) $item->name,
            ],
            'locations' => $rows,
        ]);
    }

}
