<?php

namespace App\Http\Controllers\StandaloneAccounting;

use App\Http\Controllers\Controller;
use App\Models\StandaloneAccountingAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialBalanceController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'as_of' => ['nullable', 'date'],
        ]);

        $asOf = Carbon::parse($data['as_of'] ?? now()->toDateString())->toDateString();

        $balances = DB::table('standalone_accounting_journal_lines as line')
            ->join('standalone_accounting_journals as journal', 'journal.id', '=', 'line.journal_id')
            ->whereNotNull('journal.posted_at')
            ->whereNull('journal.voided_at')
            ->whereDate('journal.date', '<=', $asOf)
            ->groupBy('line.account_id')
            ->selectRaw('line.account_id, COALESCE(SUM(line.debit), 0) AS total_debit, COALESCE(SUM(line.credit), 0) AS total_credit')
            ->get()
            ->keyBy('account_id');

        $rows = StandaloneAccountingAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->map(function (StandaloneAccountingAccount $account) use ($balances) {
                $balance = $balances->get($account->id);
                $debit = round((float) ($balance->total_debit ?? 0), 2);
                $credit = round((float) ($balance->total_credit ?? 0), 2);
                $net = round($debit - $credit, 2);

                return (object) [
                    'code' => $account->code,
                    'name' => $account->name,
                    'description' => $account->description,
                    'type' => $account->type,
                    'total_debit' => $debit,
                    'total_credit' => $credit,
                    'balance_debit' => $net > 0 ? $net : 0,
                    'balance_credit' => $net < 0 ? abs($net) : 0,
                ];
            });

        return view('standalone_accounting.trial_balance.index', [
            'asOf' => $asOf,
            'rows' => $rows,
            'totalDebit' => $rows->sum('total_debit'),
            'totalCredit' => $rows->sum('total_credit'),
            'totalBalanceDebit' => $rows->sum('balance_debit'),
            'totalBalanceCredit' => $rows->sum('balance_credit'),
        ]);
    }
}
