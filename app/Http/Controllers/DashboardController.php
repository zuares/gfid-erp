<?php

namespace App\Http\Controllers;

use App\Services\Inventory\InventoryIntelligenceService;
use App\Services\Marketplace\MarketplaceAnalyticsSummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

/**
 * Dashboard beranda per-role.
 *
 * Menyajikan angka nyata + tombol aksi cepat dengan bahasa yang mudah
 * dimengerti di lapangan. Semua query dibungkus aman (safe*) supaya halaman
 * tetap tampil walau sebagian tabel kosong atau skema berbeda di production.
 */
class DashboardController extends Controller
{
    /** Kode gudang penting untuk membaca posisi barang di tiap tahap. */
    private const WH = [
        'RM'       => 'RM',
        'CUT'      => 'WIP-CUT',
        'SEW'      => 'WIP-SEW',
        'FIN'      => 'WIP-FIN',
        'PACK'     => 'WIP-PACK',
        'FG'       => 'FG',
        'RTS'      => 'WH-RTS',
    ];

    /** Status order yang masih perlu diproses/dikemas oleh tim. */
    private const DASHBOARD_PENDING_ORDER_STATUSES = ['READY_TO_SHIP', 'PROCESSED'];
    private const PRODUCTION_WAREHOUSE_CODES = ['WIP-CUT', 'WIP-SEW', 'WIP-FIN', 'WIP-PACK'];

    public function __construct(
        private InventoryIntelligenceService $inventoryIntelligence,
        private MarketplaceAnalyticsSummaryService $marketplaceAnalytics,
    )
    {
    }

    public function devRunAudit(): \Illuminate\Http\JsonResponse
    {
        \Illuminate\Support\Facades\Artisan::call('inventory:audit-allocated', ['--quiet' => false]);
        return response()->json(['message' => "Audit selesai dijalankan."]);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $this->roleBucket($user);

        $data = match ($role) {
            'owner'     => $this->ownerData(),
            'admin'     => $this->adminData(),
            'operating' => $this->operatingData(),
            default     => [],
        };

        return view('dashboard.index', [
            'role'      => $role,
            'greeting'  => $this->greeting(),
            'userName'  => $user?->name ?? 'User',
            'today'     => Carbon::now()->translatedFormat('l, d F Y'),
            'd'         => $data,
        ]);
    }

    // =====================================================================
    //  DATA PER ROLE
    // =====================================================================

    private function ownerData(): array
    {
        $cacheKey = 'dashboard.owner.v5.' . Carbon::today()->toDateString();

        return (array) Cache::store('file')->remember($cacheKey, now()->addMinutes(2), fn () => $this->ownerDataUncached());
    }

