<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

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

    /**
     * POST /api/v1/stocks/ready
     *
     * Token-only, read-only, output sempit:
     * [
     *   {"item_code": "ABC", "stock_ready": 12},
     *   {"item_code": "DEF", "stock_ready": 0}
     * ]
     */
    public function readyBatch(Request $request): JsonResponse
    {
        $bearerToken = trim((string) $request->bearerToken());

        if ($bearerToken === '') {
            return response()->json([
                'message' => 'Token bearer wajib digunakan.',
            ], 401);
        }

        $token = PersonalAccessToken::findToken($bearerToken);

        if (! $token) {
            return response()->json([
                'message' => 'Token tidak valid.',
            ], 401);
        }

        if (! $token->can('stock-read')) {
            return response()->json([
                'message' => 'Token tidak memiliki ability stock-read.',
            ], 403);
        }

        $payload = $request->validate([
            'item_codes' => ['required'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'warehouse_code' => ['nullable', 'string', 'max:50'],
        ]);

        $codes = $this->normalizeItemCodes($payload['item_codes']);

        if ($codes->isEmpty()) {
            return response()->json([
                'message' => 'item_codes wajib berisi minimal 1 kode item.',
            ], 422);
        }

        if ($codes->count() > 100) {
            return response()->json([
                'message' => 'Maksimal 100 item_code per request.',
            ], 422);
        }

        $warehouse = null;
        $warehouseFilterUsed = false;

        if (! empty($payload['warehouse_id'])) {
            $warehouseFilterUsed = true;
            $warehouse = Warehouse::query()
                ->whereKey((int) $payload['warehouse_id'])
                ->where('active', 1)
                ->first();
        } elseif (! empty($payload['warehouse_code'])) {
            $warehouseFilterUsed = true;
            $warehouse = Warehouse::query()
                ->whereRaw('UPPER(code) = ?', [strtoupper($payload['warehouse_code'])])
                ->where('active', 1)
                ->first();
        }

        if ($warehouseFilterUsed && ! $warehouse) {
            return response()->json([
                'message' => 'Warehouse tidak ditemukan.',
            ], 404);
        }

        $stocksQuery = DB::table('inventory_stocks as s')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->whereIn(DB::raw('UPPER(i.code)'), $codes->all())
            ->where('w.active', 1);

        if ($warehouse) {
            $stocksQuery->where('s.warehouse_id', $warehouse->id);
        }

        $rows = $stocksQuery
            ->groupBy(DB::raw('UPPER(i.code)'))
            ->selectRaw('
                UPPER(i.code) as item_code,
                COALESCE(SUM(s.qty - s.allocated_qty), 0) as stock_ready
            ')
            ->get()
            ->keyBy('item_code');

        $data = $codes->map(function (string $code) use ($rows) {
            $row = $rows->get($code);

            return [
                'item_code' => $code,
                'stock_ready' => (float) ($row->stock_ready ?? 0),
            ];
        })->values();

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Normalize input item_codes dari array atau string CSV.
     *
     * @param  mixed  $itemCodes
     * @return \Illuminate\Support\Collection<int, string>
     */
    protected function normalizeItemCodes(mixed $itemCodes)
    {
        $codes = is_array($itemCodes)
            ? $itemCodes
            : preg_split('/[,\n\r\t ]+/', (string) $itemCodes, -1, PREG_SPLIT_NO_EMPTY);

        return collect($codes)
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->unique()
            ->values();
    }

}
