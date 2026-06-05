<?php

namespace App\Http\Controllers\Sales\Reports;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesInvoiceLine;
use App\Support\ReportDateRange;
use Illuminate\Http\Request;

class ItemProfitReportController extends Controller
{
    public function index(Request $request)
    {
        $range = ReportDateRange::fromRequest($request);
        $dateFrom = $range->from;
        $dateTo = $range->to;
        $customerId = $request->query('customer_id');

        $customers = Customer::orderBy('name')->get();

        $linesQuery = SalesInvoiceLine::query()
            ->with(['invoice.customer', 'item'])
            ->where('hpp_unit_snapshot', '>', 0)
            ->whereHas('invoice', function ($q) use ($dateFrom, $dateTo, $customerId) {
                $q->where('status', 'posted');

                if ($dateFrom && $dateTo) {
                    $q->whereDate('date', '>=', $dateFrom)
                        ->whereDate('date', '<=', $dateTo);
                }

                if ($customerId) {
                    $q->where('customer_id', $customerId);
                }
            });

        $lines = $linesQuery->get();

        $rows = $lines->groupBy('item_id')->map(function ($group) {
            $first = $group->first();

            $qty = $group->sum('qty');

            $hppTotal = $group->sum(function ($line) {
                return $line->hpp_unit_snapshot * $line->qty;
            });

            $revenue = $group->sum('line_total');

            $hppUnit = $qty > 0 ? ($hppTotal / $qty) : 0;

            $marginTotal = $revenue - $hppTotal;
            $marginUnit = $qty > 0 ? ($marginTotal / $qty) : 0;

            return (object) [
                'item' => $first->item,
                'qty' => $qty,
                'revenue' => $revenue,
                'hpp_unit' => $hppUnit,
                'hpp_total' => $hppTotal,
                'margin_unit' => $marginUnit,
                'margin_total' => $marginTotal,
            ];
        })->values();

        // 🔢 Grand total untuk footer
        $totals = (object) [
            'qty' => $rows->sum('qty'),
            'revenue' => $rows->sum('revenue'),
            'hpp_total' => $rows->sum('hpp_total'),
            'margin_total' => $rows->sum('margin_total'),
        ];

        return view('sales.reports.item_profit', [
            'rows' => $rows,
            'totals' => $totals,
            'customers' => $customers,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'customerId' => $customerId,
        ]);
    }
}