    private function ownerDataUncached(): array
    {
        $today = Carbon::today();

        $monthKpiResponse = $this->safe(
            fn () => $this->marketplaceAnalytics->kpis([
                'store_id' => null,
                'date_from' => $today->copy()->startOfMonth()->toDateString(),
                'date_to' => $today->toDateString(),
                'compare_mode' => 'prev_month',
            ]),
            ['current' => [], 'previous' => []],
        );
        $marketplaceMonthKpi = $monthKpiResponse['current'] ?? [];
        $marketplaceMonthPreviousKpi = $monthKpiResponse['previous'] ?? [];
        $todayKpiResponse = $this->safe(
            fn () => $this->marketplaceAnalytics->kpis([
                'store_id' => null,
                'date_from' => $today->toDateString(),
                'date_to' => $today->toDateString(),
                'compare_mode' => 'prev_period',
            ]),
            ['current' => [], 'previous' => []],
        );
        $todaySameDateResponse = $this->safe(
            fn () => $this->marketplaceAnalytics->kpis([
                'store_id' => null,
                'date_from' => $today->toDateString(),
                'date_to' => $today->toDateString(),
                'compare_mode' => 'prev_month',
            ]),
            ['current' => [], 'previous' => []],
        );
        $marketplaceTodayKpi = $todayKpiResponse['current'] ?? [];
        $marketplaceYesterdayKpi = $todayKpiResponse['previous'] ?? [];
        $marketplaceSameDatePreviousKpi = $todaySameDateResponse['previous'] ?? [];
        foreach ([&$marketplaceTodayKpi, &$marketplaceYesterdayKpi, &$marketplaceSameDatePreviousKpi] as &$todaySnapshot) {
            $todaySnapshot['net_aov'] = (int) ($todaySnapshot['order_total'] ?? 0) > 0
                ? (float) ($todaySnapshot['net_order_revenue'] ?? 0) / (int) $todaySnapshot['order_total']
                : 0;
        }
        unset($todaySnapshot);
        foreach (['net_order_revenue', 'order_total', 'net_aov'] as $todayComparisonKey) {
            $marketplaceTodayKpi['comparisons'][$todayComparisonKey] = [
                'yesterday' => $this->percentageComparison(
                    (float) ($marketplaceTodayKpi[$todayComparisonKey] ?? 0),
                    (float) ($marketplaceYesterdayKpi[$todayComparisonKey] ?? 0),
                ),
                'same_date' => $this->percentageComparison(
                    (float) ($marketplaceTodayKpi[$todayComparisonKey] ?? 0),
                    (float) ($marketplaceSameDatePreviousKpi[$todayComparisonKey] ?? 0),
                ),
            ];
        }
        $monthNetRevenue = (float) ($marketplaceMonthKpi['net_order_revenue'] ?? 0);
        $monthAdCost = (float) ($marketplaceMonthKpi['ad_cost'] ?? 0);
        $marketplaceMonthKpi['net_aov'] = (int) ($marketplaceMonthKpi['order_total'] ?? 0) > 0
            ? $monthNetRevenue / (int) $marketplaceMonthKpi['order_total']
            : 0;
        $marketplaceMonthKpi['roas_net'] = $monthAdCost > 0
            ? $monthNetRevenue / $monthAdCost
            : null;
        $previousMonthNetRevenue = (float) ($marketplaceMonthPreviousKpi['net_order_revenue'] ?? 0);
        $previousMonthAdCost = (float) ($marketplaceMonthPreviousKpi['ad_cost'] ?? 0);
        $marketplaceMonthPreviousKpi['net_aov'] = (int) ($marketplaceMonthPreviousKpi['order_total'] ?? 0) > 0
            ? $previousMonthNetRevenue / (int) $marketplaceMonthPreviousKpi['order_total']
            : 0;
        $marketplaceMonthPreviousKpi['roas_net'] = $previousMonthAdCost > 0
            ? $previousMonthNetRevenue / $previousMonthAdCost
            : null;
        $marketplaceMonthKpi['exception_total'] = (int) ($marketplaceMonthKpi['cancelled_count'] ?? 0)
            + (int) ($marketplaceMonthKpi['return_refund_order_count'] ?? 0);
        $marketplaceMonthPreviousKpi['exception_total'] = (int) ($marketplaceMonthPreviousKpi['cancelled_count'] ?? 0)
            + (int) ($marketplaceMonthPreviousKpi['return_refund_order_count'] ?? 0);
        $comparisonKeys = [
            'net_order_revenue', 'gmv', 'order_total', 'product_qty', 'net_aov',
            'estimated_profit', 'estimated_profit_margin', 'cash_payout',
            'cash_marketplace_fees', 'ad_cost', 'roas_net', 'cash_affiliate_fees',
            'exception_total', 'cash_unsettled_order_count',
        ];
        foreach ($comparisonKeys as $comparisonKey) {
            $marketplaceMonthKpi['comparisons'][$comparisonKey] = $this->percentageComparison(
                (float) ($marketplaceMonthKpi[$comparisonKey] ?? 0),
                (float) ($marketplaceMonthPreviousKpi[$comparisonKey] ?? 0),
            );
        }
        // Read-only forecast dari Inventory Intelligence. Jika data stok belum
        // lengkap, dashboard tetap tampil dan hanya menunjukkan saran 0.
        $intelligenceRows = $this->safe(
            fn () => $this->inventoryIntelligence->rows([]),
            collect(),
        );
        $productionSuggestions = $intelligenceRows
            ->filter(fn ($row) => (float) ($row->production_suggested_qty ?? 0) > 0);
        $procurementSuggestions = $intelligenceRows
            ->filter(fn ($row) => (float) ($row->procurement_suggested_qty ?? 0) > 0);

        // Fetch Ads Analytics for Executive Dashboard (Owner only)
        $adsHourly = [];
        $adsCampaigns = [];
        try {
            if (Schema::hasTable('marketplace_ads_hourly_performances') && Schema::hasTable('marketplace_ad_campaign_dailies')) {
                $from = Carbon::today()->subDays(6)->toDateString();
                $to = Carbon::today()->toDateString();
                
                // 1. Heatmap (7 Days)
                $adsHourly = DB::table('marketplace_ads_hourly_performances')
                    ->selectRaw('performance_hour, SUM(expense) as spend, SUM(broad_gmv) as gmv, SUM(clicks) as clicks, SUM(broad_order) as orders')
                    ->whereBetween('date', [$from, $to])
                    ->groupBy('performance_hour')
                    ->orderBy('performance_hour')
                    ->get();
                    
                // 2. Top 5 Campaigns Spend Share (7 Days)
                $adsCampaigns = DB::table('marketplace_ad_campaign_dailies')
                    ->join('marketplace_ad_campaigns', 'marketplace_ad_campaign_dailies.channel_campaign_id', '=', 'marketplace_ad_campaigns.campaign_id')
                    ->selectRaw('marketplace_ad_campaigns.campaign_name, SUM(marketplace_ad_campaign_dailies.expense) as total_spend')
                    ->whereBetween('marketplace_ad_campaign_dailies.date', [$from, $to])
                    ->groupBy('marketplace_ad_campaigns.campaign_name')
                    ->having('total_spend', '>', 0)
                    ->orderByDesc('total_spend')
                    ->limit(5)
                    ->get();
            }
        } catch (\Throwable $e) {}

        return [
            'marketplace_today_kpi' => $marketplaceTodayKpi,
            'marketplace_yesterday_kpi' => $marketplaceYesterdayKpi,
            'marketplace_same_date_previous_kpi' => $marketplaceSameDatePreviousKpi,
            'marketplace_kpi'      => $marketplaceMonthKpi,
            'marketplace_month_kpi' => $marketplaceMonthKpi,
            'marketplace_month_previous_kpi' => $marketplaceMonthPreviousKpi,
            'orders_todo'         => $this->mpUnshippedCount(),
            'orders_ready'        => $this->mpStatusCount(['READY_TO_SHIP']),
            'orders_shipped_7'    => $this->mpStatusCount(['SHIPPED'], 6),
            'wip_total'           => $this->whQty([self::WH['CUT'], self::WH['SEW'], self::WH['FIN'], self::WH['PACK']]),
            'fg_ready'            => $this->whQty([self::WH['FG'], self::WH['RTS']]),
            'stock_out_fg'        => $this->stockOutCount('finished_good'),
            'stock_out_rm'        => $this->stockOutCount('material'),
            'production_suggestion_items' => $productionSuggestions->count(),
            'production_suggestion_qty' => (float) $productionSuggestions->sum('production_suggested_qty'),
            'production_suggestion_value' => (float) $productionSuggestions->reduce(
                fn ($total, $row) => $total + ((float) ($row->production_suggested_qty ?? 0) * (float) ($row->unit_cost ?? 0)),
                0,
            ),
            'procurement_suggestion_items' => $procurementSuggestions->count(),
            'procurement_suggestion_qty' => (float) $procurementSuggestions->sum('procurement_suggested_qty'),
            'procurement_suggestion_value' => (float) $procurementSuggestions->reduce(
                fn ($total, $row) => $total + ((float) ($row->procurement_suggested_qty ?? 0) * (float) ($row->unit_cost ?? 0)),
                0,
            ),
            'po_unreceived'       => $this->poCount(['not_received', 'partially_received'], 'received_status'),
            'po_unpaid'           => $this->poCount(['unpaid', 'partial'], 'payment_status'),
            'reject_total'        => $this->whQty(['REJ-CUT', 'REJ-SEW', 'REJ-FIN', 'REJECT']),
            'list_todo_items'    => $this->mpUnshippedItemList(12),
            'repeat_orders'     => $this->mpRepeatOrderList(8),
            'list_stock'          => $this->stockCriticalList(6),
            'ads_hourly'          => $adsHourly,
            'ads_campaigns'       => $adsCampaigns,
        ];
    }

