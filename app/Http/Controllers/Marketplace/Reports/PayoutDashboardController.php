<?php

namespace App\Http\Controllers\Marketplace\Reports;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutDashboardController extends Controller
{
    public function index(Request $request)
    {
        // ----------------------------
        // Filters
        // ----------------------------
        $storeId = (int) $request->get('store_id', 0);
        $channel = trim((string) $request->get('channel', '')); // shopee / tiktok / reseller / etc
        $status = trim((string) $request->get('status', '')); // released / unreleased / all
        $group = trim((string) $request->get('group', 'day')); // day / month

        $from = $request->get('from')
        ? Carbon::parse($request->get('from'))->startOfDay()
        : now()->subDays(30)->startOfDay();

        $to = $request->get('to')
        ? Carbon::parse($request->get('to'))->endOfDay()
        : now()->endOfDay();

        // ----------------------------
        // Base query (SalesInvoice marketplace)
        // ----------------------------
        $q = SalesInvoice::query()
            ->whereNotNull('channel_order_no')
            ->whereNotNull('channel')
            ->whereBetween('paid_at', [$from, $to]);

        if ($storeId > 0) {
            $q->where('store_id', $storeId);
        }

        if ($channel !== '') {
            $q->where('channel', $channel);
        }

        if ($status === 'released') {
            $q->whereNotNull('released_at');
        } elseif ($status === 'unreleased') {
            $q->whereNull('released_at');
        }

        // ----------------------------
        // COGS subquery per invoice (FIXED)
        // prefer hpp_total_snapshot if not null, else hpp_unit_snapshot * qty
        // ----------------------------
        $cogsSub = DB::table('sales_invoice_lines as sil')
            ->selectRaw("
                sil.sales_invoice_id as invoice_id,
                COALESCE(SUM(
                    CASE
                      WHEN sil.hpp_total_snapshot IS NOT NULL THEN COALESCE(sil.hpp_total_snapshot,0)
                      ELSE COALESCE(sil.hpp_unit_snapshot,0) * COALESCE(sil.qty,0)
                    END
                ),0) as cogs_total
            ")
            ->groupBy('sil.sales_invoice_id');

        // ----------------------------
        // Summary (total range)
        // ----------------------------
        $summary = (clone $q)
            ->leftJoinSub($cogsSub, 'cogs', fn($join) => $join->on('cogs.invoice_id', '=', 'sales_invoices.id'))
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(sales_invoices.subtotal),0) as gross_subtotal')
            ->selectRaw('COALESCE(SUM(sales_invoices.platform_fee_total),0) as platform_fee_total')
            ->selectRaw('COALESCE(SUM(sales_invoices.refund_total),0) as refund_total')
            ->selectRaw('COALESCE(SUM(sales_invoices.net_payout_actual),0) as net_payout_actual')
            ->selectRaw('COALESCE(SUM(COALESCE(cogs.cogs_total,0)),0) as cogs_total')
            ->selectRaw('COALESCE(SUM(sales_invoices.subtotal),0) - COALESCE(SUM(COALESCE(cogs.cogs_total,0)),0) as gross_profit')
            ->selectRaw('COALESCE(SUM(sales_invoices.subtotal),0) - COALESCE(SUM(COALESCE(cogs.cogs_total,0)),0) - COALESCE(SUM(sales_invoices.platform_fee_total),0) - COALESCE(SUM(sales_invoices.refund_total),0) as net_profit')
            // unreleased totals
            ->selectRaw('COALESCE(SUM(CASE WHEN sales_invoices.released_at IS NULL THEN sales_invoices.subtotal ELSE 0 END),0) as gross_unreleased')
            ->selectRaw('COALESCE(SUM(CASE WHEN sales_invoices.released_at IS NULL THEN sales_invoices.platform_fee_total ELSE 0 END),0) as fee_unreleased')
            ->selectRaw('COALESCE(SUM(CASE WHEN sales_invoices.released_at IS NULL THEN sales_invoices.refund_total ELSE 0 END),0) as refund_unreleased')
            ->selectRaw('COALESCE(SUM(CASE WHEN sales_invoices.released_at IS NULL THEN sales_invoices.net_payout_actual ELSE 0 END),0) as net_unreleased')
            ->first();

        // ----------------------------
        // Group expr (SQLite)
        // ----------------------------
        $groupExpr = $group === 'month'
        ? DB::raw("strftime('%Y-%m', sales_invoices.paid_at) as grp")
        : DB::raw("date(sales_invoices.paid_at) as grp");

        // ----------------------------
        // Timeline (grouped)
        // Kolom urutan: Gross, COGS, G.Profit, Fee, Refund, N.Profit, Net
        // ----------------------------
        $timeline = (clone $q)
            ->leftJoinSub($cogsSub, 'cogs', fn($join) => $join->on('cogs.invoice_id', '=', 'sales_invoices.id'))
            ->select($groupExpr)
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(sales_invoices.subtotal),0) as gross_subtotal')
            ->selectRaw('COALESCE(SUM(COALESCE(cogs.cogs_total,0)),0) as cogs_total')
            ->selectRaw('COALESCE(SUM(sales_invoices.subtotal),0) - COALESCE(SUM(COALESCE(cogs.cogs_total,0)),0) as gross_profit')
            ->selectRaw('COALESCE(SUM(sales_invoices.platform_fee_total),0) as platform_fee_total')
            ->selectRaw('COALESCE(SUM(sales_invoices.refund_total),0) as refund_total')
            ->selectRaw('COALESCE(SUM(sales_invoices.subtotal),0) - COALESCE(SUM(COALESCE(cogs.cogs_total,0)),0) - COALESCE(SUM(sales_invoices.platform_fee_total),0) - COALESCE(SUM(sales_invoices.refund_total),0) as net_profit')
            ->selectRaw('COALESCE(SUM(sales_invoices.net_payout_actual),0) as net_payout_actual')
            ->groupBy('grp')
            ->orderBy('grp', 'asc')
            ->get();

