<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Jobs\MarketplaceRefreshDataQualityJob;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Services\MarketplaceIssueService;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
use App\Services\MarketplaceSyncService;
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
        // Saat halaman dibuka tanpa filter operasional, tampilkan hanya queue
        // finansial yang aktif. Order non-COMPLETED belum memiliki payout final
        // yang wajib tersedia dan tidak boleh tampil sebagai issue closing.
        $defaultIssueQueue = $statusInput === null
            && $settlementStatus === ''
            && $orderStatus === ''
            && $search === '';
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
            ->whereHas('order', fn ($order) => $order->whereIn(
                'order_status',
                MarketplaceFinancialDataQualityService::FINANCIAL_ELIGIBLE_ORDER_STATUSES
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

        $eligibleOrderCounts = $orderQuery()
            ->whereIn('order_status', MarketplaceFinancialDataQualityService::FINANCIAL_ELIGIBLE_ORDER_STATUSES)
            ->selectRaw('COALESCE(financial_data_status, ?) AS status, COUNT(*) AS total', ['unknown'])
            ->groupBy('financial_data_status')
            ->pluck('total', 'status');

        $waitingOrderCount = $orderQuery()
            ->where(function ($query) {
                $query
                    ->whereNull('order_status')
                    ->orWhereNotIn('order_status', MarketplaceFinancialDataQualityService::FINANCIAL_ELIGIBLE_ORDER_STATUSES);
            })
            ->count();

        $qualityIssueCount = $eligibleOrderCounts
            ->only([
                MarketplaceFinancialDataQualityService::ORDER_UNKNOWN,
                MarketplaceFinancialDataQualityService::ORDER_INCOMPLETE,
            ])
            ->sum();

        $settlementCounts = $settlementQuery()
            ->selectRaw('COALESCE(data_status, ?) AS status, COUNT(*) AS total', ['unknown'])
            ->groupBy('data_status')
            ->pluck('total', 'status');

        $issueBreakdown = $orderQuery()
            ->whereIn('order_status', MarketplaceFinancialDataQualityService::FINANCIAL_ELIGIBLE_ORDER_STATUSES)
            ->where(function ($query) {
                $query->where('financial_data_status', MarketplaceFinancialDataQualityService::ORDER_INCOMPLETE);
            })
            ->selectRaw('COALESCE(financial_issue_reason, ?) AS reason, COUNT(*) AS total', ['unknown_reason'])
            ->groupBy('financial_issue_reason')
            ->orderByDesc('total')
            ->get();

        $orders = $orderQuery()
            ->with(['store.channel', 'settlement', 'items.internalItem'])
            ->when($defaultIssueQueue, fn ($query) => $query->where(function ($issueQuery) {
                $issueQuery->whereIn(
                    'order_status',
                    MarketplaceFinancialDataQualityService::FINANCIAL_ELIGIBLE_ORDER_STATUSES
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
            'orderCounts' => $eligibleOrderCounts,
            'waitingOrderCount' => $waitingOrderCount,
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

    /**
     * Perbaiki satu order langsung dari antrean kualitas data.
     *
     * Aksi ini tidak mengarang nilai finansial: item hanya di-resolve ulang
     * dari mapping/HPP yang sudah ada, lalu payout ditarik ulang hanya untuk
     * order COMPLETED dan toko yang sedang CONNECTED.
     */
    public function repairOrder(
        Request $request,
        MarketplaceOrder $order,
        MarketplaceIssueService $issues,
        MarketplaceSyncService $sync,
        MarketplaceFinancialDataQualityService $quality,
    ) {
        set_time_limit(180);

        $order->load(['store.channel', 'items']);
        abort_unless($order->store, 404, 'Toko order tidak ditemukan.');

        $resolvedItems = 0;
        foreach ($order->items as $item) {
            $issues->resolveItem($item, $order->store->channel?->code);
            $resolvedItems++;
        }

        $settlementResult = null;
        $channelCode = strtolower((string) $order->store->channel?->code);
        if (
            strtoupper((string) $order->order_status) === 'COMPLETED'
            && in_array($channelCode, ['shopee', 'shp'], true)
            && $order->store->is_active
            && $order->store->status === 'active'
            && $order->store->connection_status === 'CONNECTED'
        ) {
            $settlementResult = $sync->syncSettlements(
                store: $order->store,
                orderSn: $order->channel_order_id,
                resync: true,
                limit: 1,
            );
        }

        $assessment = $quality->refreshOrder($order->fresh());
        $syncMessage = $settlementResult === null
            ? 'Payout tidak ditarik ulang karena order belum COMPLETED atau toko belum CONNECTED.'
            : sprintf(
                'Payout: %d berhasil, %d error.',
                (int) ($settlementResult['synced'] ?? 0),
                (int) ($settlementResult['errors'] ?? 0),
            );

        $message = sprintf(
            'Perbaikan order %s selesai. %d item di-resolve ulang. Status kualitas: %s. %s',
            $order->channel_order_id ?: ('#' . $order->id),
            $resolvedItems,
            $assessment['status'],
            $syncMessage,
        );

        return redirect()->back()->with('quality_result', [
            'message' => $message,
            'dry_run' => false,
            'queued' => false,
        ]);
    }
}
