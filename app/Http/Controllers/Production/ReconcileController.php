<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReconcileController extends Controller
{
    public function index()
    {
        // 1. Item stok negatif (semua gudang)
        $negativeStocks = DB::table('inventory_stocks as s')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->where('s.qty', '<', 0)
            ->select([
                's.item_id',
                's.warehouse_id',
                'i.code as item_code',
                'i.name as item_name',
                'w.code as warehouse_code',
                'w.name as warehouse_name',
                's.qty',
            ])
            ->orderBy('w.code')
            ->orderBy('i.code')
            ->get();

        // Guard: kolom baru hanya ada setelah php artisan migrate dijalankan
        $schema = DB::getSchemaBuilder();
        $hasBomGapsCol     = $schema->hasColumn('finishing_job_lines', 'bom_has_gaps');
        $hasPendingCostCol = $schema->hasColumn('sewing_pickup_supply_lines', 'pending_cost');

        // 2. Finishing job lines dengan BOM gap
        $bomGapLines = $hasBomGapsCol
            ? DB::table('finishing_job_lines as fjl')
                ->join('finishing_jobs as fj', 'fj.id', '=', 'fjl.finishing_job_id')
                ->join('items as i', 'i.id', '=', 'fjl.item_id')
                ->where('fjl.bom_has_gaps', true)
                ->where('fj.status', '!=', 'void')
                ->select([
                    'fj.id as job_id',
                    'fj.code as job_code',
                    'fj.date as job_date',
                    'fjl.id as line_id',
                    'fjl.item_id as fg_item_id',
                    'i.code as item_code',
                    'i.name as item_name',
                    'fjl.qty_ok',
                ])
                ->orderBy('fj.date', 'desc')
                ->orderBy('fj.code', 'desc')
                ->get()
            : collect();

        // Enrich: cari material BOM packing_supply yang belum ada cost di RM
        $missingMaterials = collect();
        if ($bomGapLines->isNotEmpty()) {
            $rmId = DB::table('warehouses')->where('code', 'RM')->value('id');
            $fgItemIds = $bomGapLines->pluck('fg_item_id')->unique()->filter()->values();

            if ($fgItemIds->isNotEmpty() && $rmId) {
                $bomMaterials = DB::table('item_bom_lines as ibl')
                    ->join('item_boms as ib', 'ib.id', '=', 'ibl.item_bom_id')
                    ->join('items as mat', 'mat.id', '=', 'ibl.material_item_id')
                    ->leftJoin('inventory_mutations as im', function ($join) use ($rmId) {
                        $join->on('im.item_id', '=', 'ibl.material_item_id')
                             ->where('im.warehouse_id', '=', $rmId)
                             ->where('im.direction', '=', 'in');
                    })
                    ->whereIn('ib.item_id', $fgItemIds)
                    ->where('ib.active', true)
                    ->where('ibl.usage_stage', 'packing_supply')
                    ->where('ibl.is_optional', false)
                    ->whereNull('im.id')
                    ->select([
                        'ib.item_id as fg_item_id',
                        'mat.id as mat_id',
                        'mat.code as mat_code',
                        'mat.name as mat_name',
                        'ibl.qty as bom_qty',
                    ])
                    ->distinct()
                    ->get();

                $missingMaterials = $bomMaterials->groupBy('fg_item_id');
            }
        }

        // 3. Kelengkapan jahit BELUM DIKELUARKAN (issued_qty < required_qty)
        // Pakai sewing_pickup_supply_lines (pickup-level aggregate)
        $unissuedSupplies = DB::table('sewing_pickup_supply_lines as sl')
            ->join('sewing_pickups as sp', 'sp.id', '=', 'sl.sewing_pickup_id')
            ->join('items as i', 'i.id', '=', 'sl.material_item_id')
            ->whereRaw('CAST(sl.issued_qty AS REAL) < CAST(sl.required_qty AS REAL) - 0.0001')
            ->where('sl.required_qty', '>', 0)
            ->where('sp.status', '!=', 'void')
            ->select([
                'sp.id as pickup_id',
                'sp.code as pickup_code',
                'sp.date as pickup_date',
                'sp.status as pickup_status',
                'i.code as item_code',
                'i.name as item_name',
                DB::raw('CAST(sl.required_qty AS REAL) as required_qty'),
                DB::raw('CAST(sl.issued_qty AS REAL) as issued_qty'),
                DB::raw('CAST(sl.required_qty AS REAL) - CAST(sl.issued_qty AS REAL) as gap_qty'),
                'sl.uom',
            ])
            ->orderBy('sp.date', 'desc')
            ->orderBy('sp.code', 'desc')
            ->orderBy('i.code')
            ->get();

        // 4. Kelengkapan jahit sudah keluar tapi pending GRN cost
        $pendingSupplies = $hasPendingCostCol
            ? DB::table('sewing_pickup_supply_lines as sl')
                ->join('sewing_pickups as sp', 'sp.id', '=', 'sl.sewing_pickup_id')
                ->join('items as i', 'i.id', '=', 'sl.material_item_id')
                ->where('sl.pending_cost', true)
                ->where('sl.issued_qty', '>', 0)
                ->where('sp.status', '!=', 'void')
                ->select([
                    'sp.id as pickup_id',
                    'sp.code as pickup_code',
                    'sp.date as pickup_date',
                    'i.code as item_code',
                    'i.name as item_name',
                    'sl.issued_qty',
                    'sl.uom',
                ])
                ->orderBy('sp.date', 'desc')
                ->orderBy('sp.code', 'desc')
                ->get()
            : collect();

        $totalGaps = $negativeStocks->count()
            + $bomGapLines->count()
            + $unissuedSupplies->count()
            + $pendingSupplies->count();

        return view('production.reconcile.index', compact(
            'negativeStocks',
            'bomGapLines',
            'missingMaterials',
            'unissuedSupplies',
            'pendingSupplies',
            'totalGaps'
        ));
    }
}
