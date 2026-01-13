<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockApiController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * GET /api/stock/available
     * route('api.stock.available')
     *
     * ?warehouse_id=...&item_id=...
     * return: { "available": 10 }
     */
    public function available(Request $request): JsonResponse
    {
        $warehouseId = (int) $request->query('warehouse_id');
        $itemId = (int) $request->query('item_id');

        if (!$warehouseId || !$itemId) {
            return response()->json([
                'available' => 0,
                'message' => 'warehouse_id dan item_id wajib diisi.',
            ], 422);
        }

        $available = $this->inventory->getAvailableStock($warehouseId, $itemId);

        return response()->json([
            'available' => (float) $available,
        ]);
    }

    /**
     * GET /api/stock/summary
     * route('api.stock.summary')
     *
     * ?item_id=...
     *
     * return:
     * {
     *   "item": { "id": 1, "code": "...", "name": "..." },
     *   "warehouses": [
     *      { "warehouse_id": 8, "code": "WH-PRD", "name": "...", "on_hand": 10, "reserved": 0, "available": 10 },
     *      ...
     *   ]
     * }
     */
    public function summary(Request $request): JsonResponse
    {
        $itemId = (int) $request->query('item_id');

        if (!$itemId) {
            return response()->json([
                'message' => 'item_id wajib diisi.',
            ], 422);
        }

        // Ambil info item + HPP (items.hpp)
        $item = Item::select('id', 'code', 'name', 'hpp')->findOrFail($itemId);
        $hppPerUnit = (float) ($item->hpp ?? 0);

        // Ambil stok per gudang dari inventory_stocks
        $rows = DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->where('s.item_id', $itemId)
            ->orderBy('w.code')
            ->get([
                's.warehouse_id',
                'w.code',
                'w.name',
                's.qty',
            ]);

        $warehouses = $rows->map(function ($row) use ($hppPerUnit) {
            $onHand = (float) ($row->qty ?? 0);

            return [
                'warehouse_id' => (int) $row->warehouse_id,
                'code' => (string) $row->code,
                'name' => (string) $row->name,
                'on_hand' => $onHand,
                'reserved' => 0.0,
                'available' => $onHand,

                // ✅ HPP + Value
                'hpp_per_unit' => $hppPerUnit,
                'stock_value' => $onHand * $hppPerUnit,
            ];
        })->values();

        $totalQty = (float) $warehouses->sum('on_hand');
        $totalValue = (float) $warehouses->sum('stock_value');

        return response()->json([
            'item' => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'hpp' => $hppPerUnit,
            ],
            'totals' => [
                'qty' => $totalQty,
                'value' => $totalValue,
            ],
            'warehouses' => $warehouses->all(),
        ]);
    }

}
