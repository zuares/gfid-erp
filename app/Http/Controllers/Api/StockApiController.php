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

        $user = auth()->user();
        $role = strtolower((string) ($user?->role ?? ''));
        $isOwner = $role === 'owner';

        // item header
        $item = Item::select('id', 'code', 'name', 'hpp')->findOrFail($itemId);
        $hppPerUnit = (float) ($item->hpp ?? 0);

        /**
         * Stock per warehouse from inventory_stocks
         */
        $rows = DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->where('s.item_id', $itemId)
            ->groupBy('w.id', 'w.code', 'w.name')
            ->orderBy('w.code')
            ->selectRaw('
            w.id as warehouse_id,
            w.code as code,
            w.name as name,
            COALESCE(SUM(s.qty), 0) as on_hand,
            COALESCE(SUM(s.allocated_qty), 0) as reserved
        ')
            ->havingRaw('COALESCE(SUM(s.qty), 0) <> 0 OR COALESCE(SUM(s.allocated_qty), 0) <> 0')
            ->get();

        $warehouses = $rows->map(function ($row) use ($isOwner, $hppPerUnit) {
            $onHand = (float) ($row->on_hand ?? 0);
            $reserved = (float) ($row->reserved ?? 0);

            $payload = [
                'warehouse_id' => (int) $row->warehouse_id,
                'code' => (string) $row->code,
                'name' => (string) $row->name,
                'on_hand' => $onHand,
                'reserved' => $reserved,
                'available' => $onHand - $reserved,
            ];

            // owner-only sensitive data
            if ($isOwner) {
                $payload['hpp_per_unit'] = $hppPerUnit;
                $payload['stock_value'] = $onHand * $hppPerUnit;
            }

            return $payload;
        })->values();

        $totalQty = (float) $warehouses->sum('on_hand');

        $resp = [
            'item' => [
                'id' => (int) $item->id,
                'code' => (string) $item->code,
                'name' => (string) $item->name,
            ],
            'totals' => [
                'qty' => $totalQty,
            ],
            'warehouses' => $warehouses->all(),
        ];

        if ($isOwner) {
            $totalValue = (float) $warehouses->sum('stock_value');
            $resp['item']['hpp'] = $hppPerUnit;
            $resp['totals']['value'] = $totalValue;
        }

        return response()->json($resp);
    }

}
