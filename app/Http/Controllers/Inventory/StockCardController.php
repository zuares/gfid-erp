<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMutation;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class StockCardController extends Controller
{

    /**
     * Kartu stok per item.
     *
     * Filter:
     * - item_id (wajib untuk tampilkan kartu stok)
     * - warehouse_id (opsional, kalau kosong → semua gudang)
     * - from_date, to_date
     */
    public function index(Request $request)
    {
        $items = Item::where('active', 1)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        $itemId = $request->input('item_id');
        $warehouseId = $request->input('warehouse_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $mutations = collect();
        $openingQty = 0;
        $closingQty = 0;
        $selectedItem = null;

        if ($itemId) {
            $selectedItem = Item::find($itemId);

            // default periode: 30 hari terakhir kalau kosong
            if (!$fromDate && !$toDate) {
                $toDate = now()->toDateString();
                $fromDate = now()->subDays(30)->toDateString();
            }

            // base query
            $baseQuery = InventoryMutation::with('warehouse')
                ->where('item_id', $itemId);

            if ($warehouseId) {
                $baseQuery->where('warehouse_id', $warehouseId);
            }

            // saldo awal = sum qty_change sebelum fromDate
            if ($fromDate) {
                $openingQuery = clone $baseQuery;
                $openingQty = (float) $openingQuery
                    ->whereDate('date', '<', $fromDate)
                    ->sum('qty_change');
            }

            // mutasi periode
            $mutationsQuery = clone $baseQuery;

            if ($fromDate) {
                $mutationsQuery->whereDate('date', '>=', $fromDate);
            }
            if ($toDate) {
                $mutationsQuery->whereDate('date', '<=', $toDate);
            }

            $mutations = $mutationsQuery
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            // running balance
            $running = $openingQty;
            foreach ($mutations as $m) {
                $running += (float) $m->qty_change;
                // tambahan property dinamis untuk dipakai di view
                $m->running_balance = $running;
            }

            $closingQty = $running;
        }

        return view('inventory.stock_card', [
            'items' => $items,
            'warehouses' => $warehouses,
            'mutations' => $mutations,
            'openingQty' => $openingQty,
            'closingQty' => $closingQty,
            'selectedItem' => $selectedItem,
            'filters' => [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }
}
