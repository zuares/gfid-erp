<?php

namespace App\Http\Controllers\Sales\Reports;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Support\ReportDateRange;
use Illuminate\Http\Request;

class ShipmentAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportDateRange::fromRequest($request);

        $filters = [
            'date_from' => $range->from,
            'date_to' => $range->to,
            'warehouse_id' => $request->input('warehouse_id'),
            'shipping_method' => $request->input('shipping_method'),
        ];

        // Query dasar
        $shipments = Shipment::query()
            ->with(['invoice', 'warehouse'])
            ->when($filters['date_from'], fn($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($filters['date_to'], fn($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($filters['warehouse_id'], fn($q, $v) => $q->where('warehouse_id', $v))
            ->when($filters['shipping_method'], fn($q, $v) => $q->where('shipping_method', $v))
            ->orderBy('date')
            ->get();

        // ===========================
        // 1. LEAD TIME INVOICE → SHIPMENT
        // ===========================
        $leadTimes = $shipments->map(function ($s) {
            if (!$s->invoice?->date) {
                return null;
            }

            $diff = $s->date->diffInDays($s->invoice->date);
            return [
                'shipment_code' => $s->code,
                'invoice_code' => $s->invoice->code,
                'invoice_date' => $s->invoice->date->toDateString(),
                'shipment_date' => $s->date->toDateString(),
                'lead_days' => $diff,
            ];
        })->filter();

        $avgLeadTime = $leadTimes->avg('lead_days') ?? 0;

        // ===========================
        // 2. JUMLAH SHIPMENT PER KURIR
        // ===========================
        $byCourier = $shipments
            ->groupBy('shipping_method')
            ->map(fn($grp) => [
                'shipping_method' => $grp->first()->shipping_method ?: '(Tidak diisi)',
                'total' => $grp->count(),
            ]);

        // ===========================
        // 3. SHIPMENT PER HARI
        // ===========================
        $byDate = $shipments
            ->groupBy(fn($s) => $s->date->format('Y-m-d'))
            ->map(fn($grp, $date) => [
                'date' => $date,
                'total' => $grp->count(),
            ]);

        return view('sales.reports.shipment_analytics', [
            'filters' => $filters,
            'leadTimes' => $leadTimes,
            'avgLeadTime' => round($avgLeadTime, 2),
            'byCourier' => $byCourier,
            'byDate' => $byDate,
            'warehouses' => Warehouse::orderBy('code')->get(),
        ]);
    }

    public function ship(Shipment $shipment)
    {
        if ($shipment->status === 'shipped') {
            return back()->with('info', "Shipment {$shipment->code} sudah shipped.");
        }

        $shipment->load(['lines', 'warehouse']);

        try {
            DB::transaction(function () use ($shipment) {

                foreach ($shipment->lines as $line) {
                    app('App\Services\Inventory\InventoryService')->stockOut(
                        warehouseId: $shipment->warehouse_id,
                        itemId: $line->item_id,
                        qty: $line->qty,
                        date: $shipment->date,
                        sourceType: 'shipment',
                        sourceId: $shipment->id,
                        notes: "Shipment {$shipment->code}",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false,
                    );
                }

                $shipment->update([
                    'status' => 'shipped',
                ]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Shipment {$shipment->code} berhasil dikirim & stok berkurang.");
    }

}
