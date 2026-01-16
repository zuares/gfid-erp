<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
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

        if ($role === 'admin') {
            $q->whereIn('code', ['WH-RTS', 'WH-TRANSIT', 'WIP-SEW']);
        } elseif ($role === 'operating') {
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

        // ✅ normalize role (anti spasi/case)
        $roleRaw = (string) ($user?->role ?? '');
        $role = strtolower(trim($roleRaw));
        $isOwner = $role === 'owner';

        // =========================
        // Filters & sorting
        // =========================
        $searchRaw = trim((string) $request->input('search', ''));
        $search = strtoupper($searchRaw);

        $sort = (string) $request->input('sort', 'code');
        $dir = strtolower((string) $request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSort = $isOwner
        ? ['code', 'value', 'total', 'fg', 'wip', 'ads', 'cover']
        : ['code', 'total', 'fg', 'wip'];

        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'code';
        }

        // =========================
        // STEP 1: base stock per item + warehouse
        // =========================
        $base = DB::table('inventory_mutations as m')
            ->join('warehouses as w', 'w.id', '=', 'm.warehouse_id');

        // 🔒 Role scope
        if ($role === 'operating') {
            $base->where(function ($q) {
                $q->where('w.code', 'WH-PRD')
                    ->orWhere('w.code', 'LIKE', 'WIP-%');
            });
        } elseif ($role === 'admin') {
            $base->where('w.code', 'WH-RTS');
        }

        $base->selectRaw('
        m.item_id,
        w.code AS wh_code,
        SUM(m.qty_change) AS qty
    ')
            ->groupBy('m.item_id', 'w.code');

        // =========================
        // STEP 2: aggregate per item + category
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

        $q->selectRaw('
        s.item_id,
        items.code AS item_code,
        items.name AS item_name,

        item_categories.id AS category_id,
        item_categories.name AS category_name,

        COALESCE(SUM(s.qty),0) AS total_qty,

        COALESCE(SUM(
            CASE WHEN s.wh_code LIKE \'WH-%\' THEN s.qty ELSE 0 END
        ),0) AS fg_qty,

        COALESCE(SUM(
            CASE WHEN s.wh_code LIKE \'WIP-%\' THEN s.qty ELSE 0 END
        ),0) AS wip_qty,

        COALESCE(items.hpp,0) AS hpp_per_unit,
        COALESCE(SUM(s.qty),0) * COALESCE(items.hpp,0) AS stock_value,

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
            case 'code':
            default:
                $q->orderBy('items.code', $dir);
                break;
        }

        // =========================
        // Pagination
        // =========================
        $stocks = $q->paginate(50)->appends($request->query());
        $rows = $stocks->getCollection();

        // =========================
        // Owner-only summary (current page)
        // =========================
        $hppSummary = null;
        $hppByCategory = [];

        if ($isOwner) {
            $totalQty = (float) $rows->sum('total_qty');
            $totalValue = (float) $rows->sum('stock_value');

            $hppSummary = [
                'total_qty' => $totalQty,
                'total_value' => $totalValue,
                'avg_hpp_weighted' => $totalQty > 0 ? ($totalValue / $totalQty) : 0.0,
            ];

            $hppByCategory = $rows
                ->groupBy(fn($r) => $r->category_name ?? 'Uncategorized')
                ->map(function ($grp, $catName) {
                    $qty = (float) $grp->sum('total_qty');
                    $val = (float) $grp->sum('stock_value');
                    return [
                        'category' => (string) $catName,
                        'total_qty' => $qty,
                        'total_value' => $val,
                        'avg_hpp_weighted' => $qty > 0 ? ($val / $qty) : 0.0,
                    ];
                })
                ->values()
                ->all();
        }

        // =========================
        // JSON (AJAX)
        // =========================
        $accept = (string) ($request->header('accept') ?? '');
        $forceJson = str_contains(strtolower($accept), 'application/json');

        if ($request->wantsJson() || $request->expectsJson() || $request->ajax() || $forceJson) {

            $payloadRows = $rows->map(function ($r) use ($isOwner) {
                return [
                    'item_id' => (int) $r->item_id,
                    'item_code' => (string) $r->item_code,
                    'item_name' => (string) $r->item_name,
                    'category' => [
                        'id' => $r->category_id ? (int) $r->category_id : null,
                        'name' => $r->category_name ? (string) $r->category_name : null,
                    ],
                    'total_qty' => (float) $r->total_qty,
                    'fg_qty' => (float) $r->fg_qty,
                    'wip_qty' => (float) $r->wip_qty,

                    'hpp_per_unit' => $isOwner ? (float) $r->hpp_per_unit : null,
                    'stock_value' => $isOwner ? (float) $r->stock_value : null,
                    'ads' => $isOwner ? (float) $r->ads : null,
                    'coverage_days' => $isOwner ? ($r->coverage_days !== null ? (float) $r->coverage_days : null) : null,

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
            ],
        ]);
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
        $role = $user?->role ?? null;

        $warehouseId = $request->input('warehouse_id');

        $rows = InventoryStock::query()
            ->join('warehouses', 'warehouses.id', '=', 'inventory_stocks.warehouse_id')
            ->where('inventory_stocks.item_id', $item->id)

        // 🔒 Scope per ROLE (konsisten dengan items())
            ->when($role === 'operating', function ($q) {
                // Operating: hanya WH-PRD + WIP-%
                $q->where(function ($q2) {
                    $q2->where('warehouses.code', 'WH-PRD')
                        ->orWhere('warehouses.code', 'LIKE', 'WIP-%');
                });
            })
            ->when($role === 'admin', function ($q) {
                // Admin: hanya WH-RTS
                $q->where('warehouses.code', 'WH-RTS');
            })
        // Owner / role lain: tanpa pembatasan gudang

        // Filter gudang spesifik dari request (kalau ada)
            ->when($warehouseId, fn($q) => $q->where('inventory_stocks.warehouse_id', $warehouseId))

            ->selectRaw('
            warehouses.id,
            warehouses.code,
            warehouses.name,
            SUM(inventory_stocks.qty) AS qty
        ')
            ->groupBy('warehouses.id', 'warehouses.code', 'warehouses.name')
            ->havingRaw('SUM(inventory_stocks.qty) <> 0')
            ->orderBy('warehouses.code')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'code' => (string) $row->code,
                    'name' => (string) $row->name,
                    'qty' => (float) $row->qty,
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
            ],
            'locations' => $rows,
        ]);
    }

}
