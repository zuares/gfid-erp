<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccountingPosting;
use App\Services\Marketplace\MarketplaceAccountingPostingService;
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

        $dateBasis = $request->input('date_basis', 'ordered_at');
        if (! in_array($dateBasis, ['ordered_at', 'settlement_time'], true)) {
            $dateBasis = 'ordered_at';
        }

        // A marketplace financial statement can be posted using either the
        // order date or the settlement date. Both postings use the same GL
        // source type, so including them together double-counts the statement
        // when both versions exist for one period.
        $marketplaceJournalIds = MarketplaceAccountingPosting::query()
            ->where('status', 'posted')
            ->where('date_basis', $dateBasis)
            ->whereNotNull('journal_id')
            ->pluck('journal_id');

        // Fetch all account balances in range
        $balances = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereNull('j.voided_at')
            ->whereNotIn('j.source_type', self::EXCLUDED)
            ->where(function ($query) use ($marketplaceJournalIds) {
                $query
                    ->where('j.source_type', '!=', MarketplaceAccountingPostingService::SOURCE_TYPE)
                    ->orWhereIn('j.id', $marketplaceJournalIds);
            })
            ->whereDate('j.date', '>=', $from)
            ->whereDate('j.date', '<=', $to)
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'j.source_type')
            ->selectRaw('a.id, a.code, a.name, a.type, j.source_type,
                COALESCE(SUM(jl.debit),0) as total_debit,
                COALESCE(SUM(jl.credit),0) as total_credit')
            ->get();

        $accountBalances = $balances->groupBy('code')->map(function ($rows) {
            $row = $rows->first();
            $row->total_debit = $rows->sum(fn($item) => (float) $item->total_debit);
            $row->total_credit = $rows->sum(fn($item) => (float) $item->total_credit);

            return $row;
        });

        // Before account 6115 existed, inventory adjustments were posted to
        // 6101. Keep that legacy stock variance in net profit, but do not
        // present it as operating expense. New stock variance postings use
        // 6115 and are handled the same way below.
        $legacyStockVariance = $balances
            ->filter(fn($r) => $r->code === '6101' && $r->source_type === 'inventory_adjustment')
            ->sum(fn($r) => (float) $r->total_debit - (float) $r->total_credit);
        $currentStockVariance = isset($accountBalances['6115'])
            ? (float) $accountBalances['6115']->total_debit - (float) $accountBalances['6115']->total_credit
            : 0.0;
        $inventoryVariance = round($legacyStockVariance + $currentStockVariance, 2);

        // Revenue (credit-normal). Derive the total from the same rows shown
        // below so additional revenue accounts cannot silently be omitted.
        $revenueRows = $accountBalances->filter(fn($r) => str_starts_with($r->code, '4'))->map(fn($r) => (object)[
            'code'   => $r->code,
            'name'   => $r->name,
            'amount' => round((float)$r->total_credit - (float)$r->total_debit, 2),
        ])->sortBy('code')->values();
        $totalRevenue = round((float) $revenueRows->sum('amount'), 2);

        // COGS (debit-normal)
        $cogsRows = $accountBalances->filter(fn($r) => str_starts_with($r->code, '5'))->map(fn($r) => (object)[
            'code'   => $r->code,
            'name'   => $r->name,
            'amount' => round((float)$r->total_debit - (float)$r->total_credit, 2),
        ])->sortBy('code')->values();
        $totalCogs = round((float) $cogsRows->sum('amount'), 2);

        $grossProfit = round($totalRevenue - $totalCogs, 2);

        // Expenses (debit-normal) — semua 6xxx. Credits remain negative
        // amounts because they are legitimate contra-expense movements.
        $expenseRows = $accountBalances
            ->filter(fn($r) => str_starts_with($r->code, '6') && $r->code !== '6115')
            ->map(function ($r) use ($legacyStockVariance) {
                $amount = (float) $r->total_debit - (float) $r->total_credit;
                if ($r->code === '6101') {
                    $amount -= $legacyStockVariance;
                }

                return (object) [
                    'code' => $r->code,
                    'name' => $r->name,
                    'amount' => round($amount, 2),
                ];
            })
            ->filter(fn($r) => abs($r->amount) >= 0.01)
            ->sortBy('code')
            ->values();
        $totalExpenses = round((float) $expenseRows->sum('amount'), 2);
        $netProfit = round($grossProfit - $totalExpenses - $inventoryVariance, 2);

        return view('accounting.profit_loss.index', compact(
            'from', 'to', 'dateBasis', 'inventoryVariance',
            'revenueRows', 'totalRevenue',
            'cogsRows', 'totalCogs',
            'grossProfit',
            'expenseRows', 'totalExpenses',
            'netProfit'
        ));
    }
}
