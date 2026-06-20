<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitLossController extends Controller
{
    private const EXCLUDED = ['opening_balance_void', 'opening_balance_batch_void'];

    public function index(Request $request)
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->date('from'))->toDateString()
            : now()->startOfMonth()->toDateString();

        $to = $request->filled('to')
            ? Carbon::parse($request->date('to'))->toDateString()
            : now()->toDateString();

        // Fetch all account balances in range
        $balances = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereNull('j.voided_at')
            ->whereNotIn('j.source_type', self::EXCLUDED)
            ->whereDate('j.date', '>=', $from)
            ->whereDate('j.date', '<=', $to)
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->selectRaw('a.id, a.code, a.name, a.type,
                COALESCE(SUM(jl.debit),0) as total_debit,
                COALESCE(SUM(jl.credit),0) as total_credit')
            ->get();

        $map = $balances->keyBy('code');

        $get = function (string $code) use ($map): float {
            $r = $map[$code] ?? null;
            return $r ? round((float)$r->total_credit - (float)$r->total_debit, 2) : 0.0;
        };

        $getDebitNet = function (string $code) use ($map): float {
            $r = $map[$code] ?? null;
            return $r ? round((float)$r->total_debit - (float)$r->total_credit, 2) : 0.0;
        };

        // Revenue (credit-normal)
        $penjualan       = $get('4101');
        $returPenjualan  = $get('4201'); // biasanya negatif (Dr 4201)
        $totalRevenue    = round($penjualan + $returPenjualan, 2);

        // COGS (debit-normal)
        $hpp             = $getDebitNet('5101');
        $totalCogs       = $hpp;

        $grossProfit     = round($totalRevenue - $totalCogs, 2);

        // Expenses (debit-normal) — semua 6xxx
        $expenseRows = $balances->filter(fn($r) => str_starts_with($r->code, '6'))->map(fn($r) => (object)[
            'code'   => $r->code,
            'name'   => $r->name,
            'amount' => round((float)$r->total_debit - (float)$r->total_credit, 2),
        ])->sortBy('code')->values();

        $totalExpenses   = $expenseRows->sum('amount');
        $netProfit       = round($grossProfit - $totalExpenses, 2);

        // Revenue detail rows
        $revenueRows = $balances->filter(fn($r) => str_starts_with($r->code, '4'))->map(fn($r) => (object)[
            'code'   => $r->code,
            'name'   => $r->name,
            // revenue = credit - debit
            'amount' => round((float)$r->total_credit - (float)$r->total_debit, 2),
        ])->sortBy('code')->values();

        $cogsRows = $balances->filter(fn($r) => str_starts_with($r->code, '5'))->map(fn($r) => (object)[
            'code'   => $r->code,
            'name'   => $r->name,
            'amount' => round((float)$r->total_debit - (float)$r->total_credit, 2),
        ])->sortBy('code')->values();

        return view('accounting.profit_loss.index', compact(
            'from', 'to',
            'revenueRows', 'totalRevenue',
            'cogsRows', 'totalCogs',
            'grossProfit',
            'expenseRows', 'totalExpenses',
            'netProfit'
        ));
    }
}
