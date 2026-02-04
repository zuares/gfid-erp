<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionActivity;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;

class ProductionActivityController extends Controller
{
    public function store(Request $request, ProductionOrder $order)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'process' => ['required', 'in:cut,sew,fin'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return \DB::transaction(function () use ($data, $order) {

            // total target FG
            $totalTarget = (float) $order->lines()->sum('qty_target');

            // total activity sebelumnya (semua proses)
            $totalLogged = (float) $order->activities()->sum('qty');

            if ($totalTarget > 0 && (($totalLogged + (float) $data['qty']) > ($totalTarget * 1.2))) {
                abort(422, 'Qty activity terlalu besar dibanding target produksi.');
            }

            // 1) Create activity log (no stock by default)
            $activity = ProductionActivity::create([
                'production_order_id' => $order->id,
                'date' => $data['date'],
                'process' => $data['process'],
                'qty' => $data['qty'],
                'notes' => $data['notes'] ?? null,
                'created_by' => \Auth::id(),
            ]);

            // 2) CUT output -> WIP FG (bridge agar Receipt tidak error)
            if ($data['process'] === 'cut') {

                // Ambil FG item dari target order (versi minimal: pakai line pertama)
                $fgLine = $order->lines()->with('item')->orderBy('id')->first();

                if ($fgLine && $fgLine->item_id) {
                    $wipWarehouseId = 15; // WIP-PROD (punyamu)

                    // Upsert stock row
                    \DB::table('inventory_stocks')->updateOrInsert(
                        [
                            'warehouse_id' => $wipWarehouseId,
                            'item_id' => $fgLine->item_id,
                        ],
                        [
                            // SQLite tidak bisa pakai DB::raw di updateOrInsert "qty" dengan aman di semua versi,
                            // jadi kita lakukan 2-step: ensure row exists, lalu update qty.
                            'qty' => 0,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );

                    // Update qty +X (atomic)
                    \DB::table('inventory_stocks')
                        ->where('warehouse_id', $wipWarehouseId)
                        ->where('item_id', $fgLine->item_id)
                        ->update([
                            'qty' => \DB::raw('qty + ' . (float) $data['qty']),
                            'updated_at' => now(),
                        ]);

                    // Mutation IN ke WIP FG
                    \DB::table('inventory_mutations')->insert([
                        'date' => $data['date'],
                        'warehouse_id' => $wipWarehouseId,
                        'item_id' => $fgLine->item_id,
                        'qty_change' => $data['qty'],
                        'direction' => 'in',
                        'source_type' => 'production_activity_cut',
                        'source_id' => $activity->id,
                        'notes' => trim('CUT output → WIP FG' . ($data['notes'] ? ' | ' . $data['notes'] : '')),
                        'created_at' => now(),
                        'updated_at' => now(),
                        'lot_id' => null,
                        'unit_cost' => null,
                        'total_cost' => null,
                    ]);
                }
            }

            // 3) Recalc status PO
            app(\App\Services\Production\ProductionOrderStatusService::class)->recalc($order);

            return redirect()
                ->back()
                ->with('success', 'Activity log berhasil disimpan.');
        });
    }

    public function destroy(ProductionActivity $activity)
    {
        return \DB::transaction(function () use ($activity) {

            $order = $activity->productionOrder ?? null;

            // kalau CUT, reverse WIP FG intake
            if ($activity->process === 'cut' && $order) {
                $fgLine = $order->lines()->orderBy('id')->first();

                if ($fgLine && $fgLine->item_id) {
                    $wipWarehouseId = 15; // WIP-PROD

                    // kurangi stok (jaga agar tidak minus)
                    \DB::table('inventory_stocks')
                        ->where('warehouse_id', $wipWarehouseId)
                        ->where('item_id', $fgLine->item_id)
                        ->update([
                            'qty' => \DB::raw('qty - ' . (float) $activity->qty),
                            'updated_at' => now(),
                        ]);

                    // catat mutation OUT reversal
                    \DB::table('inventory_mutations')->insert([
                        'date' => $activity->date->toDateString(),
                        'warehouse_id' => $wipWarehouseId,
                        'item_id' => $fgLine->item_id,
                        'qty_change' => $activity->qty,
                        'direction' => 'out',
                        'source_type' => 'production_activity_cut_void',
                        'source_id' => $activity->id,
                        'notes' => 'Reverse CUT output (delete activity)',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'lot_id' => null,
                        'unit_cost' => null,
                        'total_cost' => null,
                    ]);
                }
            }

            $activity->delete();

            if ($order) {
                app(\App\Services\Production\ProductionOrderStatusService::class)->recalc($order);
            }

            return redirect()
                ->back()
                ->with('success', 'Activity log dihapus.');
        });
    }

}
