<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
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
        $status = $statusInput === null ? 'incomplete' : (string) $statusInput;
        $search = trim((string) $request->input('q', ''));
        $orderStatus = trim((string) $request->input('order_status', ''));
        $settlementStatus = trim((string) $request->input('settlement_status', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $allowedStatuses = [
            MarketplaceFinancialDataQualityService::ORDER_UNKNOWN,
            MarketplaceFinancialDataQualityService::ORDER_INCOMPLETE,
            MarketplaceFinancialDataQualityService::ORDER_READY,
            MarketplaceFinancialDataQualityService::ORDER_NOT_APPLICABLE,
        ];

        if ($status !== '' && ! in_array($status, $allowedStatuses, true)) {
            $status = 'incomplete';
        }

        $orderQuery = fn () => MarketplaceOrder::query()
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->when($search, fn ($query) => $query->where(function ($nested) use ($search) {
                $nested->where('channel_order_id', 'like', '%' . $search . '%')
                    ->orWhere('external_order_id', 'like', '%' . $search . '%')
                    ->orWhere('booking_sn', 'like', '%' . $search . '%')
                    ->orWhere('buyer_username', 'like', '%' . $search . '%');
            }))
            ->when($orderStatus, fn ($query) => $query->where('order_status', $orderStatus))
            ->when($dateFrom, fn ($query) => $query->whereDate('ordered_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('ordered_at', '<=', $dateTo));
        $settlementQuery = fn () => MarketplaceOrderSettlement::query()
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId));

        $orderCounts = $orderQuery()
            ->selectRaw('COALESCE(financial_data_status, ?) AS status, COUNT(*) AS total', ['unknown'])
            ->groupBy('financial_data_status')
            ->pluck('total', 'status');

        $settlementCounts = $settlementQuery()
            ->selectRaw('COALESCE(data_status, ?) AS status, COUNT(*) AS total', ['unknown'])
            ->groupBy('data_status')
            ->pluck('total', 'status');

        $issueBreakdown = $orderQuery()
            ->where('financial_data_status', MarketplaceFinancialDataQualityService::ORDER_INCOMPLETE)
            ->selectRaw('COALESCE(financial_issue_reason, ?) AS reason, COUNT(*) AS total', ['unknown_reason'])
            ->groupBy('financial_issue_reason')
            ->orderByDesc('total')
            ->get();

        $orders = $orderQuery()
            ->with(['store.channel', 'settlement', 'items'])
            ->when($status !== '', fn ($query) => $query->where('financial_data_status', $status))
            ->when($settlementStatus === 'missing', fn ($query) => $query->doesntHave('settlement'))
            ->when(in_array($settlementStatus, ['complete', 'incomplete', 'unknown'], true), fn ($query) => $query->whereHas('settlement', function ($settlement) use ($settlementStatus) {
                if ($settlementStatus === 'unknown') {
                    $settlement->whereNull('data_status')->orWhere('data_status', 'unknown');
                } else {
                    $settlement->where('data_status', $settlementStatus);
                }
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
            'orderCounts' => $orderCounts,
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

    public function refresh(Request $request, MarketplaceFinancialDataQualityService $quality)
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
        $counts = [
            'orders' => 0,
            'ready' => 0,
            'incomplete' => 0,
            'not_applicable' => 0,
            'unknown' => 0,
            'settlement_complete' => 0,
            'settlement_incomplete' => 0,
            'settlement_unknown' => 0,
        ];

        MarketplaceOrder::query()
            ->with(['settlement', 'items'])
            ->when($storeId !== 'all', fn ($query) => $query->where('store_id', (int) $storeId))
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($quality, $dryRun, &$counts) {
                foreach ($orders as $order) {
                    $counts['orders']++;

                    if ($order->settlement) {
                        $settlement = $quality->assessSettlement($order->settlement->raw_json);
                        $counts['settlement_' . $settlement['status']]++;
                    } else {
                        $counts['settlement_incomplete']++;
                    }

                    $assessment = $dryRun
                        ? $quality->assessOrder($order)
                        : $quality->refreshOrder($order);

                    $counts[$assessment['status']]++;
                }
            });

        $message = ($dryRun ? 'Audit dry-run selesai.' : 'Status kualitas data berhasil disimpan.')
            . " {$counts['orders']} order diperiksa; ready {$counts['ready']}, incomplete {$counts['incomplete']}, not applicable {$counts['not_applicable']}.";

        return redirect()
            ->route('marketplace.reports.financial-quality', [
                'store_id' => $storeId === 'all' ? null : $storeId,
                'status' => $dryRun ? 'incomplete' : 'incomplete',
            ])
            ->with('quality_result', [
                'message' => $message,
                'dry_run' => $dryRun,
                'counts' => $counts,
            ]);
    }
}
