<?php

namespace App\Http\Controllers\Marketplace;

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
        [$report, $stores, $filters] = $this->reportData($request);
        $storeNames = $stores->pluck('name', 'id');

        return view('marketplace.finance.transactions', [
            'filters' => $filters,
            'rows' => $report['transactions'],
            'report' => $report,
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
        $components = MarketplaceFinancialComponent::query()
            ->with('financialTransaction.store')
            ->whereHas('financialTransaction', function ($query) use ($filters): void {
                if ($filters['store_id'] !== null) {
                    $query->where('store_id', $filters['store_id']);
                }
                if ($filters['order_sn'] !== null) {
                    $query->where('order_sn', $filters['order_sn']);
                }
                if ($filters['date_from'] !== null) {
                    $query->whereDate($filters['date_basis'], '>=', $filters['date_from']);
                }
                if ($filters['date_to'] !== null) {
                    $query->whereDate($filters['date_basis'], '<=', $filters['date_to']);
                }
            })
            ->orderBy('component_code')
            ->get();

        $byType = $components->groupBy('component_code')->map(fn (Collection $items): array => [
            'label' => $items->first()->component_name ?: $items->first()->component_code,
            'code' => $items->first()->component_code,
            'amount' => round($items->sum(fn ($item): float => abs((float) $item->amount)), 2),
            'count' => $items->count(),
        ])->values();

        $byStore = $components->groupBy(fn ($item): int|string => $item->financialTransaction?->store_id ?? 'unknown')
            ->map(fn (Collection $items, int|string $storeId): array => [
                'store_id' => $storeId,
                'store_name' => $items->first()->financialTransaction?->store?->name ?? 'Toko tidak diketahui',
                'amount' => round($items->sum(fn ($item): float => abs((float) $item->amount)), 2),
                'count' => $items->count(),
            ])->values();

        $byOrder = $components->groupBy(fn ($item): string => (string) $item->financialTransaction?->order_sn)
            ->map(function (Collection $items, string $orderSn): array {
                $transaction = $items->first()->financialTransaction;
                $gross = (float) ($transaction?->gross_amount ?? 0);
                $fee = round($items->sum(fn ($item): float => abs((float) $item->amount)), 2);

                return [
                    'order_sn' => $orderSn,
                    'store_name' => $transaction?->store?->name ?? 'Toko tidak diketahui',
                    'gross_amount' => round($gross, 2),
                    'fee_amount' => $fee,
                    'fee_percentage' => $gross > 0 ? round(($fee / $gross) * 100, 2) : null,
                ];
            })->values();

        return view('marketplace.finance.fee-analysis', compact('byOrder', 'byStore', 'byType', 'filters', 'stores'));
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
}
