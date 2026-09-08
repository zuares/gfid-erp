<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashExpense;
use App\Models\CashReceipt;
use App\Models\MarketplacePayout;
use App\Models\PurchasePayment;
use App\Services\Accounting\JournalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            : now()->toDateString();

        $to = $request->filled('to')
            ? Carbon::parse($request->date('to'))->toDateString()
            : now()->toDateString();

        $cashAccounts = Account::query()
            ->where('is_cash', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $cashAccountIds = $cashAccounts->pluck('id')->all();
        $cashOpeningBalances = empty($cashAccountIds)
            ? collect()
            : DB::table('journal_lines as jl')
                ->join('journals as j', 'j.id', '=', 'jl.journal_id')
                ->whereIn('jl.account_id', $cashAccountIds)
                ->whereNull('j.voided_at')
                ->whereNotIn('j.source_type', self::EXCLUDED_BALANCE_SOURCES)
                ->whereDate('j.date', '<', $from)
                ->groupBy('jl.account_id')
                ->selectRaw('jl.account_id, COALESCE(SUM(jl.debit - jl.credit), 0) as balance')
                ->pluck('balance', 'account_id');

        $cashEndingBalances = empty($cashAccountIds)
            ? collect()
            : DB::table('journal_lines as jl')
                ->join('journals as j', 'j.id', '=', 'jl.journal_id')
                ->whereIn('jl.account_id', $cashAccountIds)
                ->whereNull('j.voided_at')
                ->whereNotIn('j.source_type', self::EXCLUDED_BALANCE_SOURCES)
                ->whereDate('j.date', '<=', $to)
                ->groupBy('jl.account_id')
                ->selectRaw('jl.account_id, COALESCE(SUM(jl.debit - jl.credit), 0) as balance')
                ->pluck('balance', 'account_id');

        $cashAccounts->each(function ($account) use ($cashOpeningBalances, $cashEndingBalances) {
            $account->opening_balance = (float) ($cashOpeningBalances[$account->id] ?? 0);
            $account->balance = (float) ($cashEndingBalances[$account->id] ?? 0);
        });

        $postedExpenseBase = CashExpense::query()
            ->with(['expenseAccount', 'cashAccount'])
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        $postedExpenseTotal = (float) (clone $postedExpenseBase)->sum('amount');
        $postedExpenseCount = (int) (clone $postedExpenseBase)->count();

        $postedPurchasePaymentBase = PurchasePayment::query()
            ->with(['purchaseOrder.supplier', 'purchaseOrder.lines.expenseAccount', 'cashAccount', 'paymentMethod'])
            ->whereNull('purchase_payments.voided_at')
            ->whereIn('purchase_payments.type', ['dp', 'payment'])
            ->whereNotNull('purchase_payments.cash_account_id')
            ->whereDate('purchase_payments.date', '>=', $from)
            ->whereDate('purchase_payments.date', '<=', $to)
            ->whereHas('journal', fn ($q) => $q
                ->whereNull('voided_at')
                ->where('source_type', 'purchase_payment'));

        $postedPurchasePayments = (clone $postedPurchasePaymentBase)->get();
        $postedPurchasePaymentTotal = (float) $postedPurchasePayments->sum(fn ($payment) => (float) $payment->amount);
        $purchaseCashOutRows = $this->buildPurchaseCashOutRows($postedPurchasePayments);

        // Payroll harian dibayar melalui jurnal payroll, bukan cash_expenses.
        // Ambil tanggal jurnal payment agar laporan tetap murni basis kas: yang
        // masuk hanya payroll yang benar-benar sudah dibayar dan posted.
        $postedDailyPayrollBase = DB::table('journals as j')
            ->join('piecework_payroll_periods as p', function ($join) {
                $join->on('p.id', '=', 'j.source_id')
                    ->where('j.source_type', '=', 'piecework_payroll_period_payment');
            })
            ->where('p.module', 'daily')
            ->where('p.status', 'final')
            ->whereNull('j.voided_at')
            ->whereDate('j.date', '>=', $from)
            ->whereDate('j.date', '<=', $to)
            ->select([
                'p.id as payroll_period_id',
                'p.period_start',
                'p.period_end',
                'j.id as payment_journal_id',
                'j.date as payment_date',
                'j.description',
            ])
            ->selectRaw('COALESCE((SELECT SUM(jl.credit) FROM journal_lines jl JOIN accounts a ON a.id = jl.account_id WHERE jl.journal_id = j.id AND jl.credit > 0 AND a.is_cash = 1), 0) as total_amount')
            ->selectRaw('COALESCE(p.paid_from_account_id, (SELECT jl.account_id FROM journal_lines jl JOIN accounts a ON a.id = jl.account_id WHERE jl.journal_id = j.id AND jl.credit > 0 AND a.is_cash = 1 LIMIT 1)) as paid_from_account_id');

        $postedDailyPayrolls = (clone $postedDailyPayrollBase)->get();
        $postedDailyPayrollTotal = (float) $postedDailyPayrolls->sum(fn ($row) => (float) $row->total_amount);
        $postedDailyPayrollCount = $postedDailyPayrolls->count();
        $dailyPayrollAccount = Account::query()
            ->where('code', JournalService::CODE_EXP_DAILY_PAYROLL)
            ->first();

        $postedReceiptBase = CashReceipt::query()
            ->with(['sourceAccount', 'cashAccount'])
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        $postedReceiptTotal = (float) (clone $postedReceiptBase)->sum('amount');
        $postedReceiptCount = (int) (clone $postedReceiptBase)->count();

        $expenseByCategory = (clone $postedExpenseBase)
            ->withoutEagerLoads()
            ->join('accounts as a', 'a.id', '=', 'cash_expenses.expense_account_id')
            ->groupBy('a.id', 'a.code', 'a.name')
            ->orderBy('a.code')
            ->selectRaw('a.id, a.code, a.name, COUNT(*) as total_docs, COALESCE(SUM(cash_expenses.amount), 0) as total_amount')
            ->get();

        $receiptBySource = (clone $postedReceiptBase)
            ->withoutEagerLoads()
            ->join('accounts as a', 'a.id', '=', 'cash_receipts.source_account_id')
            ->groupBy('a.id', 'a.code', 'a.name')
            ->orderBy('a.code')
            ->selectRaw('a.id, a.code, a.name, COUNT(*) as total_docs, COALESCE(SUM(cash_receipts.amount), 0) as total_amount')
            ->get();

        // Marketplace Payouts
        $postedPayoutBase = MarketplacePayout::query()
            ->with(['bankAccount'])
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        $postedPayoutTotal = (float) (clone $postedPayoutBase)->sum('amount');
        $postedPayoutCount = (int) (clone $postedPayoutBase)->count();

        // Grouped by marketplace_name for breakdown panel
        $payoutByMarketplace = (clone $postedPayoutBase)
            ->withoutEagerLoads()
            ->groupBy('marketplace_name')
            ->orderByDesc(DB::raw('SUM(amount)'))
            ->selectRaw('marketplace_name, COUNT(*) as total_docs, COALESCE(SUM(amount), 0) as total_amount')
            ->get();

        $recentExpenses = CashExpense::query()
            ->with(['expenseAccount', 'cashAccount'])
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $recentPurchasePayments = $postedPurchasePayments
            ->sortByDesc(fn ($payment) => $payment->date?->format('Y-m-d') . str_pad($payment->id, 8, '0', STR_PAD_LEFT))
            ->take(8)
            ->values();

        // Merge CashReceipts + MarketplacePayouts, sorted by date desc, limit 10
        $recentCashReceipts = CashReceipt::query()
            ->with(['sourceAccount', 'cashAccount'])
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($r) => (object) [
                '_type'       => 'cash_receipt',
                '_route'      => route('accounting.cash-receipts.show', $r),
                'date'        => $r->date,
                'id'          => $r->id,
                'description' => $r->description,
                'reference'   => $r->reference,
                'source_name' => $r->sourceAccount?->name ?? '-',
                'bank_name'   => $r->cashAccount?->name ?? '-',
                'status'      => $r->status,
                'amount'      => $r->amount,
            ]);

        $recentPayouts = MarketplacePayout::query()
            ->with(['bankAccount'])
            ->where('status', 'posted')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn($p) => (object) [
                '_type'       => 'marketplace_payout',
                '_route'      => route('accounting.marketplace-payouts.show', $p),
                'date'        => $p->date,
                'id'          => $p->id,
                'description' => $p->description ?: $p->marketplace_name,
                'reference'   => $p->reference,
                'source_name' => '🛒 ' . $p->marketplace_name,
                'bank_name'   => $p->bankAccount?->name ?? '-',
                'status'      => $p->status,
                'amount'      => $p->amount,
            ]);

        $recentReceipts = $recentCashReceipts
            ->concat($recentPayouts)
            ->sortByDesc(fn($r) => $r->date . str_pad($r->id, 8, '0', STR_PAD_LEFT))
            ->take(10)
            ->values();

        $cashInRows = $receiptBySource
            ->map(fn ($row) => (object) [
                'name' => $row->name,
                'code' => $row->code,
                'total_docs' => $row->total_docs,
                'total_amount' => $row->total_amount,
            ])
            ->concat($payoutByMarketplace->map(fn ($row) => (object) [
                'name' => '🛒 ' . $row->marketplace_name,
                'code' => 'Marketplace',
                'total_docs' => $row->total_docs,
                'total_amount' => $row->total_amount,
            ]))
            ->sort(fn ($left, $right) => $this->compareAccountCodes($left->code, $right->code))
            ->values();

        $cashOutRows = $expenseByCategory
            ->map(fn ($row) => (object) [
                'name' => $row->name,
                'code' => $row->code,
                'total_docs' => $row->total_docs,
                'total_amount' => $row->total_amount,
            ])
            ->concat($purchaseCashOutRows)
            ->values();

        if ($postedDailyPayrollTotal > 0) {
            $dailyPayrollCode = JournalService::CODE_EXP_DAILY_PAYROLL;
            $dailyPayrollName = $dailyPayrollAccount?->name ?? 'Biaya Gaji Operasional';
            $existingDailyPayrollRow = $cashOutRows->search(fn ($row) => (string) $row->code === $dailyPayrollCode);

            if ($existingDailyPayrollRow !== false) {
                $cashOutRows[$existingDailyPayrollRow]->total_docs += $postedDailyPayrollCount;
                $cashOutRows[$existingDailyPayrollRow]->total_amount += $postedDailyPayrollTotal;
            } else {
                $cashOutRows->push((object) [
                    'name' => $dailyPayrollName,
                    'code' => $dailyPayrollCode,
                    'total_docs' => $postedDailyPayrollCount,
                    'total_amount' => $postedDailyPayrollTotal,
                ]);
            }
        }

        $cashOutRows = $cashOutRows
            ->groupBy('code')
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return (object) [
                    'name' => $first->name,
                    'code' => $first->code,
                    'total_docs' => (int) $rows->sum('total_docs'),
                    'total_amount' => (float) $rows->sum('total_amount'),
                ];
            })
            ->sort(fn ($left, $right) => $this->compareAccountCodes($left->code, $right->code))
            ->values();

        $dailyPayrollCashAccounts = $postedDailyPayrolls->pluck('paid_from_account_id')->filter()->unique();
        $dailyPayrollCashAccountNames = $dailyPayrollCashAccounts->isEmpty()
            ? collect()
            : Account::query()->whereIn('id', $dailyPayrollCashAccounts)->pluck('name', 'id');

        $recentDailyPayrolls = $postedDailyPayrolls->map(fn ($payroll) => (object) [
            '_route' => route('payroll.daily.show', ['period' => $payroll->payroll_period_id]),
            'date' => Carbon::parse($payroll->payment_date),
            'id' => $payroll->payment_journal_id,
            'direction' => 'out',
            'description' => $payroll->description ?: 'Payroll Harian (PAY)',
            'category' => $dailyPayrollAccount?->name ?? 'Biaya Gaji Operasional',
            'cash_account' => $dailyPayrollCashAccountNames[$payroll->paid_from_account_id] ?? '-',
            'amount' => $payroll->total_amount,
        ]);

        $recentCashTransactions = collect()
            ->concat($recentExpenses->map(fn ($expense) => (object) [
                '_route' => route('accounting.cash-expenses.show', $expense),
                'date' => $expense->date,
                'id' => $expense->id,
                'direction' => 'out',
                'description' => $expense->description ?: 'Pengeluaran operasional',
                'category' => $expense->expenseAccount?->name ?? '-',
                'cash_account' => $expense->cashAccount?->name ?? '-',
                'amount' => $expense->amount,
            ]))
            ->concat($recentReceipts->map(fn ($receipt) => (object) [
                '_route' => $receipt->_route,
                'date' => $receipt->date,
                'id' => $receipt->id,
                'direction' => 'in',
                'description' => $receipt->description ?: 'Penerimaan kas',
                'category' => $receipt->source_name,
                'cash_account' => $receipt->bank_name,
                'amount' => $receipt->amount,
            ]))
            ->concat($recentPurchasePayments->map(fn ($payment) => (object) [
                '_route' => route('purchasing.purchase_orders.show', $payment->purchaseOrder),
                'date' => $payment->date,
                'id' => $payment->id,
                'direction' => 'out',
                'description' => $payment->purchaseOrder?->code ?? 'Pembayaran PO',
                'category' => $payment->purchaseOrder?->supplier?->name ?? 'Pembelian',
                'cash_account' => $payment->cashAccount?->name ?? '-',
                'amount' => $payment->amount,
            ]))
            ->concat($recentDailyPayrolls)
            ->sortByDesc(fn ($transaction) => optional($transaction->date)->format('Y-m-d') . str_pad($transaction->id, 8, '0', STR_PAD_LEFT))
            ->take(20)
            ->values();

        $cashInTotal = $postedReceiptTotal + $postedPayoutTotal;
        $cashOutTotal = $postedExpenseTotal + $postedPurchasePaymentTotal + $postedDailyPayrollTotal;

        return view('accounting.cash_basis_report.index', [
            'from'                => $from,
            'to'                  => $to,
            'cashAccounts'        => $cashAccounts,
            'openingCashTotal' => (float) $cashAccounts->sum('opening_balance'),
            'cashTotal' => (float) $cashAccounts->sum('balance'),
            'cashInTotal' => $cashInTotal,
            'cashOutTotal' => $cashOutTotal,
            'cashNetFlow' => $cashInTotal - $cashOutTotal,
            'cashInRows' => $cashInRows,
            'cashOutRows' => $cashOutRows,
            'recentCashTransactions' => $recentCashTransactions,
        ]);
    }

    /**
     * Split pembayaran PO menjadi akun biaya untuk line expense, Pembelian
     * Persediaan untuk line hpp, dan Uang Muka Pembelian untuk DP. PO
     * campuran dibagi proporsional berdasarkan nilai line, sehingga total
     * tetap sama dengan nominal kas yang benar-benar dibayar.
     */
    private function buildPurchaseCashOutRows(Collection $payments): Collection
    {
        $expenseRows = [];
        $inventoryAmount = 0.0;
        $inventoryPaymentIds = [];
        $advanceAmount = 0.0;
        $advancePaymentIds = [];

        foreach ($payments as $payment) {
            $amount = round((float) $payment->amount, 2);
            if ($amount <= 0) {
                continue;
            }

            if ($payment->type === 'dp') {
                $advanceAmount += $amount;
                $advancePaymentIds[$payment->id] = true;
                continue;
            }

            $expenseLines = $payment->purchaseOrder?->lines
                ?->filter(fn ($line) => $line->allocation === 'expense'
                    && (int) $line->expense_account_id > 0
                    && $line->expenseAccount)
                ->values() ?? collect();

            if ($expenseLines->isEmpty()) {
                $inventoryAmount += $amount;
                $inventoryPaymentIds[$payment->id] = true;
                continue;
            }

            $expenseTotal = (float) $expenseLines->sum(fn ($line) => max(0, (float) $line->line_total));
            $allLineTotal = (float) ($payment->purchaseOrder?->lines?->sum(fn ($line) => max(0, (float) $line->line_total)) ?? 0);
            $allocationBase = $allLineTotal > 0 ? $allLineTotal : $expenseTotal;

            if ($expenseTotal <= 0 || $allocationBase <= 0) {
                $inventoryAmount += $amount;
                $inventoryPaymentIds[$payment->id] = true;
                continue;
            }

            $expensePaymentAmount = round($amount * min(1, $expenseTotal / $allocationBase), 2);
            $remainingExpenseAmount = $expensePaymentAmount;
            $expenseLinesByAccount = $expenseLines->groupBy('expense_account_id');
            $accountGroups = $expenseLinesByAccount->values();

            foreach ($accountGroups as $index => $lines) {
                $lineTotal = (float) $lines->sum(fn ($line) => max(0, (float) $line->line_total));
                $isLast = $index === $accountGroups->count() - 1;
                $groupAmount = $isLast
                    ? $remainingExpenseAmount
                    : round($expensePaymentAmount * ($lineTotal / $expenseTotal), 2);
                $remainingExpenseAmount = round($remainingExpenseAmount - $groupAmount, 2);

                $line = $lines->first();
                $accountId = (int) $line->expense_account_id;
                $key = (string) $accountId;
                $expenseRows[$key] ??= [
                    'name' => $line->expenseAccount->name,
                    'code' => $line->expenseAccount->code,
                    'total_docs' => [],
                    'total_amount' => 0.0,
                ];
                $expenseRows[$key]['total_docs'][$payment->id] = true;
                $expenseRows[$key]['total_amount'] += $groupAmount;
            }

            $inventoryRemainder = round($amount - $expensePaymentAmount, 2);
            if ($inventoryRemainder > 0) {
                $inventoryAmount += $inventoryRemainder;
                $inventoryPaymentIds[$payment->id] = true;
            }
        }

        $rows = collect($expenseRows)->map(fn ($row) => (object) [
            'name' => $row['name'],
            'code' => $row['code'],
            'total_docs' => count($row['total_docs']),
            'total_amount' => round($row['total_amount'], 2),
        ]);

        if ($inventoryAmount > 0) {
            $rows->push((object) [
                'name' => 'Pembelian Persediaan',
                'code' => 'Pembelian Persediaan',
                'total_docs' => count($inventoryPaymentIds),
                'total_amount' => round($inventoryAmount, 2),
            ]);
        }

        if ($advanceAmount > 0) {
            $rows->push((object) [
                'name' => 'Uang Muka Pembelian',
                'code' => 'Uang Muka Pembelian',
                'total_docs' => count($advancePaymentIds),
                'total_amount' => round($advanceAmount, 2),
            ]);
        }

        return $rows->sort(fn ($left, $right) => $this->compareAccountCodes($left->code, $right->code))->values();
    }

    private function compareAccountCodes(string $left, string $right): int
    {
        $leftIsNumeric = preg_match('/^\d/', $left) === 1;
        $rightIsNumeric = preg_match('/^\d/', $right) === 1;

        if ($leftIsNumeric !== $rightIsNumeric) {
            return $leftIsNumeric ? -1 : 1;
        }

        return strnatcasecmp($left, $right);
    }
}