        // ----------------------------
        // Loss Days (Net Profit < 0)
        // ----------------------------
        $lossDays = collect($timeline)->filter(fn($t) => (float) ($t->net_profit ?? 0) < 0)->values();

        // ----------------------------
        // Detail invoices (latest 200)
        // ----------------------------
        $invoices = (clone $q)
            ->with(['store:id,name'])
            ->orderByDesc('paid_at')
            ->limit(200)
            ->get([
                'id', 'code', 'store_id', 'channel', 'channel_order_no', 'paid_at', 'released_at',
                'subtotal', 'platform_fee_total', 'refund_total', 'net_payout_actual', 'awb', 'marketplace_status',
            ]);

        // ----------------------------
        // Rank per Store
        // ----------------------------
        $rankStores = (clone $q)
            ->leftJoin('stores as st', 'st.id', '=', 'sales_invoices.store_id')
            ->leftJoinSub($cogsSub, 'cogs', fn($join) => $join->on('cogs.invoice_id', '=', 'sales_invoices.id'))
            ->selectRaw('sales_invoices.store_id as store_id')
            ->selectRaw("COALESCE(st.name, 'Store #' || sales_invoices.store_id) as store_name")
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(sales_invoices.subtotal),0) as gross_subtotal')
            ->selectRaw('COALESCE(SUM(COALESCE(cogs.cogs_total,0)),0) as cogs_total')
            ->selectRaw('COALESCE(SUM(sales_invoices.subtotal),0) - COALESCE(SUM(COALESCE(cogs.cogs_total,0)),0) as gross_profit')
            ->selectRaw('COALESCE(SUM(sales_invoices.platform_fee_total),0) as platform_fee_total')
            ->selectRaw('COALESCE(SUM(sales_invoices.refund_total),0) as refund_total')
            ->selectRaw('COALESCE(SUM(sales_invoices.subtotal),0) - COALESCE(SUM(COALESCE(cogs.cogs_total,0)),0) - COALESCE(SUM(sales_invoices.platform_fee_total),0) - COALESCE(SUM(sales_invoices.refund_total),0) as net_profit')
            ->selectRaw('COALESCE(SUM(sales_invoices.net_payout_actual),0) as net_payout_actual')
            ->groupBy('sales_invoices.store_id', 'st.name')
            ->orderByDesc('net_profit')
            ->limit(200)
            ->get();

        // ----------------------------
        // Rank per SKU (proporsional)
        // Urutan: Gross, COGS, G.Profit, Fee, Refund, N.Profit, Net
        // ----------------------------
        $rankSkus = DB::table('sales_invoice_lines as l')
            ->join('sales_invoices as inv', 'inv.id', '=', 'l.sales_invoice_id')
            ->leftJoin('items as it', 'it.id', '=', 'l.item_id')
            ->whereNotNull('inv.channel_order_no')
            ->whereNotNull('inv.channel')
            ->whereBetween('inv.paid_at', [$from, $to])
            ->when($storeId > 0, fn($qq) => $qq->where('inv.store_id', $storeId))
            ->when($channel !== '', fn($qq) => $qq->where('inv.channel', $channel))
            ->when($status === 'released', fn($qq) => $qq->whereNotNull('inv.released_at'))
            ->when($status === 'unreleased', fn($qq) => $qq->whereNull('inv.released_at'))
            ->selectRaw('l.item_id as item_id')
            ->selectRaw("COALESCE(it.code, 'ID#' || l.item_id) as item_code")
            ->selectRaw("COALESCE(it.name, '-') as item_name")
            ->selectRaw('COALESCE(SUM(l.qty),0) as qty_total')
            ->selectRaw('COALESCE(SUM(l.line_total),0) as gross_alloc')
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                      WHEN l.hpp_total_snapshot IS NOT NULL THEN COALESCE(l.hpp_total_snapshot,0)
                      ELSE COALESCE(l.hpp_unit_snapshot,0) * COALESCE(l.qty,0)
                    END
                ),0) as cogs_alloc
            ")
            ->selectRaw("
                COALESCE(SUM(l.line_total),0)
                - COALESCE(SUM(
                    CASE
                      WHEN l.hpp_total_snapshot IS NOT NULL THEN COALESCE(l.hpp_total_snapshot,0)
                      ELSE COALESCE(l.hpp_unit_snapshot,0) * COALESCE(l.qty,0)
                    END
                ),0) as gross_profit
            ")
            ->selectRaw("
                COALESCE(SUM(
                    COALESCE(inv.platform_fee_total,0) * (COALESCE(l.line_total,0) / NULLIF(COALESCE(inv.subtotal,0),0))
                ),0) as fee_alloc
            ")
            ->selectRaw("
                COALESCE(SUM(
                    COALESCE(inv.refund_total,0) * (COALESCE(l.line_total,0) / NULLIF(COALESCE(inv.subtotal,0),0))
                ),0) as refund_alloc
            ")
            ->selectRaw("
                (
                  COALESCE(SUM(l.line_total),0)
                  - COALESCE(SUM(
                      CASE
                        WHEN l.hpp_total_snapshot IS NOT NULL THEN COALESCE(l.hpp_total_snapshot,0)
                        ELSE COALESCE(l.hpp_unit_snapshot,0) * COALESCE(l.qty,0)
                      END
                    ),0)
                )
                - COALESCE(SUM(
                    COALESCE(inv.platform_fee_total,0) * (COALESCE(l.line_total,0) / NULLIF(COALESCE(inv.subtotal,0),0))
                  ),0)
                - COALESCE(SUM(
                    COALESCE(inv.refund_total,0) * (COALESCE(l.line_total,0) / NULLIF(COALESCE(inv.subtotal,0),0))
                  ),0)
                as net_profit
            ")
            ->selectRaw("
                COALESCE(SUM(
                    COALESCE(inv.net_payout_actual,0) * (COALESCE(l.line_total,0) / NULLIF(COALESCE(inv.subtotal,0),0))
                ),0) as net_alloc
            ")
            ->groupBy('l.item_id', 'it.code', 'it.name')
            ->orderByDesc('net_profit')
            ->limit(300)
            ->get();

        // ----------------------------
        // Unreleased per tanggal (paid_at date)
        // ----------------------------
        $unreleasedByDate = (clone $q)
            ->whereNull('released_at')
            ->selectRaw("date(sales_invoices.paid_at) as d")
            ->selectRaw("COUNT(*) as orders")
            ->selectRaw("COALESCE(SUM(sales_invoices.subtotal),0) as gross_unreleased")
            ->selectRaw("COALESCE(SUM(sales_invoices.platform_fee_total),0) as fee_unreleased")
            ->selectRaw("COALESCE(SUM(sales_invoices.refund_total),0) as refund_unreleased")
            ->selectRaw("COALESCE(SUM(sales_invoices.net_payout_actual),0) as net_unreleased")
            ->groupBy('d')
            ->orderBy('d', 'asc')
            ->get();

        // Dropdowns
        $stores = Store::query()->orderBy('name')->get(['id', 'name']);
        $channels = SalesInvoice::query()
            ->whereNotNull('channel')
            ->distinct()
            ->orderBy('channel')
            ->pluck('channel')
            ->values();

        return view('marketplace.reports.payout', [
            'stores' => $stores,
            'channels' => $channels,

            'summary' => $summary,
            'timeline' => $timeline,
            'lossDays' => $lossDays,
            'invoices' => $invoices,

            'rankStores' => $rankStores,
            'rankSkus' => $rankSkus,

            'unreleasedByDate' => $unreleasedByDate,

            'filters' => [
                'store_id' => $storeId,
                'channel' => $channel,
                'status' => $status,
                'group' => $group,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }
}
