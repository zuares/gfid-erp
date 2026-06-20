<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialBalanceController extends Controller
{
    private const EXCLUDED = ['opening_balance_void', 'opening_balance_batch_void'];

    public function index(Request $request)
    {
        $asOf = $request->filled('as_of')
            ? Carbon::parse($request->date('as_of'))->toDateString()
            : now()->toDateString();

        $accounts = Account::where('is_active', true)->orderBy('code')->get();

        $balances = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->whereNull('j.voided_at')
            ->whereNotIn('j.source_type', self::EXCLUDED)
            ->whereDate('j.date', '<=', $asOf)
            ->groupBy('jl.account_id')
            ->selectRaw('jl.account_id, COALESCE(SUM(jl.debit),0) as total_debit, COALESCE(SUM(jl.credit),0) as total_credit')
            ->get()
            ->keyBy('account_id');

        $rows = $accounts->map(function ($acc) use ($balances) {
            $b = $balances[$acc->id] ?? null;
            $debit  = $b ? (float) $b->total_debit  : 0.0;
            $credit = $b ? (float) $b->total_credit : 0.0;
            $net    = round($debit - $credit, 2);

            return (object) [
                'id'     => $acc->id,
                'code'   => $acc->code,
                'name'   => $acc->name,
                'type'   => $acc->type,
                'debit'  => $debit,
                'credit' => $credit,
                // Normal balance convention
                'balance_debit'  => $net > 0 ? $net : 0.0,
                'balance_credit' => $net < 0 ? abs($net) : 0.0,
            ];
        })->filter(fn($r) => $r->debit > 0 || $r->credit > 0);

        $totalDebit        = $rows->sum('debit');
        $totalCredit       = $rows->sum('credit');
        $totalBalanceDebit  = $rows->sum('balance_debit');
        $totalBalanceCredit = $rows->sum('balance_credit');

        return view('accounting.trial_balance.index', compact(
            'asOf', 'rows', 'totalDebit', 'totalCredit', 'totalBalanceDebit', 'totalBalanceCredit'
        ));
    }
}
