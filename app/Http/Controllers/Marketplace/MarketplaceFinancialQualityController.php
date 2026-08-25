<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Jobs\MarketplaceRefreshDataQualityJob;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
use Illuminate\Http\Request;

class MarketplaceFinancialQualityController extends Controller
{
    public function index(Request $request)
    {
        $stores = Store::with('channel')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $storeId = $request->integer('store_id') ?: null;
        $statusInput = $request->input('status');
        $search = trim((string) $request->input('q', ''));
        $orderStatus = trim((string) $request->input('order_status', ''));
        $settlementStatus = trim((string) $request->input('settlement_status', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        // Saat halaman dibuka tanpa filter, tampilkan seluruh queue yang perlu
        // diperbaiki. Sebelumnya default `incomplete` hanya memeriksa quality
        // order, sehingga settlement incomplete pada order not_applicable tidak
        // pernah masuk ke list.
        $defaultIssueQueue = $statusInput === null && $settlementStatus === '';
        $status = $statusInput === null ? '' : (string) $statusInput;
        $allowedStatuses = [
            MarketplaceFinancialDataQualityService::ORDER_UNKNOWN,
            MarketplaceFinancialDataQualityService::ORDER_INCOMPLETE,
            MarketplaceFinancialDataQualityService::ORDER_READY,
            MarketplaceFinancialDataQualityService::ORDER_NOT_APPLICABLE,
        ];

        if ($status !== '' && ! in_array($status, $allowedStatuses, true)) {
            $status = 'incomplete';
        }

        $applyOrderScope = function ($query) use ($storeId, $search, $orderStatus, $dateFrom, $dateTo, $status) {
            return $query
                ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
                ->when($search, fn ($query) => $query->where(function ($nested) use ($search) {
                    $nested->where('channel_order_id', 'like', '%' . $search . '%')
                        ->orWhere('external_order_id', 'like', '%' . $search . '%')
                        ->orWhere('booking_sn', 'like', '%' . $search . '%')
                        ->orWhere('buyer_username', 'like', '%' . $search . '%');
                }))
                ->when($orderStatus, fn ($query) => $query->where('order_status', $orderStatus))
                ->when($dateFrom, fn ($query) => $query->whereDate('ordered_at', '>=', $dateFrom))
                ->when($dateTo, fn ($query) => $query->whereDate('ordered_at', '<=', $dateTo))
                ->when($status !== '', fn ($query) => $query->where('financial_data_status', $status));
        };

        $applyExplicitOrderFilters = function ($query) use ($applyOrderScope, $settlementStatus) {
            $query = $applyOrderScope($query);

            return $query
                ->when($settlementStatus === 'missing', fn ($query) => $query->doesntHave('settlement'))
                ->when(in_array($settlementStatus, ['complete', 'incomplete', 'unknown'], true), function ($query) use ($settlementStatus) {
                    if ($settlementStatus === 'unknown') {
                        $query->whereHas('settlement', fn ($settlement) => $settlement->whereNull('data_status')->orWhere('data_status', 'unknown'));
                    } else {
                        $query->whereHas('settlement', fn ($settlement) => $settlement->where('data_status', $settlementStatus));
                    }
                });
        };

        $orderQuery = fn () => $applyExplicitOrderFilters(MarketplaceOrder::query());
        $settlementQuery = fn () => MarketplaceOrderSettlement::query()
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->whereHas('order', fn ($order) => $order->whereNotIn(
                'order_status',
                MarketplaceFinancialDataQualityService::NON_APPLICABLE_ORDER_STATUSES
            ))
            ->when($settlementStatus === 'missing', fn ($query) => $query->whereRaw('1 = 0'))
            ->when(in_array($settlementStatus, ['complete', 'incomplete', 'unknown'], true), function ($query) use ($settlementStatus) {
                if ($settlementStatus === 'unknown') {
                    $query->whereNull('data_status')->orWhere('data_status', 'unknown');
                } else {
                    $query->where('data_status', $settlementStatus);
                }
            })
            ->when(
                $search || $orderStatus || $dateFrom || $dateTo || $status !== '',
                fn ($query) => $query->whereHas('order', fn ($order) => $applyOrderScope($order))
            );

        $orderCounts = $orderQuery()
            ->selectRaw('COALESCE(financial_data_status, ?) AS status, COUNT(*) AS total', ['unknown'])
            ->groupBy('financial_data_status')
            ->pluck('total', 'status');

        $qualityIssueCount = $orderQuery()
            ->whereNotIn('order_status', MarketplaceFinancialDataQualityService::NON_APPLICABLE_ORDER_STATUSES)
            ->where(function ($query) {
                $query
                    ->whereNull('financial_data_status')
                    ->orWhereIn('financial_data_status', [
                        MarketplaceFinancialDataQualityService::ORDER_UNKNOWN,
                        MarketplaceFinancialDataQualityService::ORDER_INCOMPLETE,
                    ]);
            })
            ->count();

        $settlementCounts = $settlementQuery()
            ->selectRaw('COALESCE(data_status, ?) AS status, COUNT(*) AS total', ['unknown'])
            ->groupBy('data_status')
            ->pluck('total', 'status');

        $issueBreakdown = $orderQuery()
            ->whereNotIn('order_status', MarketplaceFinancialDataQualityService::NON_APPLICABLE_ORDER_STATUSES)
            ->where('financial_data_status', MarketplaceFinancialDataQualityService::ORDER_INCOMPLETE)
            ->selectRaw('COALESCE(financial_issue_reason, ?) AS reason, COUNT(*) AS total', ['unknown_reason'])
            ->groupBy('financial_issue_reason')
            ->orderByDesc('total')
            ->get();

        $orders = $orderQuery()
            ->with(['store.channel', 'settlement', 'items'])
            ->when($defaultIssueQueue, fn ($query) => $query->where(function ($issueQuery) {
                $issueQuery->whereNotIn(
                    'order_status',
                    MarketplaceFinancialDataQualityService::NON_APPLICABLE_ORDER_STATUSES
                )->where(function ($activeIssue) {
                    $activeIssue
                        ->where(function ($orderQuality) {
                            $orderQuality
                                ->whereNull('financial_data_status')
                                ->orWhereIn('financial_data_status', [
                                    MarketplaceFinancialDataQualityService::ORDER_UNKNOWN,
                                    MarketplaceFinancialDataQualityService::ORDER_INCOMPLETE,
                                ]);
                        })
                        ->orWhere(function ($missingSettlement) {
                            $missingSettlement
                                ->whereIn('order_status', MarketplaceFinancialDataQualityService::FINANCIAL_ELIGIBLE_ORDER_STATUSES)
                                ->doesntHave('settlement');
                        })
                        ->orWhereHas('settlement', function ($settlement) {
                            $settlement->whereNull('data_status')
                                ->orWhereIn('data_status', [
                                    MarketplaceFinancialDataQualityService::SETTLEMENT_UNKNOWN,
                                    MarketplaceFinancialDataQualityService::SETTLEMENT_INCOMPLETE,
                                ]);
                        });
                });
            }))
            ->latest('financial_checked_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $lastCheckedAt = collect([
            $orderQuery()->max('financial_checked_at'),
            $settlementQuery()->max('data_checked_at'),
        ])->filter()->sortDesc()->first();

        return view('marketplace.reports.financial_quality', [
            'stores' => $stores,
            'storeId' => $storeId,
            'status' => $status,
            'defaultIssueQueue' => $defaultIssueQueue,
            'orderCounts' => $orderCounts,
            'qualityIssueCount' => $qualityIssueCount,
            'settlementCounts' => $settlementCounts,
            'issueBreakdown' => $issueBreakdown,
            'orders' => $orders,
            'search' => $search,
            'orderStatus' => $orderStatus,
            'settlementStatus' => $settlementStatus,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'orderStatuses' => MarketplaceOrder::query()
                ->whereNotNull('order_status')
                ->distinct()
                ->orderBy('order_status')
                ->pluck('order_status'),
            'lastCheckedAt' => $lastCheckedAt ?: null,
        ]);
    }

    public function refresh(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['required', 'string'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $storeId = (string) $data['store_id'];
        if ($storeId !== 'all' && ! Store::whereKey((int) $storeId)->exists()) {
            return back()->withErrors(['store_id' => 'Store tidak ditemukan.']);
        }

        $dryRun = (bool) ($data['dry_run'] ?? false);
        MarketplaceRefreshDataQualityJob::dispatch($storeId, $dryRun);

        $message = $dryRun
            ? 'Audit dry-run masuk antrean dan akan memeriksa data tanpa menyimpan perubahan.'
            : 'Refresh status kualitas masuk antrean dan akan diproses oleh worker marketplace-quality.';

        return redirect()
            ->route('marketplace.reports.financial-quality', [
                'store_id' => $storeId === 'all' ? null : $storeId,
                'status' => 'incomplete',
            ])
            ->with('quality_result', [
                'message' => $message,
                'dry_run' => $dryRun,
                'queued' => true,
            ]);
    }
}
