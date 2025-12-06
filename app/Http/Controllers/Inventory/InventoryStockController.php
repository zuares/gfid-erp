<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;

class InventoryStockController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * STOK PER ITEM (snapshot dari inventory_stocks)
     */
    // public function items(Request $request)
    // {
    //     $warehouses = Warehouse::orderBy('name')->get();
    //     $items = Item::where('active', 1)
    //         ->with('category')
    //         ->orderBy('code')
    //         ->get();

    //     $warehouseId = $request->input('warehouse_id');
    //     $itemId = $request->input('item_id');
    //     $hasBalanceOnly = $request->boolean('has_balance_only', true);
    //     $search = $request->input('search');

    //     $query = InventoryStock::query()
    //         ->with(['item.category', 'warehouse']);

    //     if ($warehouseId) {
    //         $query->where('warehouse_id', $warehouseId);
    //     }

    //     if ($itemId) {
    //         $query->where('item_id', $itemId);
    //     }

    //     if ($hasBalanceOnly) {
    //         $query->where('qty', '!=', 0);
    //     }

    //     if ($search) {
    //         $like = '%' . trim($search) . '%';
    //         $query->whereHas('item', function ($q) use ($like) {
    //             $q->where('code', 'like', $like)
    //                 ->orWhere('name', 'like', $like);
    //         });
    //     }

    //     $stocks = $query
    //         ->orderBy('warehouse_id')
    //         ->orderBy('item_id')
    //         ->paginate(50)
    //         ->withQueryString();

    //     return view('inventory.stocks.items', [
    //         'stocks' => $stocks,
    //         'warehouses' => $warehouses,
    //         'items' => $items,
    //         'filters' => [
    //             'warehouse_id' => $warehouseId,
    //             'item_id' => $itemId,
    //             'has_balance_only' => $hasBalanceOnly,
    //             'search' => $search,
    //         ],
    //     ]);
    // }

    public function items(Request $request)
    {
        $items = Item::where('active', 1)
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::orderBy('name')->get();

        $warehouseId = $request->input('warehouse_id');
        $itemId = $request->input('item_id');
        $search = $request->input('search');
        $hasBalanceOnly = (bool) $request->boolean('has_balance_only', true);

        $query = InventoryStock::query()
            ->join('items', 'items.id', '=', 'inventory_stocks.item_id')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_stocks.warehouse_id')
            ->where('items.active', 1);

        if ($warehouseId) {
            $query->where('inventory_stocks.warehouse_id', $warehouseId);
        }

        if ($itemId) {
            $query->where('inventory_stocks.item_id', $itemId);
        }

        if ($search) {
            $searchLike = '%' . $search . '%';
            $query->where(function ($q) use ($searchLike) {
                $q->where('items.code', 'like', $searchLike)
                    ->orWhere('items.name', 'like', $searchLike);
            });
        }

        $query->selectRaw('
        inventory_stocks.item_id,
        items.code AS item_code,
        items.name AS item_name,
        SUM(inventory_stocks.qty) AS total_qty,
        SUM(CASE WHEN warehouses.code = "WH-RTS" THEN inventory_stocks.qty ELSE 0 END) AS fg_qty,
        SUM(CASE WHEN warehouses.code LIKE "WIP-%" THEN inventory_stocks.qty ELSE 0 END) AS wip_qty
    ')
            ->groupBy('inventory_stocks.item_id', 'items.code', 'items.name')
            ->orderBy('items.code');

        if ($hasBalanceOnly) {
            $query->havingRaw('SUM(inventory_stocks.qty) <> 0');
        }

        $stocks = $query->paginate(50)->appends($request->query());

        return view('inventory.stocks.items', [
            'items' => $items,
            'warehouses' => $warehouses,
            'stocks' => $stocks,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'item_id' => $itemId,
                'search' => $search,
                'has_balance_only' => $hasBalanceOnly,
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

        // ambil daftar LOT dengan saldo > 0
        $lotStocks = $this->inventory->getAvailableLots(
            warehouseId: $warehouseId ? (int) $warehouseId : null,
            itemId: $itemId ? (int) $itemId : null,
        );

        // filter cari LOT code
        if ($lotSearch) {
            $needle = mb_strtolower(trim($lotSearch));
            $lotStocks = $lotStocks->filter(function ($row) use ($needle) {
                $code = mb_strtolower($row->lot->code ?? '');
                return str_contains($code, $needle);
            });
        }

        // filter cari item code / name
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

        // sort: gudang → item → lot
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
