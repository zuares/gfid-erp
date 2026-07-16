<?php

class Temp2 {
    protected function getLotStockRowsForItems(array $itemIds, int $warehouseId, $itemsCollection = null): array
    {
        $mutations = \App\Models\InventoryMutation::query()
            ->selectRaw('item_id, lot_id, SUM(qty_change) as lot_qty')
            ->where('warehouse_id', $warehouseId)
            ->whereIn('item_id', $itemIds)
            ->whereNotNull('lot_id')
            ->groupBy('item_id', 'lot_id')
            ->havingRaw('SUM(qty_change) != 0')
            ->get();
            
        $results = [];
        if ($mutations->isEmpty()) {
            return $results;
        }
        
        $lots = \App\Models\Lot::whereIn('id', $mutations->pluck('lot_id')->unique())->get()->keyBy('id');
        $items = $itemsCollection ? $itemsCollection->keyBy('id') : \App\Models\Item::whereIn('id', $itemIds)->get()->keyBy('id');

        foreach ($mutations as $mut) {
            $item = $items[$mut->item_id] ?? null;
            $lot = $lots[$mut->lot_id] ?? null;
            if (!$item || !$lot) continue;

            $results[] = [
                'id' => $item->id,
                'code' => $item->code ?? '',
                'name' => $item->name ?? '',
                'on_hand' => (float) $mut->lot_qty,
                'lot_id' => $lot->id,
                'lot_code' => $lot->code,
                'not_in_warehouse' => abs((float) $mut->lot_qty) < 0.000001,
            ];
        }

        return $results;
    }

    public function itemsForWarehouse(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id');
        if (!$warehouseId) {
            return response()->json([], 400);
        }

        $q = trim((string) $request->get('q', ''));
        $itemId = $request->integer('item_id');

        if ($itemId) {
            $item = \App\Models\Item::with('itemRole')->find($itemId);
            if (!$item) {
                return response()->json([], 404);
            }

            if (($item->itemRole->code ?? '') === 'RM') {
                $lots = $this->getLotStockRowsForItems([$item->id], $warehouseId);
                if (!empty($lots)) {
                    return response()->json($lots);
                }
            }

            $qty = (float) \App\Models\InventoryStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('item_id', $item->id)
                ->value('qty');

            return response()->json([[
                'id' => $item->id,
                'code' => $item->code ?? '',
                'name' => $item->name ?? '',
                'on_hand' => $qty,
                'lot_id' => null,
                'lot_code' => null,
                'not_in_warehouse' => abs($qty) < 0.000001,
            ]]);
        }

        if ($q === '') {
            $stocks = \App\Models\InventoryStock::query()
                ->with(['item.itemRole'])
                ->where('warehouse_id', $warehouseId)
                ->where('qty', '!=', 0)
                ->orderBy('item_id')
                ->limit(500)
                ->get();

            $results = [];
            $rmItemIds = [];
            
            foreach ($stocks as $stock) {
                if (($stock->item?->itemRole->code ?? '') === 'RM') {
                    $rmItemIds[] = $stock->item_id;
                } else {
                    $results[] = [
                        'id' => $stock->item_id,
                        'code' => $stock->item?->code ?? '',
                        'name' => $stock->item?->name ?? '',
                        'on_hand' => (float) $stock->qty,
                        'lot_id' => null,
                        'lot_code' => null,
                        'not_in_warehouse' => false,
                    ];
                }
            }

            if (!empty($rmItemIds)) {
                $rmLots = $this->getLotStockRowsForItems($rmItemIds, $warehouseId);
                $results = array_merge($results, $rmLots);
            }

            return response()->json($results);
        }

        $items = \App\Models\Item::query()
            ->with('itemRole')
            ->where(function ($sub) use ($q) {
                $sub->where('code', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%');
            })
            ->orderBy('code')
            ->limit(50)
            ->get();

        $itemIds = $items->pluck('id')->toArray();
        if (empty($itemIds)) {
            return response()->json([]);
        }

        $stocks = \App\Models\InventoryStock::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('item_id', $itemIds)
            ->pluck('qty', 'item_id');

        $results = [];
        $rmItemIds = [];

        foreach ($items as $item) {
            if (($item->itemRole->code ?? '') === 'RM') {
                $rmItemIds[] = $item->id;
            } else {
                $results[] = [
                    'id' => $item->id,
                    'code' => $item->code ?? '',
                    'name' => $item->name ?? '',
                    'on_hand' => (float) ($stocks[$item->id] ?? 0),
                    'lot_id' => null,
                    'lot_code' => null,
                    'not_in_warehouse' => !isset($stocks[$item->id]) || abs((float) ($stocks[$item->id] ?? 0)) < 0.000001,
                ];
            }
        }

        if (!empty($rmItemIds)) {
            $rmLots = $this->getLotStockRowsForItems($rmItemIds, $warehouseId, $items);
            $foundItemIds = array_column($rmLots, 'id');
            foreach ($rmItemIds as $rmId) {
                if (!in_array($rmId, $foundItemIds)) {
                    $item = $items->firstWhere('id', $rmId);
                    $rmLots[] = [
                        'id' => $item->id,
                        'code' => $item->code ?? '',
                        'name' => $item->name ?? '',
                        'on_hand' => 0,
                        'lot_id' => null,
                        'lot_code' => null,
                        'not_in_warehouse' => true,
                    ];
                }
            }
            $results = array_merge($results, $rmLots);
        }

        return response()->json($results);
    }
}
