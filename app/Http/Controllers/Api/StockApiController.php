<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;

class StockApiController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    public function available(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'item_id' => ['required', 'exists:items,id'],
        ]);

        $warehouseId = (int) $validated['warehouse_id'];
        $itemId = (int) $validated['item_id'];

        $available = $this->inventory->getAvailableStock($warehouseId, $itemId);

        return response()->json([
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'available' => $available,
        ]);
    }

    public function summary(Request $request)
    {
        $validated = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
        ]);

        $itemId = (int) $validated['item_id'];

        $summary = $this->inventory->getStockSummaryForItem($itemId);

        return response()->json($summary);
    }
}