    private function adminData(): array
    {
        $cacheKey = 'dashboard.admin.v6.' . Carbon::today()->toDateString();

        return (array) Cache::store('file')->remember($cacheKey, now()->addMinutes(2), fn () => $this->adminDataUncached());
    }

    private function adminDataUncached(): array
    {
        $salesTd = $this->mpSales(0);
        $sales7  = $this->mpSales(6);

        return [
            'orders_today'      => $this->mpOrdersInPeriod(0),
            'sales_today'       => $salesTd['amount'],
            'sales_7'           => $sales7['amount'],
            'orders_todo'       => $this->mpUnshippedCount(),
            'orders_ready'      => $this->mpStatusCount(['READY_TO_SHIP']),
            'orders_processed'  => $this->mpStatusCount(['PROCESSED']),
            'orders_shipped_7'  => $this->mpStatusCount(['SHIPPED'], 6),
            'orders_issue'      => $this->mpNeedFixCount(),
            'po_unreceived'     => $this->poCount(['not_received', 'partially_received'], 'received_status'),
            'stock_out_fg'      => $this->stockOutCount('finished_good'),
            'fg_ready'          => $this->whQty([self::WH['FG'], self::WH['RTS']]),
            'list_todo'         => $this->mpUnshippedList(10),
            'list_todo_items'   => $this->mpUnshippedItemList(12),
            'repeat_orders'     => $this->mpRepeatOrderList(8),
            'list_stock'        => $this->stockCriticalList(6, 'finished_good'),
        ];
    }

