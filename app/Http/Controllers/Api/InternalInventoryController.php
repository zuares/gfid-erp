<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Inventory\StockPositionNotFoundException;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalInventoryController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function stock(Request $request): JsonResponse
    {
        $input = $request->validate([
            'sku' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/'],
            'warehouse' => ['sometimes', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
        ]);

        try {
            $position = $this->inventory->getStockPositionForSku(
                $input['sku'],
                $input['warehouse'] ?? null,
            );
        } catch (StockPositionNotFoundException $exception) {
            return response()->json([
                'message' => $exception->resource === 'sku' ? 'SKU tidak ditemukan.' : 'Warehouse tidak ditemukan.',
            ], 404);
        }

        if (isset($input['warehouse'])) {
            $stock = $position['warehouses'][0];

            return response()->json([
                'sku' => $position['sku'],
                'warehouse' => $stock['warehouse'],
                'on_hand' => $stock['on_hand'],
                'allocated' => $stock['allocated'],
                'available' => $stock['available'],
            ]);
        }

        return response()->json([
            'sku' => $position['sku'],
            'warehouses' => array_map(static fn (array $stock): array => [
                'warehouse' => $stock['warehouse'],
                'on_hand' => $stock['on_hand'],
                'allocated' => $stock['allocated'],
                'available' => $stock['available'],
            ], $position['warehouses']),
            'total' => $position['total'],
        ]);
    }
}
