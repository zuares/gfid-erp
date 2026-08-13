<?php

namespace App\Http\Controllers\Sales\Reports;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Store;
use App\Support\ReportDateRange;
use Illuminate\Http\Request;

class ShipmentReportController extends Controller
{
    /**
     * Laporan Pengiriman.
     * Ringkasan + daftar shipment per periode/store/status,
     * lengkap dengan jumlah lines, qty, dan estimasi HPP.
     */
    public function index(Request $request)
    {
        $range = ReportDateRange::fromRequest($request);

        $filters = [
            'date_from' => $range->from,
            'date_to'   => $range->to,
            'store_id'  => $request->input('store_id'),
            'status'    => (string) $request->input('status', ''),
        ];

        $statusOptions = [
            'draft'     => 'Draft',
            'submitted' => 'Submitted',
            'posted'    => 'Posted',
            'cancelled' => 'Cancelled',
        ];

        $shipments = Shipment::query()
            ->with(['store', 'lines.item'])
            ->when($filters['date_from'], fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($filters['date_to'], fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($filters['store_id'], fn ($q, $v) => $q->where('store_id', $v))
            ->when(
                $filters['status'] !== '' && array_key_exists($filters['status'], $statusOptions),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $rows = $shipments->map(function (Shipment $shipment) {
            $totalQty = 0;
            $totalHpp = 0.0;

            foreach ($shipment->lines as $line) {
                $qty = (int) $line->qty_scanned;
                $totalQty += $qty;

                $hpp = (float) (optional($line->item)->hpp ?? 0);
                if ($hpp > 0) {
                    $totalHpp += $qty * $hpp;
                }
            }

            return (object) [
                'shipment'    => $shipment,
                'total_lines' => $shipment->lines->count(),
                'total_qty'   => $totalQty,
                'total_hpp'   => $totalHpp,
            ];
        });

        $summary = [
            'total_shipments' => $rows->count(),
            'total_qty'       => (int) $rows->sum('total_qty'),
            'total_hpp'       => (float) $rows->sum('total_hpp'),
        ];

        $stores = Store::orderBy('code')->get();

        // Export CSV opsional: ?export=csv
        if ($request->query('export') === 'csv') {
            return $this->exportCsv($rows, $filters);
        }

        return view('sales.reports.shipment', compact(
            'filters',
            'statusOptions',
            'rows',
            'summary',
            'stores'
        ));
    }

    /**
     * Unduh laporan sebagai CSV.
     */
    protected function exportCsv($rows, array $filters)
    {
        $filename = 'laporan-pengiriman-' . now()->format('Ymd-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel membaca UTF-8 dengan benar
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, ['Tanggal', 'Kode', 'Store', 'Status', 'Lines', 'Qty', 'Total HPP']);

            foreach ($rows as $row) {
                $shipment = $row->shipment;
                fputcsv($out, [
                    optional($shipment->date)->format('Y-m-d'),
                    $shipment->code,
                    $shipment->store ? ($shipment->store->code . ' - ' . $shipment->store->name) : '-',
                    strtoupper((string) $shipment->status),
                    $row->total_lines,
                    $row->total_qty,
                    round($row->total_hpp),
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }
}