    private function operatingData(): array
    {
        return [
            'wip_cut'        => $this->whQty([self::WH['CUT']]),
            'wip_sew'        => $this->whQty([self::WH['SEW']]),
            'wip_fin'        => $this->whQty([self::WH['FIN']]),
            'wip_pack'       => $this->whQty([self::WH['PACK']]),
            'fg_ready'       => $this->whQty([self::WH['FG'], self::WH['RTS']]),
            'rm_low'         => $this->stockOutCount('material'),
            'reject_total'   => $this->whQty(['REJ-CUT', 'REJ-SEW', 'REJ-FIN', 'REJECT']),
            'stock_req_open' => $this->stockRequestOpen(),
            'list_sew'       => $this->whItemsList(self::WH['SEW'], 8),
            'list_rm'        => $this->stockCriticalList(8, 'material'),
        ];
    }

    // =====================================================================
    //  HELPER QUERY (defensif)
    // =====================================================================

    /** Jalankan closure query, kembalikan fallback bila gagal / tabel tak ada. */
    private function safe(callable $fn, $fallback = 0)
    {
        try {
            return $fn();
        } catch (Throwable) {
            return $fallback;
        }
    }

    /** Penjualan marketplace n hari ke belakang (0 = hari ini). */
    private function mpSales(int $daysBack): array
    {
        return $this->mpSalesRange(
            Carbon::today()->subDays($daysBack),
            Carbon::today(),
        );
    }

    /** Perbandingan persentase terhadap hari pembanding dengan metrik yang sama. */
    private function percentageComparison(float $current, float $previous): array
    {
        if ($previous === 0.0) {
            return [
                'value' => $current > 0 ? 'Baru' : '—',
                'tone' => $current > 0 ? 'up' : 'muted',
            ];
        }

        $delta = round((($current - $previous) / abs($previous)) * 100, 1);

        return [
            'value' => ($delta >= 0 ? '↑ ' : '↓ ') . number_format(abs($delta), 1, ',', '.') . '%',
            'tone' => $delta == 0.0 ? 'muted' : ($delta > 0 ? 'up' : 'down'),
        ];
    }

