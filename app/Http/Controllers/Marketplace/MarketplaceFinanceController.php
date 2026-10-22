<?php

namespace App\Http\Controllers\Marketplace;

use Carbon\CarbonImmutable;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinancialComponent;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceFinanceReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MarketplaceFinanceController extends Controller
{
    public function __construct(
        private readonly MarketplaceFinanceReconciliationService $reconciliation,
    ) {}

    public function index(Request $request)
    {
        [$report, $stores, $filters] = $this->reportData($request);
        $storeNames = $stores->pluck('name', 'id');
        $transactions = collect($report['transactions']);
        $settlements = collect($report['settlements']);

        $grossSales = $transactions->sum(fn (array $row): float => (float) ($row['gross_sales_invoice'] ?? $row['escrow_gross'] ?? 0));
        $totalFees = $transactions->sum(fn (array $row): float => abs((float) ($row['total_components'] ?? 0)));
        $incomeAmounts = $transactions->groupBy('income_status')->map(
            fn (Collection $rows): float => $rows->sum(fn (array $row): float => (float) ($row['actual_net_income'] ?? 0)),
        );

        $overview = [
            'gross_sales' => round($grossSales, 2),
            'total_escrow_fee' => round($totalFees, 2),
            'pending_income' => round((float) ($incomeAmounts['pending'] ?? 0), 2),
            'to_release' => round((float) ($incomeAmounts['to_release'] ?? 0), 2),
            'released' => round((float) ($incomeAmounts['released'] ?? 0), 2),
            'settlement_received' => round($settlements->sum('settlement_received_amount'), 2),
            'unreconciled_amount' => (float) $report['summary']['unreconciled_amount'],
        ];

        return view('marketplace.finance.index', compact('filters', 'overview', 'report', 'storeNames', 'stores'));
    }

    public function transactions(Request $request)
    {
        $filters = $this->filters($request);
        $stores = $this->stores();
        $rows = $this->reconciliation->paginateTransactions($filters, $this->transactionsPerPage($request));
        $storeNames = $stores->pluck('name', 'id');

        return view('marketplace.finance.transactions', [
            'filters' => $filters,
            'rows' => $rows,
            'storeNames' => $storeNames,
            'stores' => $stores,
        ]);
    }

    public function settlements(Request $request)
    {
        [$report, $stores, $filters] = $this->reportData($request);
        $settlementIds = collect($report['settlements'])->pluck('settlement_id');
        $settlementModels = MarketplaceFinanceSettlement::query()
            ->with(['store', 'bankAccount'])
            ->whereIn('id', $settlementIds)
            ->get()
            ->keyBy('id');

        return view('marketplace.finance.settlements', [
            'filters' => $filters,
            'rows' => $report['settlements'],
            'report' => $report,
            'settlementModels' => $settlementModels,
            'stores' => $stores,
        ]);
    }

    public function reconciliation(Request $request)
    {
        [$report, $stores, $filters] = $this->reportData($request);

        return view('marketplace.finance.reconciliation', compact('filters', 'report', 'stores'));
    }

    public function feeAnalysis(Request $request)
    {
        $filters = $this->filters($request);
        $stores = $this->stores();
        $componentQuery = $this->feeComponentQuery($filters);

        $byType = (clone $componentQuery)
            ->select('fc.component_code as code')
            ->selectRaw('COALESCE(MAX(fc.component_name), MAX(fc.component_code)) as label')
            ->selectRaw('COUNT(fc.id) as count')
            ->selectRaw('SUM(ABS(fc.amount)) as amount')
            ->groupBy('fc.component_code')
            ->orderBy('fc.component_code')
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->label ?: $row->code,
                'code' => $row->code,
                'amount' => round((float) $row->amount, 2),
                'count' => (int) $row->count,
            ])->values();

        $byStore = (clone $componentQuery)
            ->leftJoin('stores as fee_store', 'fee_store.id', '=', 'ft.store_id')
            ->select('ft.store_id')
            ->selectRaw("COALESCE(MAX(fee_store.name), 'Toko tidak diketahui') as store_name")
            ->selectRaw('COUNT(fc.id) as count')
            ->selectRaw('SUM(ABS(fc.amount)) as amount')
            ->groupBy('ft.store_id')
            ->orderBy('store_name')
            ->get()
            ->map(fn ($row): array => [
                'store_id' => $row->store_id ?? 'unknown',
                'store_name' => $row->store_name ?: 'Toko tidak diketahui',
                'amount' => round((float) $row->amount, 2),
                'count' => (int) $row->count,
            ])->values();

        $byOrder = (clone $componentQuery)
            ->leftJoin('stores as fee_store', 'fee_store.id', '=', 'ft.store_id')
            ->select('ft.id as transaction_id', 'ft.order_sn', 'ft.gross_amount', 'ft.store_id')
            ->selectRaw('MAX(fee_store.name) as store_name')
            ->selectRaw('SUM(ABS(fc.amount)) as fee_amount')
            ->groupBy('ft.id', 'ft.order_sn', 'ft.gross_amount', 'ft.store_id')
            ->orderByDesc('ft.id')
            ->paginate(50, ['*'], 'fee_page')
            ->withQueryString();

        $byOrder->setCollection($byOrder->getCollection()->map(function ($row): array {
            $gross = (float) $row->gross_amount;
            $fee = round((float) $row->fee_amount, 2);

            return [
                'order_sn' => (string) $row->order_sn,
                'store_name' => $row->store_name ?: 'Toko tidak diketahui',
                'gross_amount' => round($gross, 2),
                'fee_amount' => $fee,
                'fee_percentage' => $gross > 0 ? round(($fee / $gross) * 100, 2) : null,
            ];
        })->values());

        return view('marketplace.finance.fee-analysis', compact('byOrder', 'byStore', 'byType', 'filters', 'stores'));
    }

    private function feeComponentQuery(array $filters)
    {
        $query = MarketplaceFinancialComponent::query()
            ->from('marketplace_financial_components as fc')
            ->join('marketplace_financial_transactions as ft', 'ft.id', '=', 'fc.financial_transaction_id');

        if ($filters['store_id'] !== null) {
            $query->where('ft.store_id', $filters['store_id']);
        }
        if ($filters['order_sn'] !== null) {
            $query->where('ft.order_sn', $filters['order_sn']);
        }
        if ($filters['date_from'] !== null) {
            $query->where('ft.'.$filters['date_basis'], '>=', CarbonImmutable::parse($filters['date_from'])->startOfDay());
        }
        if ($filters['date_to'] !== null) {
            $query->where('ft.'.$filters['date_basis'], '<=', CarbonImmutable::parse($filters['date_to'])->endOfDay());
        }

        return $query;
    }

    private function reportData(Request $request): array
    {
        $filters = $this->filters($request);

        return [$this->reconciliation->reconcile($filters), $this->stores(), $filters];
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'date_basis' => ['nullable', 'in:created_at,released_at'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'in:matched,mismatch,unmatched,pending'],
            'order_sn' => ['nullable', 'string', 'max:120'],
        ]) + [
            'store_id' => $request->input('store_id'),
            'date_basis' => $request->input('date_basis', 'created_at'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'status' => $request->input('status'),
            'order_sn' => $request->input('order_sn'),
        ];
    }

    private function stores(): Collection
    {
        return Store::query()->with('channel')->where('is_active', true)->orderBy('name')->get();
    }

    private function transactionsPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 50);

        return in_array($perPage, [25, 50, 100], true) ? $perPage : 50;
    }
}
