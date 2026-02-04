<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductionOrder $order)
    {
        $targets = $order->lines()->with('item')->get();

        // Target aggregated per item (FG target)
        $targetByItem = $order->lines()
            ->select('item_id', DB::raw('SUM(qty_target) as qty_target'))
            ->groupBy('item_id')
            ->pluck('qty_target', 'item_id');

        $fgTargetTotal = (float) $targetByItem->sum();

        // WIP warehouse
        $wipWh = \App\Models\Warehouse::where('code', 'WIP-PROD')->firstOrFail();

        // WIP live stock for target FG items (optional table)
        $targetItemIds = $targetByItem->keys()->all();

        $wipStockByItem = DB::table('inventory_stocks')
            ->where('warehouse_id', $wipWh->id)
            ->whereIn('item_id', $targetItemIds)
            ->pluck('qty', 'item_id');

        // ✅ Only POSTED docs for KPI calc
        $postedIssueIds = $order->issues()->where('status', 'posted')->pluck('id');
        $postedReceiptIds = $order->receipts()->where('status', 'posted')->pluck('id');

        // RM Issued to WIP (Material units)
        $rmIssuedToWip = (float) DB::table('inventory_mutations')
            ->where('source_type', 'production_issue')
            ->whereIn('source_id', $postedIssueIds)
            ->where('direction', 'in') // masuk ke WIP-PROD
            ->sum('qty_change');

        // FG Received (Finished units)
        $fgReceivedTotal = (float) DB::table('inventory_mutations')
            ->where('source_type', 'production_receipt')
            ->whereIn('source_id', $postedReceiptIds)
            ->where('direction', 'in') // masuk ke WH-FG
            ->sum('qty_change');

        // Received per FG item
        $receivedByItem = DB::table('inventory_mutations')
            ->where('source_type', 'production_receipt')
            ->whereIn('source_id', $postedReceiptIds)
            ->where('direction', 'in')
            ->select('item_id', DB::raw('SUM(qty_change) as qty_received'))
            ->groupBy('item_id')
            ->pluck('qty_received', 'item_id');

        $fgRemainingTotal = max(0, $fgTargetTotal - $fgReceivedTotal);

        // RM stock in WIP (LIVE) => sum material in WIP
        $rmStockInWipLive = (float) DB::table('inventory_stocks as s')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->where('s.warehouse_id', $wipWh->id)
            ->where('s.qty', '!=', 0)
            ->where('i.type', 'material')
            ->sum('s.qty');

        // Activity summary
        $activities = $order->activities()
            ->select('process', DB::raw('SUM(qty) as total'))
            ->groupBy('process')
            ->pluck('total', 'process');

        // Lists
        $issues = $order->issues()
            ->with('lines.item')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $receipts = $order->receipts()
            ->with('lines.item')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $activityRows = $order->activities()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        // =========================================================
        // TOP WIP RM ITEMS (2 tabs): FABRIC vs ACCESSORIES
        // =========================================================
        $fabricCategoryIds = [1]; // MAT
        $accessoryCategoryIds = [2, 13, 12]; // ACC + PACK + BPU

        $selectCols = [
            's.item_id',
            's.qty',
            'i.code',
            'i.name',
            'i.item_category_id',
            DB::raw('COALESCE(c.name, "-") as category_name'),
        ];

        $topWipFabric = DB::table('inventory_stocks as s')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->leftJoin('item_categories as c', 'c.id', '=', 'i.item_category_id')
            ->where('s.warehouse_id', $wipWh->id)
            ->where('s.qty', '!=', 0)
            ->where('i.type', 'material')
            ->whereIn('i.item_category_id', $fabricCategoryIds)
            ->orderByDesc('s.qty')
            ->limit(10)
            ->get($selectCols);

        $topWipAccessories = DB::table('inventory_stocks as s')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->leftJoin('item_categories as c', 'c.id', '=', 'i.item_category_id')
            ->where('s.warehouse_id', $wipWh->id)
            ->where('s.qty', '!=', 0)
            ->where('i.type', 'material')
            ->whereIn('i.item_category_id', $accessoryCategoryIds)
            ->orderByDesc('s.qty')
            ->limit(10)
            ->get($selectCols);

        // ✅ 1 query counts + qty by tab
        $allCatIds = array_values(array_unique(array_merge($fabricCategoryIds, $accessoryCategoryIds)));

        $countsRow = DB::table('inventory_stocks as s')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->where('s.warehouse_id', $wipWh->id)
            ->where('s.qty', '!=', 0)
            ->where('i.type', 'material')
            ->whereIn('i.item_category_id', $allCatIds)
            ->selectRaw('
            SUM(CASE WHEN i.item_category_id IN (' . implode(',', $fabricCategoryIds) . ') THEN 1 ELSE 0 END) AS count_fabric,
            SUM(CASE WHEN i.item_category_id IN (' . implode(',', $accessoryCategoryIds) . ') THEN 1 ELSE 0 END) AS count_accessories,
            SUM(CASE WHEN i.item_category_id IN (' . implode(',', $fabricCategoryIds) . ') THEN s.qty ELSE 0 END) AS qty_fabric,
            SUM(CASE WHEN i.item_category_id IN (' . implode(',', $accessoryCategoryIds) . ') THEN s.qty ELSE 0 END) AS qty_accessories
        ')
            ->first();

        $countWipFabric = (int) ($countsRow->count_fabric ?? 0);
        $countWipAccessories = (int) ($countsRow->count_accessories ?? 0);
        $qtyWipFabric = (float) ($countsRow->qty_fabric ?? 0);
        $qtyWipAccessories = (float) ($countsRow->qty_accessories ?? 0);

        return view('production.orders.show', compact(
            'order',
            'targets',
            'targetByItem',
            'fgTargetTotal',
            'fgReceivedTotal',
            'fgRemainingTotal',
            'rmIssuedToWip',
            'rmStockInWipLive',
            'receivedByItem',
            'wipWh',
            'wipStockByItem',
            'topWipFabric',
            'topWipAccessories',
            'countWipFabric',
            'countWipAccessories',
            'qtyWipFabric',
            'qtyWipAccessories',
            'activities',
            'issues',
            'receipts',
            'activityRows'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
