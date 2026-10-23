<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinanceSyncRun;
use App\Models\MarketplaceFinancialComponent;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceFinanceReconciliationService;
use App\Services\Marketplace\Finance\MarketplaceFinanceSyncHealthService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MarketplaceFinanceController extends Controller
{
    public function __construct(
        private readonly MarketplaceFinanceReconciliationService $reconciliation,
        private readonly MarketplaceFinanceSyncHealthService $syncHealth,
    ) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $stores = $this->stores();
        $overviewData = $this->reconciliation->overview($filters);
        $report = ['summary' => $overviewData['summary']];
        $storeNames = $stores->pluck('name', 'id');
        $transactions = $this->reconciliation->paginateTransactions($filters, 10, 'overview_transaction_page');
        $settlements = $this->reconciliation->paginateSettlements($filters, 10, 'overview_settlement_page');
        $settlementModels = MarketplaceFinanceSettlement::query()
            ->with(['store', 'bankAccount'])
            ->whereIn('id', $settlements->getCollection()->pluck('settlement_id'))
            ->get()
            ->keyBy('id');

        return view('marketplace.finance.index', [
            'filters' => $filters,
            'overview' => $overviewData['metrics'],
            'report' => $report,
            'settlementModels' => $settlementModels,
            'settlements' => $settlements,
            'storeNames' => $storeNames,
            'stores' => $stores,
            'transactions' => $transactions,
            'syncHealth' => $this->syncHealth->orderSync(),
        ]);
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
            'syncHealth' => $this->syncHealth->orderSync(),
        ]);
    }

    public function settlements(Request $request)
    {
        $filters = $this->filters($request);
        $stores = $this->stores();
        $rows = $this->reconciliation->paginateSettlements($filters, $this->transactionsPerPage($request));
        $settlementIds = $rows->getCollection()->pluck('settlement_id');
        $settlementModels = MarketplaceFinanceSettlement::query()
            ->with(['store', 'bankAccount'])
            ->whereIn('id', $settlementIds)
            ->get()
            ->keyBy('id');

        return view('marketplace.finance.settlements', [
            'filters' => $filters,
            'rows' => $rows,
            'settlementModels' => $settlementModels,
            'stores' => $stores,
            'syncHealth' => $this->syncHealth->orderSync(),
        ]);
    }

    public function reconciliation(Request $request)
    {
        $filters = $this->filters($request);
        $stores = $this->stores();
        $page = $this->reconciliation->paginateOrderReconciliation($filters, $this->transactionsPerPage($request));
        $report = [
            'summary' => $page['summary'],
            'transactions' => $page['transactions'],
        ];

        return view('marketplace.finance.reconciliation', [
            'filters' => $filters,
            'report' => $report,
            'stores' => $stores,
            'syncHealth' => $this->syncHealth->orderSync(),
        ]);
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
            ->paginate(10, ['*'], 'fee_type_page')
            ->withQueryString();
        $byType->setCollection($byType->getCollection()->map(fn ($row): array => [
            'label' => $row->code === 'unmapped_adjustment'
                ? 'Selisih settlement belum terpetakan'
                : ($row->label ?: $row->code),
            'code' => $row->code,
            'amount' => round((float) $row->amount, 2),
            'count' => (int) $row->count,
        ])->values());

        $byStore = (clone $componentQuery)
            ->leftJoin('stores as fee_store', 'fee_store.id', '=', 'ft.store_id')
            ->select('ft.store_id')
            ->selectRaw("COALESCE(MAX(fee_store.name), 'Toko tidak diketahui') as store_name")
            ->selectRaw('COUNT(fc.id) as count')
            ->selectRaw('SUM(ABS(fc.amount)) as amount')
            ->groupBy('ft.store_id')
            ->orderBy('store_name')
            ->paginate(10, ['*'], 'fee_store_page')
            ->withQueryString();
        $byStore->setCollection($byStore->getCollection()->map(fn ($row): array => [
            'store_id' => $row->store_id ?? 'unknown',
            'store_name' => $row->store_name ?: 'Toko tidak diketahui',
            'amount' => round((float) $row->amount, 2),
            'count' => (int) $row->count,
        ])->values());

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

        return view('marketplace.finance.fee-analysis', [
            'byOrder' => $byOrder,
            'byStore' => $byStore,
            'byType' => $byType,
            'filters' => $filters,
            'stores' => $stores,
            'syncHealth' => $this->syncHealth->orderSync(),
        ]);
    }

    public function syncRun(int $syncRun)
    {
        $syncRun = MarketplaceFinanceSyncRun::query()->findOrFail($syncRun);
        abort_unless(in_array($syncRun->trigger, ['order_schedule', 'order_manual'], true), 404);

        return view('marketplace.finance.sync-run', [
            'syncHealth' => $this->syncHealth->orderSync(),
            'syncRunAudit' => $this->syncHealth->health($syncRun),
        ]);
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
        if ($filters['date_from'] !== null || $filters['date_to'] !== null) {
            if ($filters['date_basis'] === 'ordered_at') {
                $this->whereFeeTransactionOrderDate($query, $filters);
            } else {
                $this->whereDateRange($query, 'ft.'.$filters['date_basis'], $filters);
            }
        }

        return $query;
    }

    private function whereFeeTransactionOrderDate($query, array $filters): void
    {
        $query->whereExists(function ($orders) use ($filters): void {
            $orders->selectRaw('1')
                ->from('marketplace_orders as finance_order')
                ->where(function ($match) {
                    $match->whereColumn('finance_order.id', 'ft.marketplace_order_id')
                        ->orWhere(function ($fallback) {
                            $fallback->whereColumn('finance_order.store_id', 'ft.store_id')
                                ->where(function ($orderId) {
                                    $orderId->whereColumn('finance_order.channel_order_id', 'ft.order_sn')
                                        ->orWhereColumn('finance_order.external_order_id', 'ft.order_sn');
                                });
                        });
                });

            if ($filters['date_from'] !== null) {
                $orders->whereRaw('COALESCE(finance_order.ordered_at, finance_order.order_date) >= ?', [
                    CarbonImmutable::parse($filters['date_from'])->startOfDay(),
                ]);
            }
            if ($filters['date_to'] !== null) {
                $orders->whereRaw('COALESCE(finance_order.ordered_at, finance_order.order_date) <= ?', [
                    CarbonImmutable::parse($filters['date_to'])->endOfDay(),
                ]);
            }
        });
    }

    private function whereDateRange($query, string $column, array $filters): void
    {
        if ($filters['date_from'] !== null) {
            $query->where($column, '>=', CarbonImmutable::parse($filters['date_from'])->startOfDay());
        }
        if ($filters['date_to'] !== null) {
            $query->where($column, '<=', CarbonImmutable::parse($filters['date_to'])->endOfDay());
        }
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'date_basis' => ['nullable', 'in:ordered_at,created_at,released_at'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'in:matched,mismatch,unmatched,pending,not_applicable'],
            'order_sn' => ['nullable', 'string', 'max:120'],
        ]) + [
            'store_id' => $request->input('store_id'),
            'date_basis' => $request->input('date_basis', 'ordered_at'),
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
        $perPage = (int) $request->input('per_page', 25);

        return $perPage >= 50 ? 50 : 25;
    }
}
