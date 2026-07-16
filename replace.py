import re

with open('app/Http/Controllers/Inventory/InventoryAdjustmentController.php', 'r') as f:
    content = f.read()

# Replace rules
content = content.replace(
    "'lines.*.item_id' => ['required', 'exists:items,id'],",
    "'lines.*.item_id' => ['required', 'exists:items,id'],\n            'lines.*.lot_id' => ['nullable', 'exists:lots,id'],"
)

# Replace loop logic
old_loop = """            foreach ($validated['lines'] as $lineData) {
                $itemId = (int) $lineData['item_id'];
                $signedChange = (float) $lineData['qty_change'];

                if (abs($signedChange) < 0.000001) {
                    continue;
                }

                $direction = $signedChange >= 0 ? 'in' : 'out';
                $qtyBefore = null;
                $qtyAfter = null;

                if ($isOwner) {
                    $qtyBefore = $inventory->getOnHandQty(
                        warehouseId: $adjustment->warehouse_id,
                        itemId: $itemId
                    );

                    // ✅ resolve cost saat eksekusi langsung (owner)
                    $item = Item::find($itemId);
                    $unitCostOverride = $this->resolveUnitCostForAdjustmentApprove(
                        itemId: $itemId,
                        warehouseId: (int) $adjustment->warehouse_id,
                        item: $item,
                        inventory: $inventory
                    );

                    $mutation = $inventory->adjustByDifference(
                        warehouseId: (int) $adjustment->warehouse_id,
                        itemId: $itemId,
                        qtyChange: $signedChange,
                        date: $adjustment->date,
                        sourceType: InventoryAdjustment::class,
                        sourceId: $adjustment->id,
                        notes: $lineData['notes'] ?? $adjustment->reason,
                        lotId: null,
                        allowNegative: false,
                        unitCostOverride: $unitCostOverride,
                        affectLotCost: false,
                    );

                    if (!$mutation) {
                        continue;
                    }

                    $qtyAfter = $inventory->getOnHandQty(
                        warehouseId: (int) $adjustment->warehouse_id,
                        itemId: $itemId
                    );
                }

                $adjustment->lines()->create([
                    'item_id' => $itemId,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $signedChange,
                    'direction' => $direction,
                    'notes' => $lineData['notes'] ?? null,
                    'lot_id' => null,
                ]);
            }"""

new_loop = """            foreach ($validated['lines'] as $lineData) {
                $itemId = (int) $lineData['item_id'];
                $lotId = isset($lineData['lot_id']) && $lineData['lot_id'] ? (int) $lineData['lot_id'] : null;
                $signedChange = (float) $lineData['qty_change'];

                if (abs($signedChange) < 0.000001) {
                    continue;
                }

                $direction = $signedChange >= 0 ? 'in' : 'out';
                $qtyBefore = null;
                $qtyAfter = null;

                if ($isOwner) {
                    if ($lotId) {
                        $qtyBefore = $inventory->getLotBalance(
                            warehouseId: $adjustment->warehouse_id,
                            itemId: $itemId,
                            lotId: $lotId
                        );
                    } else {
                        $qtyBefore = $inventory->getOnHandQty(
                            warehouseId: $adjustment->warehouse_id,
                            itemId: $itemId
                        );
                    }

                    // ✅ resolve cost saat eksekusi langsung (owner)
                    $item = Item::find($itemId);
                    $unitCostOverride = $this->resolveUnitCostForAdjustmentApprove(
                        itemId: $itemId,
                        warehouseId: (int) $adjustment->warehouse_id,
                        item: $item,
                        inventory: $inventory
                    );

                    $mutation = $inventory->adjustByDifference(
                        warehouseId: (int) $adjustment->warehouse_id,
                        itemId: $itemId,
                        qtyChange: $signedChange,
                        date: $adjustment->date,
                        sourceType: InventoryAdjustment::class,
                        sourceId: $adjustment->id,
                        notes: $lineData['notes'] ?? $adjustment->reason,
                        lotId: $lotId,
                        allowNegative: false,
                        unitCostOverride: $unitCostOverride,
                        affectLotCost: false,
                    );

                    if (!$mutation) {
                        continue;
                    }

                    if ($lotId) {
                        $qtyAfter = $inventory->getLotBalance(
                            warehouseId: (int) $adjustment->warehouse_id,
                            itemId: $itemId,
                            lotId: $lotId
                        );
                    } else {
                        $qtyAfter = $inventory->getOnHandQty(
                            warehouseId: (int) $adjustment->warehouse_id,
                            itemId: $itemId
                        );
                    }
                }

                $adjustment->lines()->create([
                    'item_id' => $itemId,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $signedChange,
                    'direction' => $direction,
                    'notes' => $lineData['notes'] ?? null,
                    'lot_id' => $lotId,
                ]);
            }"""

