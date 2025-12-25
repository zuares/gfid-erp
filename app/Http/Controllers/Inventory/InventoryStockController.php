<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $role = $user?->role ?? null;

        // Untuk dropdown filter item (masih dipakai di view summary / nanti kalau mau dihidupin lagi)
        $items = Item::where('active', 1)
            ->orderBy('name')
            ->get();

        // Untuk dropdown gudang (role-based)
        $warehouses = $this->getWarehousesForDropdown($role);

        // Filters
        $warehouseId = $request->input('warehouse_id');
        $itemId = $request->input('item_id');

        // raw text dari request (buat dikirim balik ke view)
        $searchRaw = trim((string) $request->input('search', ''));

        // versi normalisasi (uppercase) untuk dipakai di LIKE
        $search = Str::upper($searchRaw);

        $hasBalanceOnly = (bool) $request->boolean('has_balance_only', false);

        /**
         * STEP 1: Subquery stok per (item, gudang) dari inventory_mutations
         */
        $base = InventoryMutation::query()
            ->join('warehouses', 'warehouses.id', '=', 'inventory_mutations.warehouse_id');

        // 🔒 Scope per ROLE
        if ($role === 'operating') {
            // Operating: hanya WIP-% dan WH-PRD
            $base->where(function ($q) {
                $q->where('warehouses.code', 'WH-PRD')
                    ->orWhere('warehouses.code', 'LIKE', 'WIP-%');
            });
        } elseif ($role === 'admin') {
            // Admin: hanya WH-RTS
            $base->where('warehouses.code', 'WH-RTS');
        }
        // Owner / user lain: tanpa pembatasan gudang (lihat semua)

        // Filter gudang spesifik (kalau dipilih di filter)
        if ($warehouseId) {
            $base->where('inventory_mutations.warehouse_id', $warehouseId);
        }

        // Subquery stok per item+gudang
        $base->selectRaw('
        inventory_mutations.item_id,
        warehouses.code AS wh_code,
        SUM(inventory_mutations.qty_change) AS qty
    ')
            ->groupBy('inventory_mutations.item_id', 'warehouses.code');

        /**
         * STEP 2: Outer query agregasi per item
         */
        $selectSql = <<<'SQL'
s.item_id,
items.code AS item_code,
items.name AS item_name,

COALESCE(SUM(s.qty), 0) AS total_qty,

COALESCE(SUM(
    CASE
        WHEN s.wh_code LIKE 'WH-%' THEN s.qty
        ELSE 0
    END
), 0) AS fg_qty,

COALESCE(SUM(
    CASE
        WHEN s.wh_code LIKE 'WIP-%' THEN s.qty
        ELSE 0
    END
), 0) AS wip_qty
SQL;

        $query = DB::query()
            ->fromSub($base, 's')
            ->join('items', 'items.id', '=', 's.item_id')
            ->where('items.active', 1);

        // Filter item (di outer, karena join items)
        if ($itemId) {
            $query->where('s.item_id', $itemId);
        }

        /**
         * FILTER SEARCH: multi-term OR
         * Contoh input:
         * - "K7BLK K7WHT"
         * - "K7BLK,K7WHT"
         * - "K7BLK|K7WHT"
         *
         * Dipecah jadi beberapa term, lalu:
         * (code LIKE %term1% OR name LIKE %term1%)
         * OR
         * (code LIKE %term2% OR name LIKE %term2%)
         */
        if ($search !== '') {
            // Pisah pakai spasi, koma, titik koma, atau pipe
            $rawTerms = preg_split('/[\s,;|]+/', $search);

            // Bersihkan kosong
            $terms = array_values(array_filter($rawTerms, fn($t) => $t !== ''));

            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $like = '%' . $term . '%';

                        $q->orWhere(function ($q2) use ($like) {
                            $q2->where('items.code', 'like', $like)
                                ->orWhere('items.name', 'like', $like);
                        });
                    }
                });
            }
        }

        $query->selectRaw($selectSql)
            ->groupBy('s.item_id', 'items.code', 'items.name')
            ->orderBy('items.code');

        // Checkbox "Hanya ada stok" -> hanya item dengan total_qty != 0
        if ($hasBalanceOnly) {
            $query->havingRaw('COALESCE(SUM(s.qty), 0) <> 0');
        }

        // Pagination
        $stocks = $query->paginate(50)->appends($request->query());

        // OPTIONAL: respon JSON untuk AJAX
        if ($request->expectsJson() || $request->ajax()) {
            $rows = $stocks->getCollection()->map(fn($r) => [
                'item_id' => (int) $r->item_id,
                'item_code' => (string) $r->item_code,
                'item_name' => (string) $r->item_name,
                'total_qty' => (float) $r->total_qty,
                'fg_qty' => (float) $r->fg_qty,
                'wip_qty' => (float) $r->wip_qty,
                'locations_url' => route('inventory.stocks.item_locations', $r->item_id),
            ])->values();

            return response()->json([
                'ok' => true,
                'meta' => [
                    'total' => $stocks->total(),
                    'per_page' => $stocks->perPage(),
                    'current_page' => $stocks->currentPage(),
                    'last_page' => $stocks->lastPage(),
                    'from' => $stocks->firstItem() ?? 0,
                    'to' => $stocks->lastItem() ?? 0,
                ],
                'rows' => $rows,
                'pagination_html' => $stocks->hasPages() ? (string) $stocks->links() : '',
            ]);
        }

        // Normal render ke Blade
        return view('inventory.stocks.items', [
            'items' => $items,
            'warehouses' => $warehouses,
            'stocks' => $stocks,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                // pakai raw biar kalau nanti input belum full uppercase tetap
                // tampil sesuai yang diketik user (meski di JS sudah di-upper)
                'search' => $searchRaw,
                'has_balance_only' => $hasBalanceOnly,
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
        $warehouseId = $request->input('warehouse_id');

        $rows = InventoryStock::query()
            ->join('warehouses', 'warehouses.id', '=', 'inventory_stocks.warehouse_id')
            ->where('inventory_stocks.item_id', $item->id)
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
            ->get();

        return response()->json([
            'item' => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
            ],
            'locations' => $rows,
        ]);
    }
}
