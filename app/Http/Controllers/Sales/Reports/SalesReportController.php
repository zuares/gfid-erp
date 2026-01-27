<?php

namespace App\Http\Controllers\Sales\Reports;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        // Default range: 30 hari terakhir
        $tz = config('app.timezone', 'Asia/Jakarta');

        $from = $request->query('from')
        ? Carbon::parse($request->query('from'), $tz)->startOfDay()
        : now($tz)->subDays(29)->startOfDay();

        $to = $request->query('to')
        ? Carbon::parse($request->query('to'), $tz)->endOfDay()
        : now($tz)->endOfDay();

        // Guard: kalau kebalik, swap
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        // KPI ringkas (total qty & total value)
        $kpi = DB::table('daily_item_sales as d')
            ->whereBetween('d.date', [$fromDate, $toDate])
            ->selectRaw('COALESCE(SUM(d.qty_sold),0) as total_qty')
            ->selectRaw('COALESCE(SUM(d.value_sold),0) as total_value')
            ->first();

        // Tren harian (buat tabel kecil / mini chart)
        $daily = DB::table('daily_item_sales as d')
            ->whereBetween('d.date', [$fromDate, $toDate])
            ->groupBy('d.date')
            ->orderBy('d.date')
            ->selectRaw('d.date as date')
            ->selectRaw('COALESCE(SUM(d.qty_sold),0) as qty')
            ->selectRaw('COALESCE(SUM(d.value_sold),0) as value')
            ->get();

        // Top produk by qty (performa produk)
        $topByQty = DB::table('daily_item_sales as d')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->whereBetween('d.date', [$fromDate, $toDate])
            ->groupBy('d.item_id', 'i.code', 'i.name', 'i.unit')
            ->orderByDesc(DB::raw('SUM(d.qty_sold)'))
            ->limit(50)
            ->selectRaw('d.item_id')
            ->selectRaw('i.code, i.name, i.unit')
            ->selectRaw('COALESCE(SUM(d.qty_sold),0) as qty')
            ->selectRaw('COALESCE(SUM(d.value_sold),0) as value')
            ->get();

        // Top produk by value (revenue)
        $topByValue = DB::table('daily_item_sales as d')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->whereBetween('d.date', [$fromDate, $toDate])
            ->groupBy('d.item_id', 'i.code', 'i.name', 'i.unit')
            ->orderByDesc(DB::raw('SUM(d.value_sold)'))
            ->limit(50)
            ->selectRaw('d.item_id')
            ->selectRaw('i.code, i.name, i.unit')
            ->selectRaw('COALESCE(SUM(d.qty_sold),0) as qty')
            ->selectRaw('COALESCE(SUM(d.value_sold),0) as value')
            ->get();

        // Average Daily Sales (ADS) per item di range yang sama
        // ADS = total_qty / jumlah hari pada range (bukan hari yang ada transaksi)
        $daysCount = max(1, (int) $from->diffInDays($to) + 1);

        $ads = DB::table('daily_item_sales as d')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->whereBetween('d.date', [$fromDate, $toDate])
            ->groupBy('d.item_id', 'i.code', 'i.name', 'i.unit')
            ->orderByDesc(DB::raw('SUM(d.qty_sold)'))
            ->limit(50)
            ->selectRaw('d.item_id')
            ->selectRaw('i.code, i.name, i.unit')
            ->selectRaw('COALESCE(SUM(d.qty_sold),0) as total_qty')
            ->get()
            ->map(function ($row) use ($daysCount) {
                $row->ads = round(((float) $row->total_qty) / $daysCount, 2);
                return $row;
            });

        return view('sales.reports.sales_report.index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'kpi' => $kpi,
            'daily' => $daily,
            'topByQty' => $topByQty,
            'topByValue' => $topByValue,
            'ads' => $ads,
            'daysCount' => $daysCount,
        ]);
    }
}