if old_loop in content:
    content = content.replace(old_loop, new_loop)
else:
    print("WARNING: loop logic not found")

# Now replace itemsForWarehouse
old_items_method = """    public function itemsForWarehouse(Request $request): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id');
        if (!$warehouseId) {
            return response()->json([], 400);
        }

        $q = trim((string) $request->get('q', ''));
        $itemId = $request->integer('item_id');

        if ($itemId) {
            $item = Item::find($itemId);
            if (!$item) {
                return response()->json([], 404);
            }

            $qty = (float) InventoryStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('item_id', $item->id)
                ->value('qty');

            return response()->json([[
                'id' => $item->id,
                'code' => $item->code ?? '',
                'name' => $item->name ?? '',
                'on_hand' => $qty,
                'not_in_warehouse' => abs($qty) < 0.000001,
            ]]);
        }

        if ($q === '') {
            $rows = InventoryStock::query()
                ->with('item')
                ->where('warehouse_id', $warehouseId)
                ->where('qty', '!=', 0)
                ->orderBy('item_id')
                ->limit(500)
                ->get();

            return response()->json(
                $rows->map(fn(InventoryStock $row) => [
                    'id' => $row->item_id,
                    'code' => $row->item?->code ?? '',
                    'name' => $row->item?->name ?? '',
                    'on_hand' => (float) $row->qty,
                ])
            );
        }

        $stocks = InventoryStock::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('item_id', function ($sub) use ($q) {
                $sub->select('id')
                    ->from('items')
                    ->where('code', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%');
            })
            ->pluck('qty', 'item_id');

        $items = Item::query()
            ->where(function ($sub) use ($q) {
                $sub->where('code', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%');
            })
            ->orderBy('code')
            ->limit(50)
            ->get(['id', 'code', 'name']);

        return response()->json(
            $items->map(fn(Item $item) => [
                'id' => $item->id,
                'code' => $item->code ?? '',
                'name' => $item->name ?? '',
                'on_hand' => (float) ($stocks[$item->id] ?? 0),
                'not_in_warehouse' => !isset($stocks[$item->id]) || abs((float) ($stocks[$item->id] ?? 0)) < 0.000001,
            ])
        );
    }"""
    
new_items_method = """    protected function getLotStockRowsForItems(array $itemIds, int $warehouseId, $itemsCollection = null): array
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

    public function itemsForWarehouse(Request $request): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id');
        if (!$warehouseId) {
            return response()->json([], 400);
        }

        $q = trim((string) $request->get('q', ''));
        $itemId = $request->integer('item_id');

        if ($itemId) {
            $item = Item::with('itemRole')->find($itemId);
            if (!$item) {
                return response()->json([], 404);
            }

            if (($item->itemRole->code ?? '') === 'RM') {
                $lots = $this->getLotStockRowsForItems([$item->id], $warehouseId);
                if (!empty($lots)) {
                    return response()->json($lots);
                }
            }

            $qty = (float) InventoryStock::query()
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
            $stocks = InventoryStock::query()
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

        $items = Item::query()
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

        $stocks = InventoryStock::query()
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
    }"""

if old_items_method in content:
    content = content.replace(old_items_method, new_items_method)
else:
    print("WARNING: items method not found")

with open('app/Http/Controllers/Inventory/InventoryAdjustmentController.php', 'w') as f:
    f.write(content)

print("done")
