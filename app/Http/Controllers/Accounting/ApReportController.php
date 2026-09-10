<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\Accounting\ApOutstandingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ApReportController extends Controller
{
    public function index(Request $request)
    {
        $asOf = $request->filled('as_of')
            ? Carbon::parse($request->date('as_of'))->toDateString()
            : now()->toDateString();

        $supplierId = $request->filled('supplier_id') ? (int) $request->supplier_id : null;

        $rows = app(ApOutstandingService::class)->rowsAsOf($asOf, $supplierId);

        // Group by supplier
        $bySupplier = $rows->groupBy('supplier_id')->map(function ($poRows) {
            $first = $poRows->first();

            return (object) [
                'supplier_id' => $first->supplier_id,
                'supplier_name' => $first->supplier_name,
                'supplier_code' => $first->supplier_code,
                'pos' => $poRows,
                'total' => $poRows->sum('outstanding'),
                'bucket_0_30' => $poRows->where('days_outstanding', '<=', 30)->sum('outstanding'),
                'bucket_31_60' => $poRows->whereBetween('days_outstanding', [31, 60])->sum('outstanding'),
                'bucket_61_90' => $poRows->whereBetween('days_outstanding', [61, 90])->sum('outstanding'),
                'bucket_90plus' => $poRows->where('days_outstanding', '>', 90)->sum('outstanding'),
            ];
        })->values();

        $grandTotal = $bySupplier->sum('total');
        $grand0_30 = $bySupplier->sum('bucket_0_30');
        $grand31_60 = $bySupplier->sum('bucket_31_60');
        $grand61_90 = $bySupplier->sum('bucket_61_90');
        $grand90plus = $bySupplier->sum('bucket_90plus');

        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return view('accounting.ap_report.index', compact(
            'asOf', 'supplierId', 'bySupplier',
            'grandTotal', 'grand0_30', 'grand31_60', 'grand61_90', 'grand90plus',
            'suppliers'
        ));
    }
}
