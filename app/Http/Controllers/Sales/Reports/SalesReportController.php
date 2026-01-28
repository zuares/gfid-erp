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

        // Tren harian (ringkas) - tetap dipakai untuk header date list
        $daily = DB::table('daily_item_sales as d')
            ->whereBetween('d.date', [$fromDate, $toDate])
            ->groupBy('d.date')
            ->orderBy('d.date')
            ->selectRaw('d.date as date')
            ->selectRaw('COALESCE(SUM(d.qty_sold),0) as qty')
            ->selectRaw('COALESCE(SUM(d.value_sold),0) as value')
            ->get();

        // =========================
        // TOP LISTS (Performa)
        // =========================

        // Top Kategori (by qty)
        $topCategories = DB::table('daily_item_sales as d')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('item_categories as c', 'c.id', '=', 'i.item_category_id')
            ->whereBetween('d.date', [$fromDate, $toDate])
            ->groupBy('i.item_category_id', 'c.code', 'c.name')
            ->orderByDesc(DB::raw('SUM(d.qty_sold)'))
            ->limit(50)
            ->selectRaw('i.item_category_id as category_id')
            ->selectRaw("COALESCE(c.code,'-') as category_code")
            ->selectRaw("COALESCE(c.name,'(Tanpa Kategori)') as category_name")
            ->selectRaw('COALESCE(SUM(d.qty_sold),0) as qty')
            ->selectRaw('COALESCE(SUM(d.value_sold),0) as value')
            ->get();

        // Top Produk (by qty)
        $topProducts = DB::table('daily_item_sales as d')
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

        // Top Sales (by value) = top revenue
        $topSales = DB::table('daily_item_sales as d')
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

        // ADS (avg/day)
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

        // =========================
        // ACCORDION: date -> category -> items
        // =========================

        // Ambil detail per tanggal+kategori+item
        $dailyCatItems = DB::table('daily_item_sales as d')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('item_categories as c', 'c.id', '=', 'i.item_category_id')
            ->whereBetween('d.date', [$fromDate, $toDate])
            ->groupBy('d.date', 'i.item_category_id', 'c.code', 'c.name', 'd.item_id', 'i.code', 'i.name', 'i.unit')
            ->orderBy('d.date')
            ->orderBy(DB::raw("COALESCE(c.name,'(Tanpa Kategori)')"))
            ->orderByDesc(DB::raw('SUM(d.qty_sold)'))
            ->selectRaw('d.date as date')
            ->selectRaw('i.item_category_id as category_id')
            ->selectRaw("COALESCE(c.code,'-') as category_code")
            ->selectRaw("COALESCE(c.name,'(Tanpa Kategori)') as category_name")
            ->selectRaw('d.item_id as item_id')
            ->selectRaw('i.code as item_code')
            ->selectRaw('i.name as item_name')
            ->selectRaw('i.unit as unit')
            ->selectRaw('COALESCE(SUM(d.qty_sold),0) as qty')
            ->selectRaw('COALESCE(SUM(d.value_sold),0) as value')
            ->get();

        // Bentuk struktur:
        // $accordion[date] => [
        //   'qty' => total, 'value' => total,
        //   'categories' => [
        //     category_id_key => [
        //        'code','name','qty','value','items'=>[]
        //     ]
        //   ]
        // ]
        $accordion = [];
        foreach ($dailyCatItems as $row) {
            $date = (string) $row->date;
            $catKey = (string) ($row->category_id ?? '0'); // 0 = tanpa kategori

            if (!isset($accordion[$date])) {
                $accordion[$date] = [
                    'qty' => 0,
                    'value' => 0,
                    'categories' => [],
                ];
            }

            $accordion[$date]['qty'] += (float) $row->qty;
            $accordion[$date]['value'] += (float) $row->value;

            if (!isset($accordion[$date]['categories'][$catKey])) {
                $accordion[$date]['categories'][$catKey] = [
                    'code' => $row->category_code,
                    'name' => $row->category_name,
                    'qty' => 0,
                    'value' => 0,
                    'items' => [],
                ];
            }

            $accordion[$date]['categories'][$catKey]['qty'] += (float) $row->qty;
            $accordion[$date]['categories'][$catKey]['value'] += (float) $row->value;

            $accordion[$date]['categories'][$catKey]['items'][] = [
                'code' => $row->item_code,
                'name' => $row->item_name,
                'unit' => $row->unit,
                'qty' => (float) $row->qty,
                'value' => (float) $row->value,
            ];
        }

        // Sort tanggal desc biar yang terbaru di atas (lebih enak report)
        $accordion = collect($accordion)->sortKeysDesc()->all();

        return view('sales.reports.sales_report.index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'kpi' => $kpi,
            'daily' => $daily,
            'accordion' => $accordion,

            'topCategories' => $topCategories,
            'topProducts' => $topProducts,
            'topSales' => $topSales,
            'ads' => $ads,
            'daysCount' => $daysCount,
        ]);
    }
}
