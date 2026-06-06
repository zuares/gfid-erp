<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashExpense;
use App\Models\CashReceipt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashBasisReportController extends Controller
{
    private const EXCLUDED_BALANCE_SOURCES = [
        'opening_balance_void',
        'opening_balance_batch_void',
    ];

    public function index(Request $request)
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->date('from'))->toDateString()
            : now()->startOfMonth()->toDateString();

        $to = $request->filled('to')
            ? Carbon::parse($request->date('to'))->toDateString()
            : now()->toDateString();

        $cashAccounts = Account::query()
            ->where('is_cash', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $cashAccountIds = $cashAccounts->pluck('id')->all();
        $cashBalances = empty($cashAccountIds)
            ? collect()
            : DB::table('journal_lines as jl')
                ->join('journals as j', 'j.id', '=', 'jl.journal_id')
                ->whereIn('jl.account_id', $cashAccountIds)
                ->whereNull('j.voided_at')
                ->whereNotIn('j.source_type', self::EXCLUDED_BALANCE_SOURCES)
                ->groupBy('jl.account_id')
                ->selectRaw('jl.account_id, COALESCE(SUM(jl.debit - jl.credit), 0) as balance')
                ->pluck('balance', 'account_id');

        $cashAccounts->each(function ($account) use ($cashBalances) {
            $account->balance = (float) ($cashBalances[$account->id] ?? 0);
        });

        $postedExpenseBase = CashExpense::query()
            ->with(['expenseAccount', 'cashAccount'])
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        $postedExpenseTotal = (float) (clone $postedExpenseBase)->sum('amount');
        $postedExpenseCount = (int) (clone $postedExpenseBase)->count();

        $postedReceiptBase = CashReceipt::query()
            ->with(['sourceAccount', 'cashAccount'])
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        $postedReceiptTotal = (float) (clone $postedReceiptBase)->sum('amount');
        $postedReceiptCount = (int) (clone $postedReceiptBase)->count();

        $draftReceiptTotal = (float) CashReceipt::query()
            ->where('status', 'draft')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->sum('amount');

        $draftReceiptCount = (int) CashReceipt::query()
            ->where('status', 'draft')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->count();

        $draftExpenseTotal = (float) CashExpense::query()
            ->where('status', 'draft')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->sum('amount');

        $draftExpenseCount = (int) CashExpense::query()
            ->where('status', 'draft')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->count();

        $voidExpenseCount = (int) CashExpense::query()
            ->where('status', 'void')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->count();

        $expenseByCategory = (clone $postedExpenseBase)
            ->withoutEagerLoads()
            ->join('accounts as a', 'a.id', '=', 'cash_expenses.expense_account_id')
            ->groupBy('a.id', 'a.code', 'a.name')
            ->orderByDesc(DB::raw('SUM(cash_expenses.amount)'))
            ->selectRaw('a.id, a.code, a.name, COUNT(*) as total_docs, COALESCE(SUM(cash_expenses.amount), 0) as total_amount')
            ->get();

        $expenseByCash = (clone $postedExpenseBase)
            ->withoutEagerLoads()
            ->join('accounts as a', 'a.id', '=', 'cash_expenses.cash_account_id')
            ->groupBy('a.id', 'a.code', 'a.name')
            ->orderByDesc(DB::raw('SUM(cash_expenses.amount)'))
            ->selectRaw('a.id, a.code, a.name, COUNT(*) as total_docs, COALESCE(SUM(cash_expenses.amount), 0) as total_amount')
            ->get();

        $receiptBySource = (clone $postedReceiptBase)
            ->withoutEagerLoads()
            ->join('accounts as a', 'a.id', '=', 'cash_receipts.source_account_id')
            ->groupBy('a.id', 'a.code', 'a.name')
            ->orderByDesc(DB::raw('SUM(cash_receipts.amount)'))
            ->selectRaw('a.id, a.code, a.name, COUNT(*) as total_docs, COALESCE(SUM(cash_receipts.amount), 0) as total_amount')
            ->get();

        $recentExpenses = CashExpense::query()
            ->with(['expenseAccount', 'cashAccount'])
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $recentReceipts = CashReceipt::query()
            ->with(['sourceAccount', 'cashAccount'])
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('accounting.cash_basis_report.index', [
            'from' => $from,
            'to' => $to,
            'cashAccounts' => $cashAccounts,
            'cashTotal' => (float) $cashAccounts->sum('balance'),
            'postedReceiptTotal' => $postedReceiptTotal,
            'postedReceiptCount' => $postedReceiptCount,
            'draftReceiptTotal' => $draftReceiptTotal,
            'draftReceiptCount' => $draftReceiptCount,
            'postedExpenseTotal' => $postedExpenseTotal,
            'postedExpenseCount' => $postedExpenseCount,
            'draftExpenseTotal' => $draftExpenseTotal,
            'draftExpenseCount' => $draftExpenseCount,
            'voidExpenseCount' => $voidExpenseCount,
            'expenseByCategory' => $expenseByCategory,
            'expenseByCash' => $expenseByCash,
            'receiptBySource' => $receiptBySource,
            'recentExpenses' => $recentExpenses,
            'recentReceipts' => $recentReceipts,
        ]);
    }
}