    /** Penjualan marketplace pada rentang tanggal inklusif. */
    private function mpSalesRange(Carbon $from, Carbon $to): array
    {
        return $this->safe(function () use ($from, $to) {
            if (!Schema::hasTable('marketplace_orders')) {
                return ['count' => 0, 'amount' => 0];
            }
            $col  = $this->mpDateCol();

            $q = DB::table('marketplace_orders')
                ->whereBetween($col, [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->where(fn ($w) => $w->where('order_status', '!=', 'CANCELLED')->orWhereNull('order_status'));

            $amountExpr = 'COALESCE(total_amount, total_paid_customer, 0)';

            return [
                'count'  => (int) (clone $q)->count(),
                'amount' => (float) (clone $q)->sum(DB::raw($amountExpr)),
            ];
        }, ['count' => 0, 'amount' => 0]);
    }

    private function mpOrdersInPeriod(int $daysBack): int
    {
        return (int) $this->mpSales($daysBack)['count'];
    }

    /** Kolom tanggal yang tersedia di marketplace_orders. */
    private function mpDateCol(): string
    {
        if (Schema::hasColumn('marketplace_orders', 'order_date')) {
            return 'order_date';
        }
        if (Schema::hasColumn('marketplace_orders', 'ordered_at')) {
            return 'ordered_at';
        }
        return 'created_at';
    }

    /** Pesanan belum dikirim (perlu diproses + siap kirim). */
    private function mpUnshippedCount(): int
    {
        return $this->mpStatusCount(self::DASHBOARD_PENDING_ORDER_STATUSES);
    }

    private function mpStatusCount(array $statuses, ?int $daysBack = null): int
    {
        return (int) $this->safe(function () use ($statuses, $daysBack) {
            if (!Schema::hasTable('marketplace_orders') || !Schema::hasColumn('marketplace_orders', 'order_status')) {
                return 0;
            }
            $q = DB::table('marketplace_orders')->whereIn('order_status', $statuses);
            if ($daysBack !== null) {
                $q->whereBetween($this->mpDateCol(), [
                    Carbon::today()->subDays($daysBack)->startOfDay(),
                    Carbon::today()->endOfDay(),
                ]);
            }
            return $q->count();
        });
    }

    /** Pesanan yang perlu diperbaiki datanya (belum termapping / tanpa AWB dll). */
    private function mpNeedFixCount(): int
    {
        return (int) $this->safe(function () {
            if (!Schema::hasTable('marketplace_orders')) {
                return 0;
            }
            $q = DB::table('marketplace_orders')
                ->whereIn('order_status', ['READY_TO_SHIP', 'PROCESSED']);
            if (Schema::hasColumn('marketplace_orders', 'shipping_awb_no')) {
                $q->where(fn ($w) => $w->whereNull('shipping_awb_no')->orWhere('shipping_awb_no', ''));
            }
            return $q->count();
        });
    }

    private function mpUnshippedList(int $limit): array
    {
        return (array) $this->safe(function () use ($limit) {
            if (!Schema::hasTable('marketplace_orders')) {
                return [];
            }

            $orderColumns = $limit > 0
                ? [
                    'id',
                    'external_invoice_no', 'external_order_id', 'buyer_name',
                    'shipping_city', 'shipping_courier_code', 'order_status',
                    DB::raw('COALESCE(total_amount, total_paid_customer, 0) as amount'),
                ]
                : ['id'];

            $orders = DB::table('marketplace_orders')
                ->whereIn('order_status', self::DASHBOARD_PENDING_ORDER_STATUSES)
                ->when($limit > 0, function ($query) use ($limit) {
                    $query->orderByDesc($this->mpDateCol())->limit($limit);
                })
                ->get($orderColumns);

            if ($orders->isEmpty() || ! Schema::hasTable('marketplace_order_items')) {
                return $orders
                    ->map(fn ($r) => array_merge((array) $r, ['items' => []]))
                    ->all();
            }

            // Data marketplace lama memakai order_id, sedangkan data omnichannel
            // baru memakai marketplace_order_id. Ambil keduanya agar daftar
            // dashboard tetap lengkap saat data berasal dari dua alur sync.
            $orderItemOrderColumns = collect(['marketplace_order_id', 'order_id'])
                ->filter(fn ($column) => Schema::hasColumn('marketplace_order_items', $column))
                ->values()
                ->all();

            if ($orderItemOrderColumns === []) {
                return $orders
                    ->map(fn ($r) => array_merge((array) $r, ['items' => []]))
                    ->all();
            }

            $orderIds = $orders->pluck('id')->all();
            $orderItems = DB::table('marketplace_order_items')
                ->where(function ($query) use ($orderItemOrderColumns, $orderIds) {
                    foreach ($orderItemOrderColumns as $index => $column) {
                        $method = $index === 0 ? 'whereIn' : 'orWhereIn';
                        $query->{$method}($column, $orderIds);
                    }
                })
                ->get();

            $itemIdColumns = collect(['internal_item_id', 'item_id'])
                ->filter(fn ($column) => Schema::hasColumn('marketplace_order_items', $column))
                ->values()
                ->all();
            $itemIds = collect($orderItems)
                ->map(function ($item) use ($itemIdColumns) {
                    foreach ($itemIdColumns as $column) {
                        if (! empty($item->{$column})) {
                            return (int) $item->{$column};
                        }
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->values();

            // Stok siap jual tetap dipisahkan dari stok yang masih berada di
            // proses produksi agar selisih fulfillment tidak tertutup.
            $stockByItemId = collect();
            if ($itemIds->isNotEmpty()
                && Schema::hasTable('inventory_stocks')
                && Schema::hasTable('warehouses')) {
                $sellableWarehouseCodes = [self::WH['FG'], self::WH['RTS']];
                $stockWarehouseCodes = array_merge($sellableWarehouseCodes, self::PRODUCTION_WAREHOUSE_CODES);
                $stockByItemId = DB::table('inventory_stocks as s')
                    ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                    ->whereIn('w.code', $stockWarehouseCodes)
                    ->whereIn('s.item_id', $itemIds->all())
                    ->select('s.item_id')
                    ->selectRaw(
                        'COALESCE(SUM(CASE WHEN w.code IN (?, ?) THEN s.qty ELSE 0 END), 0) as stock_qty',
                        $sellableWarehouseCodes,
                    )
                    ->selectRaw(
                        'COALESCE(SUM(CASE WHEN w.code IN (?, ?, ?, ?) THEN s.qty ELSE 0 END), 0) as production_stock_qty',
                        self::PRODUCTION_WAREHOUSE_CODES,
                    )
                    ->groupBy('s.item_id')
                    ->get()
                    ->keyBy('item_id');
            }

            $internalItemsById = collect();
            if ($itemIds->isNotEmpty() && Schema::hasTable('items')) {
                $itemsQuery = DB::table('items as i');
                $itemSelect = ['i.id', 'i.code', 'i.name'];
                if (
                    Schema::hasTable('item_categories')
                    && Schema::hasColumn('items', 'item_category_id')
                ) {
                    $itemsQuery->leftJoin('item_categories as c', 'c.id', '=', 'i.item_category_id');
                    $itemSelect[] = 'c.code as category_code';
                    $itemSelect[] = 'c.name as category_name';
                }

                $internalItemsById = $itemsQuery
                    ->whereIn('i.id', $itemIds->all())
                    ->get($itemSelect)
                    ->keyBy('id');
            }

            $itemsByOrder = [];
            foreach ($orderItems as $item) {
                $itemData = (array) $item;
                $orderId = null;
                foreach ($orderItemOrderColumns as $column) {
                    if (! empty($itemData[$column])) {
                        $orderId = (int) $itemData[$column];
                        break;
                    }
                }
                if (! $orderId) {
                    continue;
                }

                $internalItemId = null;
                foreach ($itemIdColumns as $column) {
                    if (! empty($itemData[$column])) {
                        $internalItemId = (int) $itemData[$column];
                        break;
                    }
                }
                $internalItem = $internalItemId ? $internalItemsById->get($internalItemId) : null;

                $name = $itemData['item_name']
                    ?? $itemData['item_name_snapshot']
                    ?? 'Produk belum dipetakan';
                $variant = $itemData['variant_name']
                    ?? $itemData['variant_snapshot']
                    ?? null;
                $sku = $itemData['model_sku']
                    ?? $itemData['item_sku']
                    ?? $itemData['marketplace_sku']
                    ?? $itemData['external_sku']
                    ?? $itemData['item_code_snapshot']
                    ?? null;
                $stockData = $internalItemId ? $stockByItemId->get($internalItemId) : null;

                $itemsByOrder[$orderId][] = [
                    'name' => trim((string) $name) ?: 'Produk belum dipetakan',
                    'variant' => trim((string) $variant),
                    'sku' => trim((string) $sku),
                    'qty' => (float) ($itemData['qty'] ?? 0),
                    'stock_qty' => $internalItemId ? (float) ($stockData->stock_qty ?? 0) : null,
                    'production_stock_qty' => $internalItemId ? (float) ($stockData->production_stock_qty ?? 0) : null,
                    'mapped' => (bool) $internalItemId,
                    'internal_item_id' => $internalItemId,
                    'internal_code' => $internalItem?->code,
                    'internal_name' => $internalItem?->name,
                    'internal_category' => $internalItem?->category_name ?: ($internalItem?->category_code ?? null),
                ];
            }

            return $orders
                ->map(function ($order) use ($itemsByOrder) {
                    return array_merge((array) $order, [
                        'items' => $itemsByOrder[(int) $order->id] ?? [],
                    ]);
                })
                ->all();
        }, []);
    }

    /** Ringkasan permintaan pesanan belum dikirim per item internal. */
    private function mpUnshippedItemList(int $limit): array
    {
        return (array) $this->safe(function () use ($limit) {
            $orders = $this->mpUnshippedList(0);
            $grouped = [];

            foreach ($orders as $order) {
                foreach ($order['items'] ?? [] as $item) {
                    $internalItemId = (int) ($item['internal_item_id'] ?? 0);
                    $key = $internalItemId > 0
                        ? 'internal:' . $internalItemId
                        : 'unmapped:' . md5(strtolower(($item['sku'] ?? '') . '|' . ($item['name'] ?? '')));

                    if (! isset($grouped[$key])) {
                        $grouped[$key] = [
                            'name' => $item['internal_name'] ?: ($item['name'] ?? 'Produk belum dipetakan'),
                            'code' => $item['internal_code'] ?: ($item['sku'] ?? ''),
                            'category' => $item['internal_category'] ?: 'Tanpa kategori',
                            'demand_qty' => 0,
                            'stock_qty' => $item['stock_qty'] ?? null,
                            'production_stock_qty' => $item['production_stock_qty'] ?? null,
                            'mapped' => $internalItemId > 0,
                        ];
                    }

                    $grouped[$key]['demand_qty'] += (float) ($item['qty'] ?? 0);
                }
            }

            $rows = array_values($grouped);
            usort($rows, fn ($a, $b) => $b['demand_qty'] <=> $a['demand_qty']);
            $rows = array_slice($rows, 0, $limit);
            usort($rows, function ($a, $b) {
                $categoryOrder = strcasecmp((string) ($a['category'] ?? ''), (string) ($b['category'] ?? ''));

                return $categoryOrder !== 0
                    ? $categoryOrder
                    : ($b['demand_qty'] <=> $a['demand_qty']);
            });

            return $rows;
        }, []);
    }

    /** Order terbaru dari pembeli yang sudah pernah menyelesaikan order. */
    private function mpRepeatOrderList(int $limit): array
    {
        return (array) $this->safe(function () use ($limit) {
            if (! Schema::hasTable('marketplace_orders')) {
                return [];
            }

            $today = Carbon::today();
            $dateColumn = $this->mpDateCol();
            $buyerKey = "COALESCE(NULLIF(buyer_username, ''), NULLIF(buyer_name, ''))";
            $currentBuyerKey = "COALESCE(NULLIF(current.buyer_username, ''), NULLIF(current.buyer_name, ''))";
            $completedHistory = DB::table('marketplace_orders')
                ->where('order_status', 'COMPLETED')
                ->whereRaw("{$buyerKey} IS NOT NULL")
                ->select('store_id')
                ->selectRaw("{$buyerKey} as buyer_key")
                ->selectRaw('COUNT(*) as previous_order_count')
                ->selectRaw("MAX(COALESCE(ordered_at, order_date, created_at)) as last_purchased_at")
                ->groupBy('store_id')
                ->groupByRaw($buyerKey);

            return DB::table('marketplace_orders as current')
                ->joinSub($completedHistory, 'repeat_history', function ($join) use ($currentBuyerKey) {
                    $join->on('repeat_history.store_id', '=', 'current.store_id')
                        ->on('repeat_history.buyer_key', '=', DB::raw($currentBuyerKey));
                })
                ->whereIn('current.order_status', self::DASHBOARD_PENDING_ORDER_STATUSES)
                ->whereBetween('current.' . $dateColumn, [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->orderByDesc('current.' . $dateColumn)
                ->limit($limit)
                ->get([
                    'current.id',
                    DB::raw("COALESCE(NULLIF(current.buyer_name, ''), NULLIF(current.buyer_username, ''), 'Tanpa nama') as buyer_label"),
                    DB::raw("COALESCE(NULLIF(current.external_invoice_no, ''), NULLIF(current.external_order_id, ''), current.channel_order_id, CONCAT('ORDER-', current.id)) as order_number"),
                    'current.order_status',
                    DB::raw('COALESCE(current.ordered_at, current.order_date, current.created_at) as ordered_at'),
                    DB::raw("CASE WHEN current.order_status = 'COMPLETED' THEN repeat_history.previous_order_count - 1 ELSE repeat_history.previous_order_count END as previous_order_count"),
                    'repeat_history.last_purchased_at',
                ])
                ->map(fn ($row) => (array) $row)
                ->all();
        }, []);
    }

    /** Total qty di gudang tertentu (berdasarkan kode gudang). */
    private function whQty(array $codes): float
    {
        return (float) $this->safe(function () use ($codes) {
            if (!Schema::hasTable('inventory_stocks') || !Schema::hasTable('warehouses')) {
                return 0;
            }
            return DB::table('inventory_stocks as s')
                ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->whereIn('w.code', $codes)
                ->sum('s.qty');
        });
    }

    /** Daftar item terbanyak di sebuah gudang. */
    private function whItemsList(string $code, int $limit): array
    {
        return (array) $this->safe(function () use ($code, $limit) {
            if (!Schema::hasTable('inventory_stocks') || !Schema::hasTable('items')) {
                return [];
            }
            return DB::table('inventory_stocks as s')
                ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->join('items as i', 'i.id', '=', 's.item_id')
                ->where('w.code', $code)
                ->where('s.qty', '>', 0)
                ->orderByDesc('s.qty')
                ->limit($limit)
                ->get(['i.code as item_code', 'i.name as item_name', 's.qty'])
                ->map(fn ($r) => (array) $r)
                ->all();
        }, []);
    }

    /** Jumlah item yang stoknya habis (<=0) untuk tipe tertentu. */
    private function stockOutCount(string $type): int
    {
        return (int) $this->safe(function () use ($type) {
            if (!Schema::hasTable('items') || !Schema::hasTable('inventory_stocks')) {
                return 0;
            }
            // Total qty per item di semua gudang non-reject, lalu hitung yang <= 0.
            $sub = DB::table('inventory_stocks as s')
                ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->where('w.code', 'not like', 'REJ%')
                ->where('w.code', '!=', 'REJECT')
                ->groupBy('s.item_id')
                ->select('s.item_id', DB::raw('SUM(s.qty) as total_qty'));

            return DB::table('items as i')
                ->leftJoinSub($sub, 't', 't.item_id', '=', 'i.id')
                ->where('i.type', $type)
                ->where('i.active', 1)
                ->where(fn ($w) => $w->whereNull('t.total_qty')->orWhere('t.total_qty', '<=', 0))
                ->count();
        });
    }

    /** Daftar item stok kritis (habis / menipis). */
    private function stockCriticalList(int $limit, ?string $type = null): array
    {
        return (array) $this->safe(function () use ($limit, $type) {
            if (!Schema::hasTable('items') || !Schema::hasTable('inventory_stocks')) {
                return [];
            }
            $sub = DB::table('inventory_stocks as s')
                ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
                ->where('w.code', 'not like', 'REJ%')
                ->where('w.code', '!=', 'REJECT')
                ->groupBy('s.item_id')
                ->select('s.item_id', DB::raw('SUM(s.qty) as total_qty'));

            $q = DB::table('items as i')
                ->leftJoinSub($sub, 't', 't.item_id', '=', 'i.id')
                ->where('i.active', 1)
                ->where(fn ($w) => $w->whereNull('t.total_qty')->orWhere('t.total_qty', '<=', 0));

            if ($type) {
                $q->where('i.type', $type);
            }

            return $q->orderBy('i.name')
                ->limit($limit)
                ->get([
                    'i.code as item_code', 'i.name as item_name', 'i.type',
                    DB::raw('COALESCE(t.total_qty, 0) as qty'),
                ])
                ->map(fn ($r) => (array) $r)
                ->all();
        }, []);
    }

    private function poCount(array $statuses, string $col): int
    {
        return (int) $this->safe(function () use ($statuses, $col) {
            if (!Schema::hasTable('purchase_orders') || !Schema::hasColumn('purchase_orders', $col)) {
                return 0;
            }
            return DB::table('purchase_orders')
                ->whereIn($col, $statuses)
                ->when(Schema::hasColumn('purchase_orders', 'status'),
                    fn ($q) => $q->where('status', '!=', 'cancelled'))
                ->count();
        });
    }

    private function stockRequestOpen(): int
    {
        return (int) $this->safe(function () {
            if (!Schema::hasTable('stock_requests') || !Schema::hasColumn('stock_requests', 'status')) {
                return 0;
            }
            return DB::table('stock_requests')
                ->whereNotIn('status', ['completed', 'cancelled', 'void'])
                ->count();
        });
    }

    // =====================================================================
    //  UTIL
    // =====================================================================

    private function roleBucket($user): string
    {
        if (!$user) {
            return 'generic';
        }
        if (method_exists($user, 'isOwner') && $user->isOwner()) {
            return 'owner';
        }
        $role = strtolower((string) ($user->role ?? ''));
        return in_array($role, ['owner', 'admin', 'operating'], true) ? $role : 'generic';
    }

    private function greeting(): string
    {
        $h = (int) Carbon::now()->format('H');
        return match (true) {
            $h < 11 => 'Selamat pagi',
            $h < 15 => 'Selamat siang',
            $h < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }
}
