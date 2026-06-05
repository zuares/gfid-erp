<?php

namespace App\Http\Controllers\Sales\Reports;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoiceLine;
use App\Models\Store;
use App\Support\ReportDateRange;
use Illuminate\Http\Request;

class ChannelProfitReportController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportDateRange::fromRequest($request);

        $filters = [
            'date_from' => $range->from,
            'date_to' => $range->to,
            'store_id' => $request->input('store_id'),
            // 🔥 filter baru: hanya store dengan shipped% di bawah angka ini (misal 80)
            'shipped_below' => $request->input('shipped_below'),
        ];

        $rows = SalesInvoiceLine::query()
            ->join('sales_invoices', 'sales_invoice_lines.sales_invoice_id', '=', 'sales_invoices.id')
            ->leftJoin('stores', 'sales_invoices.store_id', '=', 'stores.id')
            ->leftJoin('shipments', 'sales_invoices.id', '=', 'shipments.sales_invoice_id')
            ->where('sales_invoices.status', 'posted')
            ->when($filters['date_from'], function ($q, $v) {
                $q->whereDate('sales_invoices.date', '>=', $v);
            })
            ->when($filters['date_to'], function ($q, $v) {
                $q->whereDate('sales_invoices.date', '<=', $v);
            })
            ->when($filters['store_id'], function ($q, $v) {
                $q->where('sales_invoices.store_id', $v);
            })
            ->selectRaw('
                COALESCE(stores.id, 0) as store_id,
                COALESCE(stores.code, "NO-STORE") as store_code,
                COALESCE(stores.name, "Tanpa Store") as store_name,

                SUM(sales_invoice_lines.line_total) as total_sales,
                SUM(sales_invoice_lines.hpp_unit_snapshot * sales_invoice_lines.qty) as total_hpp,
                SUM(sales_invoice_lines.margin_total) as total_margin,

                COUNT(DISTINCT sales_invoices.id) as invoice_count,
                COUNT(DISTINCT CASE WHEN shipments.id IS NOT NULL THEN sales_invoices.id END) as invoice_shipped_count
            ')
            ->groupBy('stores.id', 'stores.code', 'stores.name')
            ->orderBy('store_code')
            ->get();

        // Hitung persentase & apply filter shipped_below di PHP
        foreach ($rows as $row) {
            $row->margin_percent = $row->total_sales > 0
            ? round($row->total_margin / $row->total_sales * 100, 2)
            : 0;

            $row->shipped_percent = $row->invoice_count > 0
            ? round($row->invoice_shipped_count / $row->invoice_count * 100, 2)
            : 0;
        }

        // 🔥 Filter: hanya store yang shipped% di bawah X (misal 80)
        if ($filters['shipped_below'] !== null && $filters['shipped_below'] !== '') {
            $threshold = (float) $filters['shipped_below'];
            $rows = $rows->filter(function ($row) use ($threshold) {
                return $row->shipped_percent < $threshold;
            })->values(); // reset index
        }

        // Totals dihitung dari rows yang SUDAH terfilter
        $totals = [
            'sales' => (float) $rows->sum('total_sales'),
            'hpp' => (float) $rows->sum('total_hpp'),
            'margin' => (float) $rows->sum('total_margin'),
        ];
        $totals['margin_percent'] = $totals['sales'] > 0
        ? round($totals['margin'] / $totals['sales'] * 100, 2)
        : 0;

        $totals['invoice_count'] = (int) $rows->sum('invoice_count');
        $totals['invoice_shipped_count'] = (int) $rows->sum('invoice_shipped_count');
        $totals['shipped_percent'] = $totals['invoice_count'] > 0
        ? round($totals['invoice_shipped_count'] / $totals['invoice_count'] * 100, 2)
        : 0;

        return view('sales.reports.channel_profit', [
            'rows' => $rows,
            'totals' => $totals,
            'filters' => $filters,
            'stores' => Store::orderBy('code')->get(),
        ]);
    }
}
