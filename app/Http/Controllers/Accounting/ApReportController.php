<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApReportController extends Controller
{
    public function index(Request $request)
    {
        $asOf = $request->filled('as_of')
            ? Carbon::parse($request->date('as_of'))->toDateString()
            : now()->toDateString();

        $supplierId = $request->filled('supplier_id') ? (int) $request->supplier_id : null;

        // Jika supplier sudah memiliki saldo awal, transaksi GRN sebelum atau
        // pada tanggal saldo awal dianggap sudah tercakup dalam saldo tersebut.
        // Ini mencegah historical GRN terhitung dua kali.
        $openingCutoffs = DB::table('supplier_ap_opening_balances')
            ->where('status', 'posted')
            ->whereNull('voided_at')
            ->whereDate('date', '<=', $asOf)
            ->groupBy('supplier_id')
            ->selectRaw('supplier_id, MIN(date) as opening_date');

        // Per-PO outstanding: basis = GRN posted - payment (type=payment,dp_apply)
        $operationalQuery = DB::table('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->joinSub(
                DB::table('purchase_receipts as pr')
                    ->leftJoinSub($openingCutoffs, 'ob_cutoff', 'ob_cutoff.supplier_id', '=', 'pr.supplier_id')
                    ->where('pr.status', 'posted')
                    ->whereDate('pr.updated_at', '<=', $asOf)
                    ->where(function ($query) {
                        $query->whereNull('ob_cutoff.opening_date')
                            ->orWhereColumn('pr.date', '>', 'ob_cutoff.opening_date');
                    })
                    ->groupBy('pr.purchase_order_id')
                    ->selectRaw('pr.purchase_order_id, SUM(pr.grand_total) as grn_total, MIN(pr.date) as oldest_grn_date'),
                'grn', 'grn.purchase_order_id', '=', 'po.id'
            )
            ->leftJoinSub(
                DB::table('purchase_payments')
                    ->whereNull('voided_at')
                    ->whereIn('type', ['payment', 'dp_apply'])
                    ->whereDate('date', '<=', $asOf)
                    ->groupBy('purchase_order_id')
                    ->selectRaw('purchase_order_id, SUM(amount) as paid_total'),
                'pay', 'pay.purchase_order_id', '=', 'po.id'
            )
            ->selectRaw("
                s.id as supplier_id,
                s.name as supplier_name,
                s.code as supplier_code,
                po.id as po_id,
                po.code as po_code,
                po.date as po_date,
                grn.oldest_grn_date,
                grn.grn_total,
                COALESCE(pay.paid_total, 0) as paid_total,
                ROUND(grn.grn_total - COALESCE(pay.paid_total, 0), 2) as outstanding,
                CAST(julianday(?) - julianday(grn.oldest_grn_date) AS INTEGER) as days_outstanding,
                0 as is_opening,
                NULL as reference_no
            ", [$asOf]);

        if ($supplierId) {
            $operationalQuery->where('po.supplier_id', $supplierId);
        }

        $operationalRows = $operationalQuery
            ->whereRaw('ROUND(grn.grn_total - COALESCE(pay.paid_total, 0), 2) > 0.01')
            ->orderBy('s.name')->orderBy('grn.oldest_grn_date')->get();

        // Saldo awal AP adalah subledger per supplier. Jurnal opening balance
        // umum tidak punya supplier_id, sehingga tidak bisa dipakai langsung
        // untuk laporan AP yang membutuhkan aging per supplier.
        $openingQuery = DB::table('supplier_ap_opening_balances as ob')
            ->join('suppliers as s', 's.id', '=', 'ob.supplier_id')
            ->where('ob.status', 'posted')
            ->whereNull('ob.voided_at')
            ->whereDate('ob.date', '<=', $asOf)
            ->selectRaw("
                s.id as supplier_id,
                s.name as supplier_name,
                s.code as supplier_code,
                NULL as po_id,
                NULL as po_code,
                ob.date as po_date,
                COALESCE(ob.invoice_date, ob.date) as oldest_grn_date,
                ob.amount as grn_total,
                0 as paid_total,
                ROUND(ob.amount, 2) as outstanding,
                CAST(julianday(?) - julianday(COALESCE(ob.invoice_date, ob.date)) AS INTEGER) as days_outstanding,
                1 as is_opening,
                ob.reference_no as reference_no
            ", [$asOf]);

        if ($supplierId) {
            $openingQuery->where('ob.supplier_id', $supplierId);
        }

        $rows = $operationalRows
            ->concat($openingQuery->get())
            ->filter(fn ($row) => (float) $row->outstanding > 0.01)
            ->sortBy(fn ($row) => strtolower((string) $row->supplier_name) . '|' . $row->oldest_grn_date . '|' . $row->is_opening)
            ->values();

        // Group by supplier
        $bySupplier = $rows->groupBy('supplier_id')->map(function ($poRows) {
            $first = $poRows->first();
            return (object) [
                'supplier_id'   => $first->supplier_id,
                'supplier_name' => $first->supplier_name,
                'supplier_code' => $first->supplier_code,
                'pos'           => $poRows,
                'total'         => $poRows->sum('outstanding'),
                'bucket_0_30'   => $poRows->where('days_outstanding', '<=', 30)->sum('outstanding'),
                'bucket_31_60'  => $poRows->whereBetween('days_outstanding', [31, 60])->sum('outstanding'),
                'bucket_61_90'  => $poRows->whereBetween('days_outstanding', [61, 90])->sum('outstanding'),
                'bucket_90plus' => $poRows->where('days_outstanding', '>', 90)->sum('outstanding'),
            ];
        })->values();

        $grandTotal    = $bySupplier->sum('total');
        $grand0_30     = $bySupplier->sum('bucket_0_30');
        $grand31_60    = $bySupplier->sum('bucket_31_60');
        $grand61_90    = $bySupplier->sum('bucket_61_90');
        $grand90plus   = $bySupplier->sum('bucket_90plus');

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('accounting.ap_report.index', compact(
            'asOf', 'supplierId', 'bySupplier',
            'grandTotal', 'grand0_30', 'grand31_60', 'grand61_90', 'grand90plus',
            'suppliers'
        ));
    }
}
