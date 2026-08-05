<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncOrdersRequest;
use App\Models\Channel;
use App\Models\ItemCostSnapshot;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdGroup;
use App\Models\MarketplaceAdItemMap;
use App\Models\MarketplaceOrder;
use App\Models\Item;
use App\Models\MarketplaceProduct;
use App\Models\MarketplacePromotion;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceSyncLog;
use App\Models\OrderFulfillment;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Marketplace\Ads\ItemHppResolver;
use App\Services\Channels\ChannelManager;
use App\Support\GmvMaxAnalytics;
use App\Services\MarketplaceIssueService;
use App\Services\MarketplaceSyncService;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MarketplaceController extends Controller
{
    public function __construct(
        protected MarketplaceSyncService $sync,
        protected MarketplaceApiGateway $gateway,
        protected OrderFulfillmentService $fulfillment,
        protected MarketplaceIssueService $issueService = new MarketplaceIssueService(),
    ) {}

    // ─── Pages ────────────────────────────────────────────────────────────────

    public function toko(): \Illuminate\View\View
    {
        return view('marketplace.toko');
    }

    public function orders(Request $request): \Illuminate\View\View
    {
        $today   = now()->toDateString();
        $weekAgo = now()->subDays(6)->toDateString();
        $filters = [
            'date_from' => $request->query('date_from', $weekAgo),
            'date_to'   => $request->query('date_to', $today),
        ];
        $isDummy = $request->boolean('dummy') && app()->environment(['local', 'testing']);

        return view('marketplace.orders', compact('filters', 'isDummy'));
    }

    public function webhookTests(Request $request): \Illuminate\View\View
    {
        $today   = now()->toDateString();
        $weekAgo = now()->subDays(6)->toDateString();
        $filters = [
            'date_from' => $request->query('date_from', $weekAgo),
            'date_to'   => $request->query('date_to', $today),
        ];
        $isDummy = $request->boolean('dummy') && app()->environment(['local', 'testing']);

        return view('marketplace.webhook-tests', compact('filters', 'isDummy'));
    }

    public function fulfillment(): \Illuminate\View\View
    {
        return view('marketplace.fulfillment');
    }

    public function fulfillmentProcess(int $id): \Illuminate\View\View
    {
        return view('marketplace.fulfillment-process', compact('id'));
    }

    public function picking(): \Illuminate\View\View
    {
        return view('marketplace.picking');
    }

    public function skuMapping(): \Illuminate\View\View
    {
        return view('marketplace.sku-mapping');
    }

    public function sync(): \Illuminate\View\View
    {
        return view('marketplace.sync');
    }

    public function settlement(): \Illuminate\View\View
    {
        return view('marketplace.settlement');
    }

    public function profit(): \Illuminate\View\View
    {
        return view('marketplace.profit');
    }

    public function incomeDetail(): \Illuminate\View\View
    {
        return view('marketplace.income-detail');
    }

    public function incomeProducts(Request $request): JsonResponse
    {
        $settlementQuery = MarketplaceOrderSettlement::with([
            'store:id,name,channel_id',
            'store.channel:id,code,name',
            'order:id,channel_order_id,order_status,ordered_at,subtotal_items,total_paid_customer',
            'order.items:id,marketplace_order_id,hpp_snapshot,qty,item_name,variant_name,model_sku,item_sku,image_url,mapping_status,internal_item_id,price',
        ]);

        if ($request->filled('store_id')) {
            $settlementQuery->where('store_id', $request->store_id);
        }
        if ($request->filled('order_date_from')) {
            $settlementQuery->whereHas('order', fn ($q) => $q->whereDate('ordered_at', '>=', $request->order_date_from));
        }
        if ($request->filled('order_date_to')) {
            $settlementQuery->whereHas('order', fn ($q) => $q->whereDate('ordered_at', '<=', $request->order_date_to));
        }
        if ($request->filled('settlement_date_from')) {
            $from = $request->settlement_date_from;
            $settlementQuery->where(function ($q) use ($from) {
                $q->whereDate('settlement_time', '>=', $from)
                  ->orWhere(function ($q2) use ($from) {
                      $q2->whereNull('settlement_time')
                         ->whereHas('order', fn ($oq) => $oq->whereDate('ordered_at', '>=', $from));
                });
            });
        }
        if ($request->filled('settlement_date_to')) {
            $to = $request->settlement_date_to;
            $settlementQuery->where(function ($q) use ($to) {
                $q->whereDate('settlement_time', '<=', $to)
                  ->orWhere(function ($q2) use ($to) {
                      $q2->whereNull('settlement_time')
                         ->whereHas('order', fn ($oq) => $oq->whereDate('ordered_at', '<=', $to));
                  });
            });
        }

        $settlements = $settlementQuery->get();
        $rows = [];
        $totalOrderCount = 0;
        $totalMapped = 0;
        $totalUnmapped = 0;
        $totalSettledOrderCount = 0;
        $totalUnsettledOrderCount = 0;

        foreach ($settlements as $settlement) {
            $order = $settlement->order;
            if (! $order) continue;
            $totalOrderCount++;
            $isSettled = ! empty($settlement->settlement_time);
            if ($isSettled) {
                $totalSettledOrderCount++;
            } else {
                $totalUnsettledOrderCount++;
            }

            $sellerDiscountOrder = (float) data_get($settlement->raw_json, 'seller_discount', 0);
            $grossOrder = (float) ($order->subtotal_items ?? $settlement->buyer_payment_amount ?? 0) - $sellerDiscountOrder;
            $finalIncome = (float) ($settlement->final_income ?? 0);
            $cogsOrder = (float) $order->items->sum(fn ($item) => (float) ($item->hpp_snapshot ?? 0) * (int) ($item->qty ?? 0));
            $status = strtoupper($order->order_status ?? '');
            $isCancelledOrReturned = in_array($status, ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);
            $isReturning = in_array($status, ['TO_RETURN', 'RETURNING']);
            $items = $order->items ?? collect();
            if ($items->isEmpty()) continue;

            foreach ($items as $item) {
                $qty = max((int) ($item->qty ?? 0), 1);
                $lineGrossBeforeSellerDiscount = (float) (($item->price ?? 0) * $qty);
                if ($lineGrossBeforeSellerDiscount <= 0 && $grossOrder > 0) {
                    $lineGrossBeforeSellerDiscount = $grossOrder / max($items->count(), 1);
                }
                $share = $grossOrder > 0 ? ($lineGrossBeforeSellerDiscount / max((float) ($order->subtotal_items ?? $grossOrder), 1)) : (1 / max($items->count(), 1));
                $voucherTokoOrder = (float) $this->settlementVoucherTokoAmount($settlement);
                $grossAfterVoucherTokoOrder = max($grossOrder - $voucherTokoOrder, 0);
                $lineGrossAfterSellerDiscount = $grossOrder * $share;
                $lineSalesAfterVoucherToko = $grossAfterVoucherTokoOrder * $share;
                $lineCogs = (float) ($item->hpp_snapshot ?? 0) * $qty;
                $estimatedNetIncomeOrder = $finalIncome;
                if ($isCancelledOrReturned || $isReturning) {
                    $estimatedNetIncomeOrder = 0;
                } elseif ($estimatedNetIncomeOrder <= 0) {
                    $estimatedNetIncomeOrder = max($grossAfterVoucherTokoOrder * 0.76, 0);
                }
                $lineIncome = $estimatedNetIncomeOrder * $share;
                $lineProfit = $lineIncome - $lineCogs;
                $sku = $item->model_sku ?: $item->item_sku ?: $item->marketplace_sku ?: '-';
                $key = $sku . '|' . ($item->variant_name ?: '-') . '|' . ($item->item_name ?: '-');

                if (! isset($rows[$key])) {
                $rows[$key] = [
                    'sku' => $sku,
                    'name' => $item->item_name ?: $item->variant_name ?: '-',
                    'variant_name' => $item->variant_name ?: '-',
                    'image_url' => $item->image_url,
                        'order_count' => 0,
                        'qty_total' => 0,
                        'gross_before_seller_discount_total' => 0,
                        'gross_after_seller_discount_total' => 0,
                        'sales_after_voucher_toko_total' => 0,
                    'buyer_paid_total' => 0,
                    'cogs_total' => 0,
                    'cogs_qty_total' => 0,
                    'income_total' => 0,
                    'income_cair_total' => 0,
                    'income_belum_cair_total' => 0,
                    'profit_total' => 0,
                    'profit_cair_total' => 0,
                    'profit_belum_cair_total' => 0,
                    'qty_cair_total' => 0,
                    'qty_belum_cair_total' => 0,
                    'mapped_count' => 0,
                    'unmapped_count' => 0,
                ];
            }

                $rows[$key]['order_count'] += 1;
                $rows[$key]['qty_total'] += $qty;
                $rows[$key]['gross_before_seller_discount_total'] += $lineGrossBeforeSellerDiscount;
                $rows[$key]['gross_after_seller_discount_total'] += $lineGrossAfterSellerDiscount;
                $rows[$key]['gross_total'] = $rows[$key]['gross_after_seller_discount_total'];
                $rows[$key]['sales_after_voucher_toko_total'] += $lineSalesAfterVoucherToko;
                $buyerPaidOrder = (float) ($settlement->order?->total_paid_customer ?? $settlement->buyer_payment_amount ?? 0);
                $rows[$key]['buyer_paid_total'] += $buyerPaidOrder * $share;
                $rows[$key]['cogs_total'] += $lineCogs;
                $rows[$key]['income_total'] += $lineIncome;
                $rows[$key]['profit_total'] += $lineProfit;
                $rows[$key]['cogs_qty_total'] += $lineCogs > 0 ? $qty : 0;
                if ($isSettled) {
                    $rows[$key]['qty_cair_total'] += $qty;
                    $rows[$key]['income_cair_total'] += $lineIncome;
                    $rows[$key]['profit_cair_total'] += $lineProfit;
                } else {
                    $rows[$key]['qty_belum_cair_total'] += $qty;
                    $rows[$key]['income_belum_cair_total'] += $lineIncome;
                    $rows[$key]['profit_belum_cair_total'] += $lineProfit;
                }
                $rows[$key]['settlement_time'] = $settlement->settlement_time?->toISOString();
                $rows[$key]['avg_selling_price'] = $rows[$key]['qty_total'] > 0
                    ? ($rows[$key]['gross_before_seller_discount_total'] / $rows[$key]['qty_total'])
                    : 0;
                $rows[$key]['avg_selling_price_after_seller_discount'] = $rows[$key]['qty_total'] > 0
                    ? ($rows[$key]['gross_after_seller_discount_total'] / $rows[$key]['qty_total'])
                    : 0;
                $rows[$key]['avg_selling_price_after_voucher_toko'] = $rows[$key]['qty_total'] > 0
                    ? ($rows[$key]['sales_after_voucher_toko_total'] / $rows[$key]['qty_total'])
                    : 0;
                $rows[$key]['buyer_paid_satuan'] = $rows[$key]['qty_total'] > 0
                    ? ($rows[$key]['buyer_paid_total'] / $rows[$key]['qty_total'])
                    : 0;
                $rows[$key]['avg_buyer_paid_satuan'] = $rows[$key]['buyer_paid_satuan'];
                if (!empty($item->internal_item_id) || ($item->mapping_status ?? null) === 'mapped') {
                    $rows[$key]['mapped_count'] += 1;
                    $totalMapped++;
                } else {
                    $rows[$key]['unmapped_count'] += 1;
                    $totalUnmapped++;
                }
            }
        }

        $rows = collect(array_values($rows))
            ->sortByDesc('qty_total')
            ->values();

        $topByProfit = $rows->first();
        $topByQty = $rows->sortByDesc('qty_total')->first();
        $topByMargin = $rows->sortByDesc(fn ($r) => (($r['sales_after_voucher_toko_total'] ?? 0) > 0 ? (($r['profit_total'] ?? 0) / $r['sales_after_voucher_toko_total']) * 100 : -999))->first();
        $unmappedOnly = $rows->filter(fn ($r) => (int) ($r['unmapped_count'] ?? 0) > 0)->count();
        $mappedOnly = $rows->filter(fn ($r) => (int) ($r['unmapped_count'] ?? 0) === 0)->count();
        $totalGrossBeforeSellerDiscount = (float) $rows->sum('gross_before_seller_discount_total');
        $totalGrossAfterSellerDiscount = (float) $rows->sum('gross_after_seller_discount_total');
        $totalSalesAfterVoucherToko = (float) $rows->sum('sales_after_voucher_toko_total');
        $totalBuyerPaid = (float) $rows->sum('buyer_paid_total');
        $totalCogs = (float) $rows->sum('cogs_total');
        $totalCogsQty = (int) $rows->sum('cogs_qty_total');
        $totalProfit = (float) $rows->sum('profit_total');
        $totalIncome = (float) $rows->sum('income_total');
        $totalIncomeCair = (float) $rows->sum('income_cair_total');
        $totalIncomeBelumCair = (float) $rows->sum('income_belum_cair_total');
        $totalProfitCair = (float) $rows->sum('profit_cair_total');
        $totalProfitBelumCair = (float) $rows->sum('profit_belum_cair_total');
        $totalQtyCair = (int) $rows->sum('qty_cair_total');
        $totalQtyBelumCair = (int) $rows->sum('qty_belum_cair_total');
        $totalQty = (int) $rows->sum('qty_total');
        $coverageBase = $totalMapped + $totalUnmapped;
        $topProfitList = $rows->take(3)->map(fn ($r) => [
            'name' => $r['name'] ?? '-',
            'value' => (float) ($r['profit_total'] ?? 0),
            'qty' => (int) ($r['qty_total'] ?? 0),
        ])->values();
        $topQtyList = $rows->sortByDesc('qty_total')->take(3)->map(fn ($r) => [
            'name' => $r['name'] ?? '-',
            'value' => (int) ($r['qty_total'] ?? 0),
            'profit' => (float) ($r['profit_total'] ?? 0),
        ])->values();
        $topMarginList = $rows
            ->sortByDesc(fn ($r) => (($r['sales_after_voucher_toko_total'] ?? 0) > 0 ? (($r['profit_total'] ?? 0) / $r['sales_after_voucher_toko_total']) * 100 : -999))
            ->take(3)
            ->map(fn ($r) => [
                'name' => $r['name'] ?? '-',
                'margin' => (($r['sales_after_voucher_toko_total'] ?? 0) > 0 ? (($r['profit_total'] ?? 0) / $r['sales_after_voucher_toko_total']) * 100 : 0),
                'profit' => (float) ($r['profit_total'] ?? 0),
            ])->values();

        return response()->json([
            'rows' => $rows,
            'meta' => [
                'total_products' => $rows->count(),
                'total_qty' => $totalQty,
                'total_profit' => $totalProfit,
                'total_income' => $totalIncome,
                'total_gross_before_seller_discount' => $totalGrossBeforeSellerDiscount,
                'total_gross_after_seller_discount' => $totalGrossAfterSellerDiscount,
                'total_gross' => $totalGrossAfterSellerDiscount,
                'total_sales_after_voucher_toko' => $totalSalesAfterVoucherToko,
                'total_buyer_paid' => $totalBuyerPaid,
                'total_cogs' => $totalCogs,
                'total_cogs_qty' => $totalCogsQty,
                'total_income_cair' => $totalIncomeCair,
                'total_income_belum_cair' => $totalIncomeBelumCair,
                'total_profit_cair' => $totalProfitCair,
                'total_profit_belum_cair' => $totalProfitBelumCair,
                'total_settled_order_count' => $totalSettledOrderCount,
                'total_unsettled_order_count' => $totalUnsettledOrderCount,
                'total_qty_cair' => $totalQtyCair,
                'total_qty_belum_cair' => $totalQtyBelumCair,
                'total_order_count' => $totalOrderCount,
                'rows_mapped' => $totalMapped,
                'rows_unmapped' => $totalUnmapped,
                'unmapped_products' => $unmappedOnly,
                'mapped_products' => $mappedOnly,
                'avg_profit_margin' => $totalGrossAfterSellerDiscount > 0 ? (($totalProfit / $totalGrossAfterSellerDiscount) * 100) : 0,
                'avg_profit_per_order' => $totalOrderCount > 0 ? ($totalProfit / $totalOrderCount) : 0,
                'avg_sales_after_voucher_toko_satuan' => $totalQty > 0 ? ($totalSalesAfterVoucherToko / $totalQty) : 0,
                'avg_buyer_paid_satuan' => $totalQty > 0 ? ($totalBuyerPaid / $totalQty) : 0,
                'avg_cogs_satuan' => $totalQty > 0 ? ($totalCogs / $totalQty) : 0,
                'avg_income_cair_satuan' => $totalQtyCair > 0 ? ($totalIncomeCair / $totalQtyCair) : 0,
                'avg_income_belum_cair_satuan' => $totalQtyBelumCair > 0 ? ($totalIncomeBelumCair / $totalQtyBelumCair) : 0,
                'sku_map_rate' => $rows->count() > 0 ? (($mappedOnly / $rows->count()) * 100) : 0,
                'sku_coverage_rate' => $coverageBase > 0 ? (($totalMapped / $coverageBase) * 100) : 0,
                'top_profit_name' => $topByProfit['name'] ?? null,
                'top_profit_value' => $topByProfit['profit_total'] ?? 0,
                'top_qty_name' => $topByQty['name'] ?? null,
                'top_qty_value' => $topByQty['qty_total'] ?? 0,
                'top_margin_name' => $topByMargin['name'] ?? null,
                'top_margin_value' => $topByMargin['sales_after_voucher_toko_total'] > 0 ? (($topByMargin['profit_total'] / $topByMargin['sales_after_voucher_toko_total']) * 100) : 0,
                'top_price_name' => $rows->sortByDesc(fn ($r) => ($r['avg_selling_price_after_voucher_toko'] ?? 0))->first()['name'] ?? null,
                'top_price_value' => $rows->sortByDesc(fn ($r) => ($r['avg_selling_price_after_voucher_toko'] ?? 0))->first()['avg_selling_price_after_voucher_toko'] ?? 0,
                'top_profit_list' => $topProfitList,
                'top_qty_list' => $topQtyList,
                'top_margin_list' => $topMarginList,
            ],
        ]);
    }

    public function analytics(Request $request): \Illuminate\View\View
    {
        $today   = now()->toDateString();
        $weekAgo = now()->subDays(29)->toDateString();
        $filters = [
            'date_from' => $request->query('date_from', $weekAgo),
            'date_to'   => $request->query('date_to', $today),
        ];
        return view('marketplace.analytics', compact('filters'));
    }

    public function ads(): \Illuminate\View\View
    {
        return view('marketplace.ads');
    }

    public function promotions(Request $request): \Illuminate\View\View
    {
        return view('marketplace.promotions', [
            'selectedStoreId' => $request->integer('store_id') ?: null,
            'selectedStatus'   => $request->query('status', 'ongoing'),
        ]);
    }

    public function promotionCreatePage(Request $request): \Illuminate\View\View
    {
        $store = $this->resolvePromotionStore($request->integer('store_id'));

        return view('marketplace.promotion-edit', [
            'store' => $store ? $this->normalizeStorePayload($store) : null,
            'discountId' => null,
            'mode' => 'create',
            'backUrl' => route('marketplace.promotions', [
                'store_id' => $store?->id,
            ]),
        ]);
    }

    public function promotionEdit(Request $request, Store $store, int $discountId): \Illuminate\View\View
    {
        $store = $this->resolvePromotionStoreBinding($store);

        return view('marketplace.promotion-edit', [
            'store' => $this->normalizeStorePayload($store),
            'discountId' => $discountId,
            'mode' => 'edit',
            'backUrl' => route('marketplace.promotions', [
                'store_id' => $store->id,
            ]),
        ]);
    }

    public function promotionsSummary(Request $request): \Illuminate\View\View
    {
        $today = now()->toDateString();

        return view('marketplace.promotions-summary', [
            'filters' => [
                'store_id'  => $request->query('store_id', 'all'),
                'status'    => $request->query('status', 'all'),
                'date_from' => $request->query('date_from', now()->subDays(29)->toDateString()),
                'date_to'   => $request->query('date_to', $today),
            ],
        ]);
    }

    public function issueCenter(): \Illuminate\View\View
    {
        return view('marketplace.issues');
    }

    public function cacheMonitor(): \Illuminate\View\View
    {
        $disk = Storage::disk('local');
        $directory = 'shipping_labels';

        $totalFiles = 0;
        $totalSizeBytes = 0;
        $expiredFiles = 0;

        if ($disk->exists($directory)) {
            $files = $disk->allFiles($directory);
            $totalFiles = count($files);

            $fourDaysAgo = Carbon::now()->subDays(4);

            foreach ($files as $file) {
                if (!str_ends_with($file, '.pdf.gz')) continue;

                $totalSizeBytes += $disk->size($file);

                $filename = basename($file);
                $filenameWithoutExt = str_replace('.pdf.gz', '', $filename);
                $parts = explode('_', $filenameWithoutExt, 2);

                if (count($parts) === 2) {
                    $storeId = $parts[0];
                    $orderSn = $parts[1];

                    // Estimasi expired
                    $order = MarketplaceOrder::where('store_id', $storeId)
                        ->where('channel_order_id', $orderSn)
                        ->first();

                    if (!$order || ($order->order_status === 'COMPLETED' && $order->updated_at < $fourDaysAgo)) {
                        $expiredFiles++;
                    }
                }
            }
        }

        return view('marketplace.cache-monitor', compact('totalFiles', 'totalSizeBytes', 'expiredFiles'));
    }

    public function runCacheCleanup()
    {
        Artisan::call('marketplace:cleanup-labels');
        $output = Artisan::output();

        return back()->with('success', nl2br(e($output)));
    }

    public function settings(): \Illuminate\View\View
    {
        $settings = [
            'marketplace_print_default_format' => \App\Models\SystemSetting::get('marketplace_print_default_format', 'THERMAL_AIR_WAYBILL'),
            'marketplace_print_branding'       => \App\Models\SystemSetting::get('marketplace_print_branding', '1'),
            'marketplace_footer_image'         => \App\Models\SystemSetting::get('marketplace_footer_image', ''),
            'marketplace_footer_greeting'      => \App\Models\SystemSetting::get('marketplace_footer_greeting', 'Terima kasih telah berbelanja!'),
            'marketplace_footer_alignment'     => \App\Models\SystemSetting::get('marketplace_footer_alignment', 'C'),
            'marketplace_footer_divider'       => \App\Models\SystemSetting::get('marketplace_footer_divider', '0'),
            'marketplace_sender_name'          => \App\Models\SystemSetting::get('marketplace_sender_name', ''),
            'marketplace_sender_phone'         => \App\Models\SystemSetting::get('marketplace_sender_phone', ''),
            'marketplace_social_accounts'      => \App\Models\SystemSetting::get('marketplace_social_accounts', '[]'),
            'marketplace_auto_sync'            => \App\Models\SystemSetting::get('marketplace_auto_sync', '0'),
            'marketplace_auto_push_stock'      => \App\Models\SystemSetting::get('marketplace_auto_push_stock', '0'),
            'marketplace_auto_process_orders'  => \App\Models\SystemSetting::get('marketplace_auto_process_orders', '0'),
            'marketplace_default_warehouse'    => \App\Models\SystemSetting::get('marketplace_default_warehouse', ''),
        ];

        $warehouses = \App\Models\Warehouse::orderBy('name')->get();

        // Ambil daftar file template greeting
        $greetingTemplates = [];
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists('templates/greetings')) {
            $files = \Illuminate\Support\Facades\Storage::disk('public')->files('templates/greetings');
            foreach ($files as $file) {
                // Return just the basename, e.g., 'template_1.png'
                $greetingTemplates[] = pathinfo($file, PATHINFO_BASENAME);
            }
        }
        sort($greetingTemplates);

        // Ambil daftar file template footer
        $footerTemplates = [];
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists('templates/footers')) {
            $files = \Illuminate\Support\Facades\Storage::disk('public')->files('templates/footers');
            foreach ($files as $file) {
                $footerTemplates[] = pathinfo($file, PATHINFO_BASENAME);
            }
        }
        sort($footerTemplates);

        return view('marketplace.settings', compact('settings', 'warehouses', 'greetingTemplates', 'footerTemplates'));
    }

    public function updateSettings(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'marketplace_print_default_format' => ['required', 'string'],
            'marketplace_print_branding'       => ['required', 'in:0,1'],
            'marketplace_footer_image'         => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'remove_footer_image'              => ['nullable', 'in:1'],
            'marketplace_footer_greeting'      => ['nullable', 'string', 'max:500'],
            'marketplace_footer_alignment'     => ['required', 'in:L,C,R'],
            'marketplace_footer_template'      => ['nullable', 'string', 'max:50'],
            'marketplace_footer_divider'       => ['nullable', 'in:0,1'],
            'marketplace_sender_name'          => ['nullable', 'string', 'max:255'],
            'marketplace_sender_phone'         => ['nullable', 'string', 'max:50'],
            'marketplace_auto_sync'            => ['required', 'in:0,1'],
            'marketplace_auto_push_stock'      => ['required', 'in:0,1'],
            'marketplace_print_greeting_card'  => ['nullable', 'in:0,1'],
            'marketplace_greeting_card_image'  => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'remove_greeting_card_image'       => ['nullable', 'in:1'],
            'marketplace_greeting_card_template' => ['nullable', 'string', 'max:50'],
            'marketplace_auto_process_orders'  => ['required', 'in:0,1'],
            'marketplace_default_warehouse'    => ['nullable', 'exists:warehouses,id'],
            'upload_template_1'                => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'upload_template_2'                => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'upload_template_3'                => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'add_greeting_template'            => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'add_footer_template'              => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
        ]);

        if ($request->has('remove_footer_image') && $request->input('remove_footer_image') == '1') {
            $oldPath = \App\Models\SystemSetting::get('marketplace_footer_image', '');
            if ($oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            \App\Models\SystemSetting::set('marketplace_footer_image', '');
            unset($data['marketplace_footer_image']);
        } elseif ($request->hasFile('marketplace_footer_image')) {
            $path = $request->file('marketplace_footer_image')->store('marketplace/footer', 'public');
            \App\Models\SystemSetting::set('marketplace_footer_image', $path);
            unset($data['marketplace_footer_image']);
        }
        unset($data['remove_footer_image']);

        if ($request->has('remove_greeting_card_image') && $request->input('remove_greeting_card_image') == '1') {
            $oldPath = \App\Models\SystemSetting::get('marketplace_greeting_card_image', '');
            if ($oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            \App\Models\SystemSetting::set('marketplace_greeting_card_image', '');
            unset($data['marketplace_greeting_card_image']);
        } elseif ($request->hasFile('marketplace_greeting_card_image')) {
            $path = $request->file('marketplace_greeting_card_image')->store('marketplace/greetings', 'public');
            \App\Models\SystemSetting::set('marketplace_greeting_card_image', $path);
            unset($data['marketplace_greeting_card_image']);
        }
        unset($data['remove_greeting_card_image']);

        for ($i = 1; $i <= 3; $i++) {
            $field = 'upload_template_' . $i;
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $ext = $file->getClientOriginalExtension() ?: 'png';
                $filename = 'template_' . $i . '.' . $ext;
                // Delete existing ones with different extensions just in case
                foreach (['png', 'jpg', 'jpeg', 'pdf'] as $e) {
                    $old = 'templates/greetings/template_' . $i . '.' . $e;
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($old)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
                    }
                }
                $file->storeAs('templates/greetings', $filename, 'public');
            }
            unset($data[$field]);
        }

        if ($request->hasFile('add_greeting_template')) {
            $file = $request->file('add_greeting_template');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $baseName = 'custom_' . time();
            $filename = $baseName . '.' . $ext;
            $file->storeAs('templates/greetings', $filename, 'public');
            unset($data['add_greeting_template']);
        }

        if ($request->hasFile('add_footer_template')) {
            $file = $request->file('add_footer_template');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $baseName = 'footer_' . time();
            $filename = $baseName . '.' . $ext;
            $file->storeAs('templates/footers', $filename, 'public');
            unset($data['add_footer_template']);
        }

        foreach ($data as $key => $value) {
            \App\Models\SystemSetting::set($key, $value);
        }

        $platforms = $request->input('social_platforms', []);
        $usernames = $request->input('social_usernames', []);

        $accounts = [];
        for ($i = 0; $i < count($platforms); $i++) {
            if (!empty(trim($usernames[$i] ?? ''))) {
                $accounts[] = [
                    'platform' => $platforms[$i],
                    'username' => trim($usernames[$i])
                ];
            }
        }

        \App\Models\SystemSetting::set('marketplace_social_accounts', json_encode($accounts));

        return redirect()->route('marketplace.settings')->with('success', 'Pengaturan berhasil disimpan');
    }

    public function deleteTemplate(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'type' => 'required|in:greeting,footer',
            'filename' => 'required|string',
        ]);

        $type = $request->input('type');
        $filename = $request->input('filename');
        $folder = $type === 'greeting' ? 'templates/greetings' : 'templates/footers';
        $path = $folder . '/' . $filename;

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }

        return redirect()->route('marketplace.settings')->with('success', 'Template berhasil dihapus!');
    }

    public function previewSettingsPdf(\Illuminate\Http\Request $request)
    {
        $config = $request->except(['marketplace_footer_image', 'social_platforms', 'social_usernames']);

        $platforms = $request->input('social_platforms', []);
        $usernames = $request->input('social_usernames', []);

        $accounts = [];
        for ($i = 0; $i < count($platforms); $i++) {
            if (!empty(trim($usernames[$i] ?? ''))) {
                $accounts[] = [
                    'platform' => $platforms[$i],
                    'username' => trim($usernames[$i])
                ];
            }
        }
        $config['marketplace_social_accounts'] = json_encode($accounts);

        $tmpImgPath = null;
        if ($request->hasFile('marketplace_footer_image')) {
            $path = $request->file('marketplace_footer_image')->store('marketplace/footer/tmp', 'public');
            $config['marketplace_footer_image'] = $path;
            $tmpImgPath = storage_path('app/public/' . $path);
        } else {
            // Keep existing if any
            if ($request->input('remove_footer_image') == '1') {
                $config['marketplace_footer_image'] = '';
            } else {
                $config['marketplace_footer_image'] = \App\Models\SystemSetting::get('marketplace_footer_image', '');
            }
        }

        // Generate a fake Shopee AWB using FPDF
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->AddPage('P', [100, 150]);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'MARKETPLACE AWB MOCKUP', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, 'No. Pesanan: 260709908SV5CN', 0, 1, 'C');

        $pdf->SetFillColor(0, 0, 0);
        for($i = 0; $i < 60; $i++) {
            $w = rand(1, 3);
            $pdf->Rect(15 + ($i * 1.2), 30, $w * 0.5, 15, 'F');
        }

        $pdf->SetY(50);
        $pdf->Cell(0, 10, '==================================================', 0, 1, 'C');
        $rawPdf = $pdf->Output('S');

        $overlayService = new \App\Services\ShippingLabelOverlayService();
        $finalPdf = $overlayService->overlayPdfContent($rawPdf, $config);

        if ($tmpImgPath && file_exists($tmpImgPath)) {
            @unlink($tmpImgPath);
        }

        return response($finalPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"'
        ]);
    }

    public function printSampleGreetingCard(\Illuminate\Http\Request $request)
    {
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->AddPage('P', [100, 150]);

        $settings = \App\Models\SystemSetting::all()->pluck('setting_value', 'setting_key')->toArray();

        $greetingImageFull = null;
        $customGreetingImg = $settings['marketplace_greeting_card_image'] ?? '';

        if (!empty($customGreetingImg) && file_exists(storage_path('app/public/' . $customGreetingImg))) {
            $greetingImageFull = storage_path('app/public/' . $customGreetingImg);
        } else {
            $gTpl = $request->query('template', $settings['marketplace_greeting_card_template'] ?? 'template_1.png');
            if ($gTpl !== 'none') {
                if (in_array($gTpl, ['1', '2', '3'])) {
                    $gTpl = 'template_' . $gTpl . '.png';
                }
                $tplPath = storage_path('app/public/templates/greetings/' . $gTpl);
                if (file_exists($tplPath)) {
                    $greetingImageFull = $tplPath;
                }
            }
        }

        if ($greetingImageFull) {
            $ext = strtolower(pathinfo($greetingImageFull, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                try {
                    $pdf->setSourceFile($greetingImageFull);
                    $gTplId = $pdf->importPage(1);
                    $pdf->useTemplate($gTplId, 0, 0, 100, 150);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to load PDF greeting card sample: " . $e->getMessage());
                }
            } else {
                $m = 4;
                $pdf->Image($greetingImageFull, $m, $m, 100 - ($m * 2), 150 - ($m * 2));
            }
        } else {
            $pdf->SetFont('Helvetica', 'B', 14);
            $pdf->SetXY(0, 70);
            $pdf->Cell(100, 10, 'Thank you for your order!', 0, 1, 'C');
        }

        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Sample_Greeting_Card.pdf"'
        ]);
    }

    // ─── API ──────────────────────────────────────────────────────────────────

    public function bootstrap(): JsonResponse
    {
        $channels = $this->sync->bootstrapChannels();

        return response()->json([
            'message'  => 'Channel default berhasil dibuat.',
            'channels' => $channels,
        ]);
    }

    public function channels(): JsonResponse
    {
        return response()->json(
            Channel::withCount('stores')->orderBy('name')->get()
        );
    }

    public function stores(): JsonResponse
    {
        $stores = Store::with(['channel', 'defaultWarehouse'])->latest()->get()->map(fn (Store $store) => [
            'id'                   => $store->id,
            'channel_id'           => $store->channel_id,
            'code'                 => $store->code,
            'name'                 => $store->name,
            'external_shop_id'     => $store->external_shop_id,
            'region'               => $store->region,
            'status'               => $store->status,
            'is_active'            => (bool) $store->is_active,
            'connection_status'    => $store->connection_status,
            'token_expires_at'     => $store->token_expires_at?->toISOString(),
            'last_synced_at'       => $store->last_synced_at?->toISOString(),
            'meta'                 => $store->meta,
            'default_warehouse_id' => $store->default_warehouse_id,
            'default_warehouse'    => $store->defaultWarehouse ? [
                'id'   => $store->defaultWarehouse->id,
                'code' => $store->defaultWarehouse->code,
                'name' => $store->defaultWarehouse->name,
            ] : null,
            'channel' => $store->channel ? [
                'id'     => $store->channel->id,
                'code'   => $store->channel->code,
                'name'   => $store->channel->name,
                'status' => $store->channel->status,
            ] : null,
        ]);

        return response()->json($stores);
    }

    public function storeStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel_id'           => ['required', 'integer', 'exists:channels,id'],
            'name'                 => ['required', 'string', 'min:1', 'max:120'],
            'code'                 => ['nullable', 'string', 'max:80'],
            'region'               => ['nullable', 'string', 'max:32'],
            'default_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);

        $channel = Channel::findOrFail((int) $data['channel_id']);
        $requestedCode = trim((string) ($data['code'] ?? ''));
        $baseCode = Str::upper(Str::slug($requestedCode !== '' ? $requestedCode : $data['name'], '-'));
        $baseCode = Str::limit($baseCode !== '' ? $baseCode : 'STORE', 68, '');

        $code = $baseCode;
        $suffix = 2;
        while (Store::where('code', $code)->exists()) {
            $suffixText = '-' . $suffix++;
            $code = Str::limit($baseCode, 80 - strlen($suffixText), '') . $suffixText;
        }

        $store = Store::create([
            'code'                 => $code,
            'name'                 => trim($data['name']),
            'channel_id'           => $channel->id,
            'region'               => $data['region'] ?? null,
            'default_warehouse_id' => $data['default_warehouse_id'] ?? null,
            'status'               => 'active',
            'is_active'            => true,
        ]);

        return response()->json([
            'message' => 'Toko berhasil ditambahkan. Hubungkan akun marketplace dari menu toko untuk mulai sinkronisasi.',
            'store' => $store->load('channel'),
        ], 201);
    }

    public function shopInfo(Store $store): JsonResponse
    {
        return response()->json(
            $this->gateway->getShopInfo($store)
        );
    }

    public function promotionsIndex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id'         => ['nullable', 'integer', 'exists:stores,id'],
            'status'           => ['nullable', 'string', 'in:all,ongoing,upcoming,ended'],
            'page_no'          => ['nullable', 'integer', 'min:1'],
            'page_size'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'update_time_from' => ['nullable', 'integer'],
            'update_time_to'   => ['nullable', 'integer'],
        ]);

        $store = $this->resolvePromotionStore($data['store_id'] ?? null);

        if (! $store) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada toko marketplace yang bisa dipakai untuk promosi.',
            ], 422);
        }

        $status = $data['status'] ?? 'ongoing';
        $pageNo = (int) ($data['page_no'] ?? 1);
        $pageSize = (int) ($data['page_size'] ?? 100);
        $updateTimeFrom = $data['update_time_from'] ?? null;
        $updateTimeTo = $data['update_time_to'] ?? null;

        try {
                        $responses = [];
            $promotions = [];
            $statuses = $status === 'all' ? ['ongoing', 'upcoming', 'ended'] : [$status];

            foreach ($statuses as $statusItem) {
                $response = $this->gateway->getDiscountList(
                    $store,
                    $statusItem,
                    $pageNo,
                    $pageSize,
                    $updateTimeFrom,
                    $updateTimeTo
                );

                if (! empty($response['error'])) {
                    $message = $this->promotionAuthErrorMessage($response, 'Gagal memuat data promosi.');
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'code'    => $this->isShopeeAuthError($response) ? 'auth_required' : null,
                        'store'   => $this->normalizeStorePayload($store),
                        'raw'     => $response,
                    ], 422);
                }

                $responses[$statusItem] = $response;

                foreach ($this->extractPromotionList($response) as $promotion) {
                    $promotions[] = $this->normalizePromotionCampaign($promotion);
                }
            }

            if ($status === 'all') {
                $promotions = collect($promotions)
                    ->unique('discount_id')
                    ->sortByDesc(fn ($item) => $item['start_time'] ?? 0)
                    ->values()
                    ->all();
            }

            $promotions = $this->enrichPromotionCampaignsWithLocalPreview($store, $promotions);

            return response()->json([
                'success'    => true,
                'message'    => 'Daftar promosi berhasil dimuat.',
                'store'      => $this->normalizeStorePayload($store),
                'status'     => $status,
                'promotions' => $promotions,
                'count'      => count($promotions),
                'raw'        => $status === 'all' ? $responses : ($responses[$status] ?? null),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'store'   => $this->normalizeStorePayload($store),
            ], 500);
        }
    }

    public function promotionsSummaryData(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id'  => ['nullable', 'string'],
            'status'    => ['nullable', 'string', 'in:all,ongoing,upcoming,ended,suspended'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $stores = $this->resolvePromotionStores(
            ($data['store_id'] ?? 'all') !== 'all' ? (int) $data['store_id'] : null
        );

        if ($stores->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada toko Shopee yang bisa dipakai untuk ringkasan promosi.',
            ], 422);
        }

        $status = $data['status'] ?? 'all';
        $statuses = $status === 'all' ? ['ongoing', 'upcoming', 'ended', 'suspended'] : [$status];
        $dateFromTs = ! empty($data['date_from'])
            ? Carbon::parse($data['date_from'])->startOfDay()->timestamp
            : null;
        $dateToTs = ! empty($data['date_to'])
            ? Carbon::parse($data['date_to'])->endOfDay()->timestamp
            : null;

        $rows = [];
        $storeSummaries = [];
        $totals = [
            'stores' => 0,
            'promotions' => 0,
            'ongoing' => 0,
            'upcoming' => 0,
            'ended' => 0,
            'suspended' => 0,
            'items' => 0,
        ];

        try {
            foreach ($stores as $store) {
                                $storeRows = [];

                foreach ($statuses as $statusItem) {
                    $response = $this->gateway->getDiscountList($store, $statusItem, 1, 100, null, null);

                    if (! empty($response['error'])) {
                        $message = $this->promotionAuthErrorMessage($response, 'Gagal memuat ringkasan promosi.');
                        return response()->json([
                            'success' => false,
                            'message' => $message,
                            'code'    => $this->isShopeeAuthError($response) ? 'auth_required' : null,
                            'store'   => $this->normalizeStorePayload($store),
                            'raw'     => $response,
                        ], 422);
                    }

                    foreach ($this->extractPromotionList($response) as $promotion) {
                        $normalized = $this->normalizePromotionCampaign($promotion);

                        if (! $this->promotionMatchesDateRange($normalized, $dateFromTs, $dateToTs)) {
                            continue;
                        }

                        $row = [
                            'store' => $this->normalizeStorePayload($store),
                            'store_label' => $this->formatStoreLabel($store),
                            'status_key' => strtolower((string) ($normalized['discount_status'] ?? '')),
                            'status_label' => $normalized['status_label'],
                            'schedule_label' => $this->formatPromotionSchedule($normalized['start_time'], $normalized['end_time']),
                        ] + $normalized;

                        $rows[] = $row;
                        $storeRows[] = $row;
                    }
                }

                $storeSummary = $this->buildPromotionStoreSummary($store, $storeRows);
                $storeSummaries[] = $storeSummary;

                $totals['stores'] += 1;
                $totals['promotions'] += $storeSummary['promotions'];
                $totals['ongoing'] += $storeSummary['ongoing'];
                $totals['upcoming'] += $storeSummary['upcoming'];
                $totals['ended'] += $storeSummary['ended'];
                $totals['suspended'] += $storeSummary['suspended'];
                $totals['items'] += $storeSummary['items'];
            }

            usort($rows, function ($a, $b) {
                $storeCmp = strcmp((string) ($a['store_label'] ?? ''), (string) ($b['store_label'] ?? ''));
                if ($storeCmp !== 0) {
                    return $storeCmp;
                }

                return ((int) ($b['start_time'] ?? 0)) <=> ((int) ($a['start_time'] ?? 0));
            });

            usort($storeSummaries, function ($a, $b) {
                return strcmp((string) ($a['store']['name'] ?? ''), (string) ($b['store']['name'] ?? ''));
            });

            return response()->json([
                'success' => true,
                'message' => 'Ringkasan promosi berhasil dimuat.',
                'filters' => [
                    'store_id'  => $data['store_id'] ?? 'all',
                    'status'    => $status,
                    'date_from' => $data['date_from'] ?? null,
                    'date_to'   => $data['date_to'] ?? null,
                ],
                'rows' => $rows,
                'store_summaries' => $storeSummaries,
                'totals' => $totals,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function promotionDetail(Request $request, Store $store, int $discountId): JsonResponse
    {
        $store = $this->resolvePromotionStoreBinding($store);
        $pageNo = max(1, (int) $request->input('page_no', 1));
        $pageSize = min(100, max(1, (int) $request->input('page_size', 50)));
        $forceRefresh = $request->boolean('refresh');

        try {
            if (! $forceRefresh) {
                $cached = $this->getCachedPromotionDetail($store, $discountId);
                if ($cached) {
                    return response()->json([
                        'success'   => true,
                        'message'   => 'Detail promosi dimuat dari cache lokal.',
                        'store'     => $this->normalizeStorePayload($store),
                        'promotion' => $cached['promotion'],
                        'cached'    => true,
                        'cached_at' => $cached['cached_at'],
                    ]);
                }
            }

            $response = $this->gateway->getDiscount($store, $discountId, $pageNo, $pageSize);

            if (! empty($response['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? $response['error'] ?? 'Gagal memuat detail promosi.',
                    'store'   => $this->normalizeStorePayload($store),
                    'raw'     => $response,
                ], 422);
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Detail promosi berhasil dimuat.',
                'store'     => $this->normalizeStorePayload($store),
                'promotion' => $this->storePromotionDetailCache(
                    $store,
                    $discountId,
                    $this->normalizePromotionDetail($response, $store, $discountId)
                ),
                'cached'    => false,
                'raw'       => $response,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'store'   => $this->normalizeStorePayload($store),
            ], 500);
        }
    }

    public function promotionCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id'      => ['required', 'integer', 'exists:stores,id'],
            'discount_name' => ['required', 'string', 'max:255'],
            'start_time'    => ['required', 'integer'],
            'end_time'      => ['required', 'integer', 'gte:start_time'],
            'item_list'     => ['nullable'],
            'duplicate_from_discount_id' => ['nullable', 'integer'],
        ]);

        $store = Store::with('channel')->findOrFail((int) $data['store_id']);
        $itemList = $this->normalizePromotionItemList($data['item_list'] ?? []);

        try {
                        $createResponse = $this->gateway->addDiscount(
                $store,
                $data['discount_name'],
                (int) $data['start_time'],
                (int) $data['end_time']
            );

            if (! empty($createResponse['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $createResponse['message'] ?? $createResponse['error'] ?? 'Gagal membuat promosi.',
                    'store'   => $this->normalizeStorePayload($store),
                    'raw'     => $createResponse,
                ], 422);
            }

            $discountId = (int) data_get($createResponse, 'response.discount_id', 0);
            $itemsResponse = null;
            $recordStatus = $this->inferPromotionStatus((int) $data['start_time'], (int) $data['end_time']);

            if ($discountId && ! empty($itemList)) {
                $itemsResponse = $this->gateway->addDiscountItem($store, $discountId, $itemList);
            }

            $this->persistPromotionRecord($store, $discountId, [
                'source_discount_id' => $data['duplicate_from_discount_id'] ?? null,
                'discount_name' => $data['discount_name'],
                'discount_status' => $recordStatus,
                'sync_status' => empty($itemsResponse['error']) ? 'synced' : 'partial',
                'sync_error' => ! empty($itemsResponse['error'])
                    ? ($itemsResponse['message'] ?? $itemsResponse['error'] ?? null)
                    : null,
                'start_time' => (int) $data['start_time'],
                'end_time' => (int) $data['end_time'],
                'item_count' => count($itemList),
                'item_list_json' => $itemList,
                'request_payload' => array_merge($data, [
                    'store_id' => (int) $data['store_id'],
                    'item_list' => $itemList,
                ]),
                'create_response' => $createResponse,
                'items_response' => $itemsResponse,
                'detail_cache_json' => null,
                'detail_cached_at' => null,
                'raw_json' => [
                    'create' => $createResponse,
                    'items' => $itemsResponse,
                ],
            ]);

            if (! empty($itemsResponse['error'])) {
                return response()->json([
                    'success'     => false,
                    'message'     => $itemsResponse['message'] ?? $itemsResponse['error'] ?? 'Promosi dibuat, tetapi item gagal ditambahkan.',
                    'store'       => $this->normalizeStorePayload($store),
                    'discount_id' => $discountId,
                    'create'      => $createResponse,
                    'items'       => $itemsResponse,
                ], 422);
            }

            return response()->json([
                'success'     => true,
                'message'     => $discountId && ! empty($itemList)
                    ? 'Promosi berhasil dibuat dan item sudah ditambahkan.'
                    : 'Promosi berhasil dibuat.',
                'store'       => $this->normalizeStorePayload($store),
                'discount_id' => $discountId,
                'create'      => $createResponse,
                'items'       => $itemsResponse,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'store'   => $this->normalizeStorePayload($store),
            ], 500);
        }
    }

    public function promotionUpdate(Request $request, Store $store, int $discountId): JsonResponse
    {
        $store = $this->resolvePromotionStoreBinding($store);
        $data = $request->validate([
            'discount_name' => ['nullable', 'string', 'max:255'],
            'start_time'    => ['nullable', 'integer'],
            'end_time'      => ['nullable', 'integer'],
            'item_list'     => ['nullable'],
        ]);

        $itemList = $this->normalizePromotionItemList($data['item_list'] ?? []);
        $currentRecord = MarketplacePromotion::query()
            ->where('store_id', $store->id)
            ->where('discount_id', $discountId)
            ->first();
        $currentStatus = strtolower((string) ($currentRecord->discount_status ?? ''));
        $isOngoing = $currentStatus === 'ongoing';
        $startTime = $isOngoing ? null : (array_key_exists('start_time', $data) ? (int) $data['start_time'] : null);
        $endTime = array_key_exists('end_time', $data) ? (int) $data['end_time'] : null;
        $shouldUpdateMeta = $request->filled('discount_name')
            || (! $isOngoing && $request->filled('start_time'))
            || $request->filled('end_time');

        if (
            ! $request->filled('discount_name')
            && ! $request->filled('start_time')
            && ! $request->filled('end_time')
            && empty($itemList)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada perubahan yang dikirim.',
                'store'   => $this->normalizeStorePayload($store),
            ], 422);
        }

        try {
                        $updateResponse = null;
            $itemsResponse = null;

            if ($shouldUpdateMeta) {
                $updateResponse = $this->gateway->updateDiscount(
                    $store,
                    $discountId,
                    $data['discount_name'] ?? null,
                    $startTime,
                    $endTime
                );

                if (! empty($updateResponse['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $updateResponse['message'] ?? $updateResponse['error'] ?? 'Gagal memperbarui promosi.',
                        'store'   => $this->normalizeStorePayload($store),
                        'raw'     => $updateResponse,
                    ], 422);
                }
            }

            if (! empty($itemList)) {
                $itemsResponse = $this->gateway->updateDiscountItem($store, $discountId, $itemList);

                if (! empty($itemsResponse['error'])) {
                    $this->persistPromotionRecord($store, $discountId, [
                        'discount_name' => $data['discount_name'] ?? null,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'item_count' => count($itemList),
                        'item_list_json' => $itemList,
                        'request_payload' => array_merge($data, [
                            'store_id' => $store->id,
                            'item_list' => $itemList,
                        ]),
                        'update_response' => $updateResponse,
                        'items_response' => $itemsResponse,
                        'detail_cache_json' => null,
                        'detail_cached_at' => null,
                        'raw_json' => [
                            'update' => $updateResponse,
                            'items' => $itemsResponse,
                        ],
                        'sync_status' => 'partial',
                        'sync_error' => $itemsResponse['message'] ?? $itemsResponse['error'] ?? null,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => $itemsResponse['message'] ?? $itemsResponse['error'] ?? 'Promosi diperbarui, tetapi item gagal disimpan.',
                        'store'   => $this->normalizeStorePayload($store),
                        'update'  => $updateResponse,
                        'items'   => $itemsResponse,
                    ], 422);
                }
            }

            $this->persistPromotionRecord($store, $discountId, [
                'discount_name' => $data['discount_name'] ?? null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'item_count' => ! empty($itemList) ? count($itemList) : null,
                'item_list_json' => ! empty($itemList) ? $itemList : null,
                'request_payload' => array_merge($data, [
                    'store_id' => $store->id,
                    'item_list' => $itemList,
                ]),
                'update_response' => $updateResponse,
                'items_response' => $itemsResponse,
                'detail_cache_json' => null,
                'detail_cached_at' => null,
                'raw_json' => [
                    'update' => $updateResponse,
                    'items' => $itemsResponse,
                ],
                'sync_status' => 'synced',
                'sync_error' => null,
            ]);

            if (($request->filled('start_time') && ! $isOngoing) || $request->filled('end_time')) {
                $this->persistPromotionRecord($store, $discountId, [
                    'discount_status' => $this->inferPromotionStatus(
                        $startTime,
                        $endTime
                    ),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Promosi berhasil diperbarui.',
                'store'   => $this->normalizeStorePayload($store),
                'update'  => $updateResponse,
                'items'   => $itemsResponse,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'store'   => $this->normalizeStorePayload($store),
            ], 500);
        }
    }

    public function promotionEnd(Store $store, int $discountId): JsonResponse
    {
        $store = $this->resolvePromotionStoreBinding($store);
        try {
            $response = $this->gateway->endDiscount($store, $discountId);

            if (! empty($response['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? $response['error'] ?? 'Gagal menutup promosi.',
                    'store'   => $this->normalizeStorePayload($store),
                    'raw'     => $response,
                ], 422);
            }

            $this->persistPromotionRecord($store, $discountId, [
                'discount_status' => 'ended',
                'ended_at' => now(),
                'sync_status' => 'ended',
                'sync_error' => null,
                'end_response' => $response,
                'detail_cache_json' => null,
                'detail_cached_at' => null,
                'raw_json' => [
                    'end' => $response,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promosi berhasil ditutup.',
                'store'   => $this->normalizeStorePayload($store),
                'raw'     => $response,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'store'   => $this->normalizeStorePayload($store),
            ], 500);
        }
    }

    public function promotionActivate(Request $request, Store $store, int $discountId): JsonResponse
    {
        $store = $this->resolvePromotionStoreBinding($store);
        $data = $request->validate([
            'current_status' => ['nullable', 'string', 'in:upcoming,ongoing,ended,suspended'],
        ]);

        try {
            $response = $this->gateway->updateDiscount(
                $store,
                $discountId,
                null,
                now()->timestamp,
                null
            );

            if (! empty($response['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? $response['error'] ?? 'Gagal mengaktifkan promosi.',
                    'store'   => $this->normalizeStorePayload($store),
                    'raw'     => $response,
                ], 422);
            }

            $this->persistPromotionRecord($store, $discountId, [
                'discount_status' => 'ongoing',
                'start_time' => now()->timestamp,
                'sync_status' => 'synced',
                'sync_error' => null,
                'update_response' => $response,
                'detail_cache_json' => null,
                'detail_cached_at' => null,
                'raw_json' => [
                    'activate' => $response,
                    'current_status' => $data['current_status'] ?? null,
                ],
            ]);

            DB::table('marketplace_promotions')
                ->where('store_id', $store->id)
                ->where('discount_id', $discountId)
                ->update([
                    'discount_status' => 'ongoing',
                    'start_time' => now()->timestamp,
                    'sync_status' => 'synced',
                    'sync_error' => null,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Promosi berhasil diaktifkan sekarang.',
                'store'   => $this->normalizeStorePayload($store),
                'raw'     => $response,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'store'   => $this->normalizeStorePayload($store),
            ], 500);
        }
    }

    public function promotionDeactivate(Request $request, Store $store, int $discountId): JsonResponse
    {
        $store = $this->resolvePromotionStoreBinding($store);
        $data = $request->validate([
            'current_status' => ['required', 'string', 'in:upcoming,ongoing'],
        ]);

        try {
            if ($data['current_status'] === 'ongoing') {
                $response = $this->gateway->endDiscount($store, $discountId);
            } else {
                $response = $this->gateway->deleteDiscount($store, $discountId);
            }

            if (! empty($response['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? $response['error'] ?? 'Gagal menonaktifkan promosi.',
                    'store'   => $this->normalizeStorePayload($store),
                    'raw'     => $response,
                ], 422);
            }

            $this->persistPromotionRecord($store, $discountId, [
                'discount_status' => $data['current_status'] === 'ongoing' ? 'ended' : 'deleted',
                'ended_at' => now(),
                'sync_status' => $data['current_status'] === 'ongoing' ? 'ended' : 'deleted',
                'sync_error' => null,
                'end_response' => $data['current_status'] === 'ongoing' ? $response : null,
                'delete_response' => $data['current_status'] === 'ongoing' ? null : $response,
                'detail_cache_json' => null,
                'detail_cached_at' => null,
                'raw_json' => [
                    'deactivate' => $response,
                    'current_status' => $data['current_status'],
                ],
            ]);

            DB::table('marketplace_promotions')
                ->where('store_id', $store->id)
                ->where('discount_id', $discountId)
                ->update([
                    'discount_status' => $data['current_status'] === 'ongoing' ? 'ended' : 'deleted',
                    'ended_at' => now(),
                    'sync_status' => $data['current_status'] === 'ongoing' ? 'ended' : 'deleted',
                    'sync_error' => null,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => $data['current_status'] === 'ongoing'
                    ? 'Promosi berhasil dinonaktifkan.'
                    : 'Promosi yang belum aktif berhasil dihapus.',
                'store'   => $this->normalizeStorePayload($store),
                'raw'     => $response,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'store'   => $this->normalizeStorePayload($store),
            ], 500);
        }
    }

    public function promotionDelete(Store $store, int $discountId): JsonResponse
    {
        $store = $this->resolvePromotionStoreBinding($store);
        try {
            $response = $this->gateway->deleteDiscount($store, $discountId);

            if (! empty($response['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? $response['error'] ?? 'Gagal menghapus promosi.',
                    'store'   => $this->normalizeStorePayload($store),
                    'raw'     => $response,
                ], 422);
            }

            $this->persistPromotionRecord($store, $discountId, [
                'discount_status' => 'deleted',
                'sync_status' => 'deleted',
                'sync_error' => null,
                'delete_response' => $response,
                'detail_cache_json' => null,
                'detail_cached_at' => null,
                'raw_json' => [
                    'delete' => $response,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promosi berhasil dihapus.',
                'store'   => $this->normalizeStorePayload($store),
                'raw'     => $response,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'store'   => $this->normalizeStorePayload($store),
            ], 500);
        }
    }

    private function clearPromotionDetailCache(Store $store, int $discountId): void
    {
        $this->persistPromotionRecord($store, $discountId, [
            'detail_cache_json' => null,
            'detail_cached_at' => null,
        ]);
    }

    public function promotionDeleteItem(Request $request, Store $store, int $discountId): JsonResponse
    {
        $store = $this->resolvePromotionStoreBinding($store);
        $data = $request->validate([
            'item_id'  => ['required', 'integer'],
            'model_id' => ['nullable', 'integer'],
        ]);

        try {
            $response = $this->gateway->deleteDiscountItem(
                $store,
                $discountId,
                (int) $data['item_id'],
                (int) ($data['model_id'] ?? 0)
            );

            if (! empty($response['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? $response['error'] ?? 'Gagal menghapus item dari promosi.',
                    'store'   => $this->normalizeStorePayload($store),
                    'raw'     => $response,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil dihapus dari promosi.',
                'store'   => $this->normalizeStorePayload($store),
                'raw'     => $response,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'store'   => $this->normalizeStorePayload($store),
            ], 500);
        }
    }

    private function persistPromotionRecord(Store $store, int $discountId, array $attributes): void
    {
        if ($discountId <= 0) {
            return;
        }

        $store->loadMissing('channel');
        $record = MarketplacePromotion::firstOrNew([
            'store_id' => $store->id,
            'discount_id' => $discountId,
        ]);

        $record->store_id = $store->id;
        $record->channel_code = strtolower((string) ($store->channel?->code ?? '')) ?: null;
        $record->discount_id = $discountId;

        if (array_key_exists('source_discount_id', $attributes)) {
            $record->source_discount_id = $attributes['source_discount_id'] ? (int) $attributes['source_discount_id'] : null;
        }

        if (array_key_exists('discount_name', $attributes)) {
            $record->discount_name = $attributes['discount_name'];
        }

        if (array_key_exists('discount_status', $attributes)) {
            $record->discount_status = $attributes['discount_status'];
        }

        if (array_key_exists('sync_status', $attributes)) {
            $record->sync_status = $attributes['sync_status'];
        }

        if (array_key_exists('sync_error', $attributes)) {
            $record->sync_error = $attributes['sync_error'];
        }

        if (array_key_exists('start_time', $attributes)) {
            $record->start_time = $attributes['start_time'] !== null ? (int) $attributes['start_time'] : null;
        }

        if (array_key_exists('end_time', $attributes)) {
            $record->end_time = $attributes['end_time'] !== null ? (int) $attributes['end_time'] : null;
        }

        if (array_key_exists('item_count', $attributes) && $attributes['item_count'] !== null) {
            $record->item_count = (int) $attributes['item_count'];
        }

        if (array_key_exists('item_list_json', $attributes) && $attributes['item_list_json'] !== null) {
            $record->item_list_json = $attributes['item_list_json'];
        }

        if (array_key_exists('request_payload', $attributes)) {
            $record->request_payload = $attributes['request_payload'];
        }

        if (array_key_exists('create_response', $attributes)) {
            $record->create_response = $attributes['create_response'];
        }

        if (array_key_exists('items_response', $attributes)) {
            $record->items_response = $attributes['items_response'];
        }

        if (array_key_exists('update_response', $attributes)) {
            $record->update_response = $attributes['update_response'];
        }

        if (array_key_exists('end_response', $attributes)) {
            $record->end_response = $attributes['end_response'];
        }

        if (array_key_exists('delete_response', $attributes)) {
            $record->delete_response = $attributes['delete_response'];
        }

        if (array_key_exists('detail_cache_json', $attributes)) {
            $record->detail_cache_json = $attributes['detail_cache_json'];
        }

        if (array_key_exists('detail_cached_at', $attributes)) {
            $record->detail_cached_at = $attributes['detail_cached_at'] ? Carbon::parse($attributes['detail_cached_at']) : null;
        }

        if (array_key_exists('raw_json', $attributes)) {
            $record->raw_json = $attributes['raw_json'];
        }

        if (array_key_exists('synced_at', $attributes)) {
            $record->synced_at = $attributes['synced_at'] ? Carbon::parse($attributes['synced_at']) : now();
        } else {
            $record->synced_at = now();
        }

        if (array_key_exists('ended_at', $attributes)) {
            $record->ended_at = $attributes['ended_at'] ? Carbon::parse($attributes['ended_at']) : null;
        }

        $record->save();
    }

    private function getCachedPromotionDetail(Store $store, int $discountId): ?array
    {
        $promotion = MarketplacePromotion::query()
            ->where('store_id', $store->id)
            ->where('discount_id', $discountId)
            ->first();

        if (! $promotion || empty($promotion->detail_cache_json) || ! $promotion->detail_cached_at) {
            return null;
        }

        $maxAgeMinutes = (int) config('shopee.promotion_detail_cache_minutes', 15);
        if ($promotion->detail_cached_at->lt(now()->subMinutes($maxAgeMinutes))) {
            return null;
        }

        $cached = is_array($promotion->detail_cache_json) ? $promotion->detail_cache_json : [];
        if (empty($cached)) {
            return null;
        }

        return [
            'promotion' => $cached,
            'cached_at' => $promotion->detail_cached_at->toISOString(),
        ];
    }

    private function storePromotionDetailCache(Store $store, int $discountId, array $promotion): array
    {
        if ($discountId <= 0) {
            return $promotion;
        }

        $record = MarketplacePromotion::firstOrNew([
            'store_id' => $store->id,
            'discount_id' => $discountId,
        ]);

        $record->store_id = $store->id;
        $record->discount_id = $discountId;
        $record->detail_cache_json = $promotion;
        $record->detail_cached_at = now();
        $record->save();

        return $promotion;
    }

    private function inferPromotionStatus(?int $startTime, ?int $endTime): string
    {
        $now = now()->timestamp;

        if ($startTime && $startTime > $now) {
            return 'upcoming';
        }

        if ($endTime && $endTime < $now) {
            return 'ended';
        }

        return 'ongoing';
    }

    private function resolvePromotionStore(?int $storeId = null): ?Store
    {
        if ($storeId) {
            $store = Store::with('channel')->find($storeId);
            return $this->isPromotionFilterableStore($store) ? $store : null;
        }

        $preferred = Store::with('channel')
            ->whereHas('channel', function ($query) {
                $query->whereIn('code', ['shopee', 'shp']);
            })
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($preferred) {
            return $preferred;
        }

        return Store::with('channel')->where('is_active', true)->orderBy('id')->first();
    }

    private function resolvePromotionStores(?int $storeId = null)
    {
        if ($storeId) {
            $store = Store::with('channel')->find($storeId);
            return $this->isPromotionFilterableStore($store) ? collect([$store]) : collect();
        }

        return Store::with('channel')
            ->whereHas('channel', function ($query) {
                $query->whereIn('code', ['shopee', 'shp']);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function resolvePromotionStoreBinding(mixed $store): Store
    {
        if ($store instanceof Store && filled($store->id)) {
            return Store::with('channel')->findOrFail((int) $store->id);
        }

        $routeStore = request()->route('store');
        if ($routeStore instanceof Store && filled($routeStore->id)) {
            return Store::with('channel')->findOrFail((int) $routeStore->id);
        }

        if (is_numeric($routeStore)) {
            return Store::with('channel')->findOrFail((int) $routeStore);
        }

        if (is_numeric($store)) {
            return Store::with('channel')->findOrFail((int) $store);
        }

        abort(404, 'Toko promosi tidak ditemukan.');
    }

    private function isPromotionFilterableStore(?Store $store): bool
    {
        if (! $store) {
            return false;
        }

        $store->loadMissing('channel');

        $channelCode = strtolower((string) ($store->channel?->code ?? ''));

        return $store->is_active
            && in_array($channelCode, ['shopee', 'shp'], true);
    }

    private function normalizeStorePayload(Store $store): array
    {
        $store->loadMissing('channel');

        return [
            'id' => $store->id,
            'name' => $store->name,
            'channel' => $store->channel ? [
                'id' => $store->channel->id,
                'code' => $store->channel->code,
                'name' => $store->channel->name,
            ] : null,
        ];
    }

    private function formatStoreLabel(Store $store): string
    {
        $store->loadMissing('channel');

        return $store->channel
            ? "{$store->name} • {$store->channel->name}"
            : $store->name;
    }

    private function extractPromotionList(array $response): array
    {
        return data_get($response, 'response.discount_list')
            ?? data_get($response, 'response.list')
            ?? data_get($response, 'response.discount_info_list')
            ?? [];
    }

    private function normalizePromotionCampaign(array $discount): array
    {
        $itemList = data_get($discount, 'discount_item_list')
            ?? data_get($discount, 'item_list')
            ?? data_get($discount, 'items')
            ?? [];

        if (! is_array($itemList)) {
            $itemList = [];
        }

        $startTime = (int) (data_get($discount, 'start_time') ?? 0);
        $endTime = (int) (data_get($discount, 'end_time') ?? 0);
        $updateTime = (int) (data_get($discount, 'update_time') ?? 0);
        $status = (string) (data_get($discount, 'discount_status') ?? data_get($discount, 'status') ?? '');

        return [
            'discount_id'   => (int) (data_get($discount, 'discount_id') ?? 0),
            'discount_name' => (string) (data_get($discount, 'discount_name') ?? data_get($discount, 'name') ?? ''),
            'discount_status' => $status ?: null,
            'status_label'  => $this->formatPromotionStatus($status),
            'start_time'    => $startTime ?: null,
            'start_label'   => $this->formatPromotionTimestamp($startTime),
            'end_time'      => $endTime ?: null,
            'end_label'     => $this->formatPromotionTimestamp($endTime),
            'update_time'   => $updateTime ?: null,
            'update_label'  => $this->formatPromotionTimestamp($updateTime),
            'item_count'    => (int) (data_get($discount, 'item_count') ?? count($itemList)),
            'raw'           => $discount,
        ];
    }

    private function enrichPromotionCampaignsWithLocalPreview(Store $store, array $promotions): array
    {
        if (empty($promotions)) {
            return $promotions;
        }

        $discountIds = collect($promotions)
            ->pluck('discount_id')
            ->filter(fn ($value) => ! empty($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        if (empty($discountIds)) {
            return $promotions;
        }

        $records = MarketplacePromotion::query()
            ->where('store_id', $store->id)
            ->whereIn('discount_id', $discountIds)
            ->get()
            ->keyBy('discount_id');

        return collect($promotions)
            ->map(function (array $promotion) use ($records) {
                $record = $records->get((int) ($promotion['discount_id'] ?? 0));
                if (! $record) {
                    return $promotion + [
                        'items_preview' => [],
                        'item_preview_summary' => '—',
                    ];
                }

                $items = [];
                $previewSource = is_array($record->detail_cache_json ?? null)
                    ? (data_get($record->detail_cache_json, 'items') ?: [])
                    : [];

                if (empty($previewSource) && is_array($record->item_list_json ?? null)) {
                    $previewSource = $this->buildPromotionPreviewFromItemList($record->item_list_json);
                }

                if (is_array($previewSource)) {
                    $items = $this->normalizePromotionPreviewItems($previewSource);
                }

                $promotion['items_preview'] = $items;
                $promotion['item_preview_summary'] = $this->formatPromotionItemsPreview($items);
                if (empty($promotion['item_count']) && ! empty($items)) {
                    $promotion['item_count'] = count($items);
                }

                return $promotion;
            })
            ->all();
    }

    private function buildPromotionPreviewFromItemList(array $itemList): array
    {
        return collect($itemList)->map(function ($item) {
            $models = data_get($item, 'model_list', []);
            if (! is_array($models)) {
                $models = [];
            }

            return [
                'item_id' => (int) (data_get($item, 'item_id') ?? 0),
                'item_name' => (string) (data_get($item, 'item_name') ?? ''),
                'item_sku' => (string) (data_get($item, 'item_sku') ?? ''),
                'sku_mapping_code' => (string) (data_get($item, 'sku_mapping_code') ?? ''),
                'model_list' => collect($models)->map(function ($model) {
                    return [
                        'model_id' => (int) (data_get($model, 'model_id') ?? 0),
                        'model_name' => (string) (data_get($model, 'model_name') ?? ''),
                        'model_sku' => (string) (data_get($model, 'model_sku') ?? ''),
                        'variant_sku_label' => (string) (data_get($model, 'variant_sku_label') ?? ''),
                        'sku_mapping_code' => (string) (data_get($model, 'sku_mapping_code') ?? ''),
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    private function normalizePromotionPreviewItems(array $items): array
    {
        return collect($items)->map(function ($item) {
            $models = data_get($item, 'model_list', []);
            if (! is_array($models)) {
                $models = [];
            }

            return [
                'item_id' => (int) (data_get($item, 'item_id') ?? 0),
                'item_name' => (string) (data_get($item, 'item_name') ?? data_get($item, 'product_title_label') ?? ''),
                'item_sku' => (string) (data_get($item, 'item_sku') ?? ''),
                'sku_mapping_code' => (string) (data_get($item, 'sku_mapping_code') ?? ''),
                'model_list' => collect($models)->map(function ($model) {
                    return [
                        'model_id' => (int) (data_get($model, 'model_id') ?? 0),
                        'model_name' => (string) (data_get($model, 'model_name') ?? ''),
                        'model_sku' => (string) (data_get($model, 'model_sku') ?? ''),
                        'variant_sku_label' => (string) (data_get($model, 'variant_sku_label') ?? data_get($model, 'model_sku') ?? ''),
                        'sku_mapping_code' => (string) (data_get($model, 'sku_mapping_code') ?? ''),
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    private function formatPromotionItemsPreview(array $items, int $maxItems = 2, int $maxVariants = 3): string
    {
        if (empty($items)) {
            return '—';
        }

        $chunks = [];

        foreach (array_slice($items, 0, $maxItems) as $item) {
            $itemLabel = trim((string) ($item['item_name'] ?? ''));
            $itemId = (string) ($item['item_id'] ?? '');
            $head = $itemId !== '' ? $itemId : '—';
            if ($itemLabel !== '') {
                $head .= ' • ' . Str::limit($itemLabel, 26);
            }

            $variants = [];
            foreach (array_slice($item['model_list'] ?? [], 0, $maxVariants) as $model) {
                $variant = trim((string) ($model['model_name'] ?? ''));
                $variantId = (string) ($model['model_id'] ?? '');
                $skuCode = trim((string) ($model['sku_mapping_code'] ?? ''));
                $parts = [];
                if ($variantId !== '') {
                    $parts[] = $variantId;
                }
                if ($variant !== '') {
                    $parts[] = $variant;
                }
                $label = implode(' • ', $parts);
                if ($label === '') {
                    $label = $variantId !== '' ? $variantId : 'variant';
                }
                if ($skuCode !== '') {
                    $label .= ' [' . $skuCode . ']';
                }
                $variants[] = $label;
            }

            if (count(($item['model_list'] ?? [])) > $maxVariants) {
                $variants[] = '+' . (count($item['model_list']) - $maxVariants);
            }

            $chunks[] = $head . ' | ' . implode(', ', $variants);
        }

        if (count($items) > $maxItems) {
            $chunks[] = '+' . (count($items) - $maxItems) . ' item';
        }

        return implode(' · ', $chunks);
    }

    private function normalizePromotionDetail(array $response, Store $store, int $discountId): array
    {
        $detail = data_get($response, 'response.discount_info')
            ?? data_get($response, 'response')
            ?? [];

        if (! is_array($detail)) {
            $detail = [];
        }

        $items = data_get($response, 'response.discount_item_list')
            ?? data_get($response, 'response.item_list')
            ?? data_get($detail, 'discount_item_list')
            ?? data_get($detail, 'item_list')
            ?? [];

        if (! is_array($items)) {
            $items = [];
        }

        return [
            'discount_id'   => (int) (data_get($detail, 'discount_id') ?? $discountId),
            'discount_name' => (string) (data_get($detail, 'discount_name') ?? data_get($detail, 'name') ?? ''),
            'discount_status' => (string) (data_get($detail, 'discount_status') ?? data_get($detail, 'status') ?? ''),
            'status_label'  => $this->formatPromotionStatus((string) (data_get($detail, 'discount_status') ?? data_get($detail, 'status') ?? '')),
            'start_time'    => (int) (data_get($detail, 'start_time') ?? 0) ?: null,
            'end_time'      => (int) (data_get($detail, 'end_time') ?? 0) ?: null,
            'item_count'    => (int) (data_get($detail, 'item_count') ?? count($items)),
            'items'         => $this->enrichPromotionItemsWithSku($store, collect($items)->map(function ($item) {
                $modelList = data_get($item, 'model_list')
                    ?? data_get($item, 'models')
                    ?? data_get($item, 'model_info')
                    ?? [];

                if (! is_array($modelList)) {
                    $modelList = [];
                }

                return [
                    'item_id' => (int) (data_get($item, 'item_id') ?? 0),
                    'item_name' => (string) (data_get($item, 'item_name') ?? data_get($item, 'name') ?? ''),
                    'item_original_price' => data_get($item, 'original_price', data_get($item, 'price', data_get($item, 'price_info.0.original_price'))),
                    'model_list' => array_map(function ($model) {
                        $originalPrice = data_get($model, 'model_original_price')
                            ?? data_get($model, 'original_price')
                            ?? data_get($model, 'price')
                            ?? data_get($model, 'model_price')
                            ?? data_get($model, 'price_info.0.original_price');

                        return [
                            'model_id' => (int) (data_get($model, 'model_id') ?? 0),
                            'model_name' => (string) (data_get($model, 'model_name') ?? data_get($model, 'name') ?? ''),
                            'model_original_price' => $originalPrice,
                            'model_promotion_price' => data_get($model, 'model_promotion_price', data_get($model, 'promotion_price', data_get($model, 'discount_price'))),
                            'model_promotion_percentage' => data_get($model, 'model_promotion_percentage', data_get($model, 'promotion_percentage')),
                        ];
                    }, $modelList),
                    'raw' => $item,
                ];
            })->values()->all()),
            'store'         => $this->normalizeStorePayload($store),
            'raw'           => $response,
        ];
    }

    private function enrichPromotionItemsWithSku(Store $store, array $items): array
    {
        $itemIds = collect($items)
            ->pluck('item_id')
            ->filter(fn ($itemId) => ! empty($itemId))
            ->map(fn ($itemId) => (int) $itemId)
            ->unique()
            ->values()
            ->all();

        if (empty($itemIds)) {
            return $items;
        }

        $products = MarketplaceProduct::with(['models'])
            ->where('store_id', $store->id)
            ->whereIn('item_id', $itemIds)
            ->get()
            ->keyBy('item_id');

        $channelCode = strtolower((string) ($store->channel?->code ?? ''));

        return collect($items)->map(function (array $item) use ($products, $channelCode) {
            $product = $products->get((int) ($item['item_id'] ?? 0));
            $productTitle = $product?->item_name
                ?? data_get($item, 'item_name')
                ?? data_get($item, 'name')
                ?? null;

            $itemVariantSku = $product?->item_sku
                ?? data_get($item, 'item_sku')
                ?? null;

            $item['item_sku'] = $itemVariantSku;
            $item['item_sku_label'] = $itemVariantSku ?: '—';
            $item['product_title'] = $productTitle;
            $item['product_title_label'] = $productTitle ?: '—';
            $item['sku_mapping_label'] = '—';
            $item['sku_mapping_code'] = null;
            $item['promo_stock'] = (int) ($product?->stock_total ?? data_get($item, 'stock_total') ?? 0);

            $modelRows = [];
            foreach (($item['model_list'] ?? []) as $model) {
                $modelId = (string) data_get($model, 'model_id', '');
                $localModel = $product?->models?->firstWhere('model_id', $modelId);
                $variantSku = $localModel?->model_sku
                    ?? data_get($model, 'model_sku')
                    ?? $itemVariantSku
                    ?? null;

                $mappingItem = null;
                if ($variantSku) {
                    $mappingItem = SkuMapping::query()
                        ->with('item:id,code,name')
                        ->where('marketplace_sku', $variantSku)
                        ->when($channelCode !== '', fn ($q) => $q->where(function ($qq) use ($channelCode) {
                            $qq->whereNull('channel_code')->orWhere('channel_code', $channelCode);
                        }))
                        ->orderByRaw('CASE WHEN channel_code IS NULL THEN 1 ELSE 0 END')
                        ->first();
                }

                $modelStock = $localModel?->stock;
                $promoStock = $modelStock !== null
                    ? (int) $modelStock
                    : (int) ($product?->stock_total ?? data_get($item, 'stock_total') ?? 0);

                $modelRows[] = array_merge($model, [
                    'model_sku' => $variantSku,
                    'variant_sku_label' => $variantSku ?: '—',
                    'sku_mapping_code' => $mappingItem?->item?->code,
                    'sku_mapping_label' => $mappingItem?->item?->code
                        ? ($mappingItem->item->code . ($mappingItem->item->name ? ' • ' . $mappingItem->item->name : ''))
                        : '—',
                    'promo_stock' => $promoStock,
                    'promo_stock_label' => number_format($promoStock, 0, ',', '.'),
                    'model_stock' => $modelStock !== null ? (int) $modelStock : null,
                ]);
            }

            $item['model_list'] = $modelRows;

            return $item;
        })->values()->all();
    }

    private function normalizePromotionItemList(mixed $items): array
    {
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(fn ($item) => $this->normalizePromotionItem($item))
            ->filter(fn ($item) => ! empty($item['item_id']))
            ->values()
            ->all();
    }

    private function normalizePromotionItem(mixed $item): array
    {
        $item = is_array($item) ? $item : [];

        $modelList = data_get($item, 'model_list') ?? data_get($item, 'models') ?? [];
        if (is_string($modelList)) {
            $decoded = json_decode($modelList, true);
            $modelList = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($modelList)) {
            $modelList = [];
        }

        if (empty($modelList)) {
            $modelList = [[
                'model_id' => data_get($item, 'model_id', 0),
                'model_promotion_price' => data_get($item, 'model_promotion_price', data_get($item, 'promotion_price', data_get($item, 'discount_price'))),
                'model_promotion_percentage' => data_get($item, 'model_promotion_percentage', data_get($item, 'promotion_percentage')),
            ]];
        }

        $normalizedModels = collect($modelList)->map(function ($model) {
            $model = is_array($model) ? $model : [];

            return [
                'model_id' => (int) (data_get($model, 'model_id') ?? 0),
                'model_promotion_price' => $this->toNullableFloat(
                    data_get($model, 'model_promotion_price', data_get($model, 'promotion_price', data_get($model, 'discount_price')))
                ),
                'model_promotion_percentage' => $this->toNullableFloat(
                    data_get($model, 'model_promotion_percentage', data_get($model, 'promotion_percentage'))
                ),
            ];
        })->filter(fn ($model) => $model['model_id'] !== 0 || $model['model_promotion_price'] !== null || $model['model_promotion_percentage'] !== null)
            ->values()
            ->all();

        return [
            'item_id' => (int) (data_get($item, 'item_id') ?? 0),
            'model_list' => $normalizedModels,
        ];
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function formatPromotionStatus(string $status): string
    {
        return match (strtolower($status)) {
            'ongoing'  => 'Ongoing',
            'upcoming' => 'Upcoming',
            'ended'    => 'Ended',
            'suspended'=> 'Suspended',
            default    => $status !== '' ? ucfirst(strtolower($status)) : '-',
        };
    }

    private function formatPromotionTimestamp(?int $timestamp): ?string
    {
        if (! $timestamp) {
            return null;
        }

        return Carbon::createFromTimestamp($timestamp)
            ->setTimezone(config('app.timezone'))
            ->format('d M Y H:i');
    }

    private function formatPromotionSchedule(?int $startTime, ?int $endTime): string
    {
        $start = $this->formatPromotionTimestamp($startTime);
        $end = $this->formatPromotionTimestamp($endTime);

        if ($start && $end) {
            return $start . ' - ' . $end;
        }

        return $start ?: $end ?: '-';
    }

    private function promotionMatchesDateRange(array $promotion, ?int $dateFromTs, ?int $dateToTs): bool
    {
        if (! $dateFromTs && ! $dateToTs) {
            return true;
        }

        $start = (int) ($promotion['start_time'] ?? 0);
        $end = (int) ($promotion['end_time'] ?? 0);

        if ($dateFromTs && $end && $end < $dateFromTs) {
            return false;
        }

        if ($dateToTs && $start && $start > $dateToTs) {
            return false;
        }

        return true;
    }

    private function buildPromotionStoreSummary(Store $store, array $rows): array
    {
        $summary = [
            'store' => $this->normalizeStorePayload($store),
            'promotions' => count($rows),
            'ongoing' => 0,
            'upcoming' => 0,
            'ended' => 0,
            'suspended' => 0,
            'items' => 0,
            'next_start' => null,
            'next_end' => null,
        ];

        foreach ($rows as $row) {
            $status = strtolower((string) ($row['status_key'] ?? ''));
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
            $summary['items'] += (int) ($row['item_count'] ?? 0);

            $start = (int) ($row['start_time'] ?? 0);
            $end = (int) ($row['end_time'] ?? 0);

            if ($start && (! $summary['next_start'] || $start < $summary['next_start'])) {
                $summary['next_start'] = $start;
            }
            if ($end && (! $summary['next_end'] || $end > $summary['next_end'])) {
                $summary['next_end'] = $end;
            }
        }

        $summary['next_start_label'] = $summary['next_start'] ? $this->formatPromotionTimestamp($summary['next_start']) : null;
        $summary['next_end_label'] = $summary['next_end'] ? $this->formatPromotionTimestamp($summary['next_end']) : null;

        return $summary;
    }

    private function isShopeeAuthError(array $response): bool
    {
        $error = strtolower((string) ($response['error'] ?? ''));
        $message = strtolower((string) ($response['message'] ?? ''));

        return $error === 'error_auth'
            || str_contains($error, 'access_token')
            || str_contains($message, 'access_token')
            || str_contains($message, 'token expired')
            || str_contains($message, 'expired');
    }

    private function promotionAuthErrorMessage(array $response, string $fallback): string
    {
        if ($this->isShopeeAuthError($response)) {
            return 'Token Shopee untuk toko ini tidak valid atau perlu disambungkan ulang. Silakan buka menu Toko lalu reconnect akun Shopee.';
        }

        return $response['message'] ?? $response['error'] ?? $fallback;
    }

    private function ensureSettlementStoreSyncable(Store $store): ?JsonResponse
    {
        $store->loadMissing('channel');
        $channelCode = strtolower(trim((string) $store->channel?->code));

        if (! in_array($channelCode, ['shopee', 'shp'], true)) {
            return response()->json([
                'success' => false,
                'code' => 'SETTLEMENT_CHANNEL_UNSUPPORTED',
                'message' => "Sync settlement saat ini hanya tersedia untuk Shopee. Toko {$store->name} memakai channel " . ($store->channel?->name ?? 'tidak dikenal') . '.',
            ], 422);
        }

        if ($store->status !== 'active' || ! $store->is_active) {
            return response()->json([
                'success' => false,
                'code' => 'STORE_INACTIVE',
                'message' => "Toko {$store->name} sedang nonaktif. Aktifkan toko terlebih dahulu sebelum menjalankan sync settlement.",
            ], 409);
        }

        return null;
    }

    /**
     * Cek connection_status toko SEBELUM meng-queue job latar belakang (force-sync-background,
     * sync-orders-background, sync-historical) — supaya tidak diam-diam gagal di worker tanpa
     * pernah memberi tahu user (job latar belakang tidak punya jalur feedback real-time ke UI).
     * Pola & pesan disalin persis dari pre-check yang sudah dipakai & terverifikasi di
     * syncOrders() (baris ~594-649) dan syncSettlementsBackground(). Return null kalau toko
     * siap disync, atau JsonResponse redirect (422/401) kalau tidak.
     */
    private function ensureStoreReadyForBackgroundSync(Store $store, bool $refreshExpiredToken = true): ?JsonResponse
    {
        $store->loadMissing('channel');
        $status = $store->connection_status;

        if ($status === 'TOKEN_EXPIRED' && $refreshExpiredToken) {
            try {
                if ($store->channel->code === 'shopee') {
                    /** @var \App\Services\Channels\Shopee\ShopeeChannel $shopee */
                    $shopee = app(\App\Services\Marketplace\MarketplaceApiGateway::class);
                    $shopee->refreshToken($store);
                    $store->refresh();
                    $status = $store->connection_status;
                } elseif ($store->channel->code === 'tiktok') {
                    /** @var \App\Services\Channels\TikTokShop\TikTokShopChannel $tiktok */
                    $tiktok = app(\App\Services\Channels\TikTokShop\TikTokShopChannel::class);
                    $tiktok->refreshToken($store);
                    $store->refresh();
                    $status = $store->connection_status;
                }
            } catch (\Throwable $e) {
                $status = 'AUTH_REQUIRED';
                \Illuminate\Support\Facades\Log::warning('Token refresh failed before queueing background sync', [
                    'store_id'   => $store->id,
                    'store_name' => $store->name,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        if ($status === 'TOKEN_EXPIRED' && ! $refreshExpiredToken) {
            return null;
        }

        if ($status === 'NOT_CONNECTED') {
            $urlSegment = $store->channel->code === 'TKT' ? 'tiktok' : 'shopee';
            return response()->json([
                'success' => false,
                'code'    => 'STORE_NOT_CONNECTED',
                'message' => "Toko {$store->name} belum terhubung ke {$store->channel->name}.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Hubungkan ' . $store->channel->name,
                    'url'   => url('/marketplace/' . $urlSegment . '/connect'),
                ],
            ], 422);
        }

        if ($status !== 'CONNECTED') {
            $urlSegment = $store->channel->code === 'TKT' ? 'tiktok' : 'shopee';
            return response()->json([
                'success' => false,
                'code'    => 'SHOPEE_AUTH_REQUIRED',
                'message' => "Koneksi {$store->channel->name} untuk toko {$store->name} sudah tidak aktif. Login ulang diperlukan sebelum sinkronisasi bisa berjalan.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Login Ulang ' . $store->channel->name,
                    'url'   => url('/marketplace/' . $urlSegment . '/connect'),
                ],
            ], 401);
        }

        return null;
    }

    private function resolveLegacyMarketplaceStoreIds(Store $store): array
    {
        $ids = [$store->id];

        $legacyById = DB::table('marketplace_stores')
            ->where('id', $store->id)
            ->pluck('id')
            ->all();

        $ids = array_merge($ids, $legacyById);

        if (! empty($store->external_shop_id)) {
            $legacyByExternal = DB::table('marketplace_stores')
                ->where('external_store_id', (string) $store->external_shop_id)
                ->pluck('id')
                ->all();

            $ids = array_merge($ids, $legacyByExternal);
        }

        $ids = array_values(array_unique(array_map('intval', array_filter(
            $ids,
            fn ($value) => $value !== null && $value !== ''
        ))));

        return $ids ?: [$store->id];
    }

    public function syncHistorical(Request $request, Store $store): JsonResponse
    {
        if ($resp = $this->ensureStoreReadyForBackgroundSync($store, false)) {
            return $resp;
        }

        $year = $request->input('year', 2022);

        // Lempar pekerjaan berat ini ke antrean (Queue) di latar belakang
        // Queue 'heavy': job backfill berjam-jam tidak boleh menyumbat queue default
        // (webhook order real-time, download resi) — lihat routes/console.php.
        \Illuminate\Support\Facades\Artisan::queue('shopee:sync-historical-orders', [
            'year' => $year,
            '--store' => $store->id
        ])->onQueue('heavy');

        \Illuminate\Support\Facades\Artisan::queue('shopee:sync-historical-returns', [
            'year' => $year,
            '--store' => $store->id
        ])->onQueue('heavy');

        return response()->json([
            'status' => 'success',
            'message' => "Proses 'Mesin Waktu' menuju tahun {$year} untuk toko {$store->name} sedang berjalan di latar belakang!"
        ]);
    }

    public function forceSyncBackground(Request $request, Store $store): JsonResponse
    {
        if ($resp = $this->ensureStoreReadyForBackgroundSync($store, false)) {
            return $resp;
        }

        // Jalur antre yang SAMA dengan syncOrdersBackground() (queueOrderSync) —
        // sebelumnya dua endpoint ini menduplikasi logika queue masing-masing.
        $this->queueOrderSync($store, 3);
        \App\Jobs\SyncMarketplaceReturns::dispatch($store, null, null, true);

        return response()->json([
            'message' => 'Perintah tarik data pesanan (3 hari terakhir, default command) dan retur terbaru telah dikirim ke latar belakang.',
            'status' => 'queued'
        ]);
    }

    /**
     * SATU-SATUNYA jalur antre sync order latar belakang — dipakai
     * forceSyncBackground() (3 hari + retur) dan syncOrdersBackground() (N hari).
     * Set progres "queued" untuk UI, pilih queue berdasarkan bobot pekerjaan,
     * dan catat last_synced_at.
     */
    private function queueOrderSync(Store $store, int $days): void
    {
        // Set state awal supaya UI langsung menampilkan progres "antre" sebelum worker jalan.
        \Illuminate\Support\Facades\Cache::put("marketplace:sync_progress:{$store->id}", [
            'percent' => 0,
            'label'   => 'Menunggu antrean worker…',
            'status'  => 'queued',
            'store'   => $store->name,
            'ts'      => now()->timestamp,
        ], 1800);

        $pending = \Illuminate\Support\Facades\Artisan::queue('marketplace:sync-orders', [
            '--store' => $store->id,
            '--days'  => $days,
        ]);
        if ($days > 7) {
            // Rentang panjang = pekerjaan lama → queue 'heavy' agar webhook real-time
            // di queue default tidak tertahan. Worker heavy jalan tiap 5 menit.
            $pending->onQueue('heavy');
        }

        $store->update(['last_synced_at' => now()]);
    }

    /**
     * Sync pesanan rentang panjang (mis. 30/60 hari) di latar belakang via queue.
     * Menghindari timeout browser untuk penarikan data berat.
     */
    public function syncOrdersBackground(Request $request, Store $store): JsonResponse
    {
        if ($resp = $this->ensureStoreReadyForBackgroundSync($store, false)) {
            return $resp;
        }

        $days = max(1, min(365, (int) $request->input('days', 30)));

        $this->queueOrderSync($store, $days);

        return response()->json([
            'message' => "Sync pesanan {$days} hari untuk toko ini dikirim ke latar belakang. Data akan masuk bertahap.",
            'status'  => 'queued',
            'days'    => $days,
        ]);
    }

    /**
     * Progres sinkronisasi latar belakang untuk satu toko (dibaca dari Cache).
     * Dipakai UI untuk menampilkan persentase pada dropdown "Latar Belakang".
     */
    public function syncOrdersProgress(Store $store): JsonResponse
    {
        $progress = \Illuminate\Support\Facades\Cache::get("marketplace:sync_progress:{$store->id}");

        if (! $progress) {
            return response()->json(['status' => 'idle', 'percent' => null]);
        }

        return response()->json($progress);
    }

    public function syncOrders(SyncOrdersRequest $request, Store $store): JsonResponse
    {
        // Beri waktu lebih untuk penarikan data berat (opsi 30/60 hari dipecah
        // menjadi beberapa jendela 14 hari, jadi butuh waktu lebih panjang).
        set_time_limit(300);

        $lock = \Illuminate\Support\Facades\Cache::lock("sync_store_{$store->id}", 240);

        if (!$lock->get()) {
            return response()->json(['message' => 'Sync sedang berjalan untuk toko ini. Mohon tunggu.'], 429);
        }

        // Pre-check koneksi memakai helper terpusat yang sama dengan semua endpoint
        // background — sebelumnya blok TOKEN_EXPIRED/NOT_CONNECTED/AUTH_REQUIRED
        // yang sama persis (±60 baris) terduplikasi inline di sini.
        if ($resp = $this->ensureStoreReadyForBackgroundSync($store)) {
            $lock->release();
            return $resp;
        }

        try {
            // Sinkronisasi order reguler
            $result = $this->sync->syncOrders(
                $store,
                (int) $request->time_from,
                (int) $request->time_to,
                (int) ($request->page_size ?? 50),
                (bool) $request->dry_run
            );

            // Sinkronisasi pesanan kilat (bookings) agar statusnya ter-update di UI.
            // Dry-run harus benar-benar read-only.
            if (! $request->boolean('dry_run')
                && $request->boolean('sync_bookings', true)
                && class_exists(\App\Jobs\SyncMarketplaceBookings::class)) {
                dispatch_sync(new \App\Jobs\SyncMarketplaceBookings($store, null, null, false));
            }

        } catch (\RuntimeException $e) {
            $lock->release();

            $msg = $e->getMessage();
            if (str_contains(strtolower($msg), 'access_token') || str_contains(strtolower($msg), 'auth')) {
                return response()->json([
                    'success' => false,
                    'code'    => 'SHOPEE_AUTH_REQUIRED',
                    'message' => "Koneksi {$store->channel->name} untuk toko {$store->name} sudah tidak aktif.",
                    'action'  => [
                        'type'  => 'redirect',
                        'label' => 'Login Ulang ' . $store->channel->name,
                        'url'   => url('/marketplace/' . $store->channel->code . '/connect')
                    ]
                ], 401);
            }

            return response()->json([
                'success' => false,
                'code'    => 'VALIDATION_ERROR',
                'message' => $msg
            ], 422);
        } catch (\Throwable $e) {
            $lock->release();

            \Illuminate\Support\Facades\Log::error('Marketplace sync internal error', [
                'store_id' => $store->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'code'    => 'CONNECTION_ERROR',
                'message' => 'Sinkronisasi pesanan belum berhasil. Koneksi ke ' . $store->channel->name . ' sedang bermasalah.'
            ], 502);
        }

        $lock->release();
        return response()->json($result);
    }

    public function localOrders(): JsonResponse
    {
        if (request()->boolean('dummy') && app()->environment(['local', 'testing'])) {
            return response()->json(app(\App\Support\DummyMarketplaceOrderProvider::class)->orders());
        }

        // Sertakan scan_log jika kolom sudah ada (setelah migration)
        $hasScanLog = in_array(
            'scan_log',
            \Illuminate\Support\Facades\Schema::getColumnListing('order_fulfillments')
        );
        $fulfillmentSelect = $hasScanLog
            ? 'id,marketplace_order_id,status,scan_log'
            : 'id,marketplace_order_id,status';

        $with = [
            'store.channel',
            'items',
            'items.internalItem' => fn ($q) => $q->select('id', 'code', 'item_category_id')->with('category:id,code,name'),
            'fulfillment:' . $fulfillmentSelect,
            'fulfillment.lines',
            'fulfillment.lines.item:id,code,name',
            'fulfillment.lines.splitChildren',
            'fulfillment.lines.splitChildren.item:id,code,name',
        ];

        // order_sn milik Pesanan Kilat (punya booking) — untuk penanda is_kilat, sekaligus
        // supaya order kilat tetap ikut ditarik walau lebih lama dari 200 order terbaru.
        // Guard hasTable agar Orders tidak error bila migration booking belum jalan di server.
        // Cocokkan hanya via order_sn (unik global) — JANGAN pakai store_id, karena record
        // booking & order bisa tersimpan di store_id lokal berbeda sehingga match gagal.
        $kilatMap = [];      // key: order_sn => booking_sn
        $kilatOrderSns = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('marketplace_bookings')) {
            $kilatMap = \App\Models\MarketplaceBooking::whereNotNull('order_sn')->where('order_sn', '!=', '')
                ->pluck('booking_sn', 'order_sn')->all();
            $kilatOrderSns = array_keys($kilatMap);
        }

        // Filter tanggal opsional dari UI (halaman Orders mengirim rentang aktif).
        // Tanpa ini, halaman selalu terpaku pada 200 order terbaru sehingga hasil
        // sync data masa lalu (backfill) tidak pernah muncul / jumlah tidak bertambah.
        $dateFrom = request()->query('date_from');
        $dateTo   = request()->query('date_to');
        $limit    = max(1, min(2000, (int) request()->query('limit', 500)));

        $ordersQuery = MarketplaceOrder::with($with);
        if ($dateFrom || $dateTo) {
            $ordersQuery->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereNull('ordered_at'); // order tanpa tanggal tetap tampil (difilter longgar di frontend)
                $q->orWhere(function ($range) use ($dateFrom, $dateTo) {
                    if ($dateFrom) $range->where('ordered_at', '>=', $dateFrom . ' 00:00:00');
                    if ($dateTo)   $range->where('ordered_at', '<=', $dateTo . ' 23:59:59');
                });
            });
        }
        $orders = $ordersQuery->latest('ordered_at')->limit($limit)->get();

        // Sertakan order kilat yang TIDAK masuk 200 terbaru (mis. booking MATCHED yang lama).
        // Cocokkan channel_order_id ATAU external_order_id (sebagian toko simpan order_sn di sana).
        if (! empty($kilatOrderSns)) {
            $extra = MarketplaceOrder::with($with)
                ->where(function ($q) use ($kilatOrderSns) {
                    $q->whereIn('channel_order_id', $kilatOrderSns)
                      ->orWhereIn('external_order_id', $kilatOrderSns);
                })
                ->whereNotIn('id', $orders->pluck('id')->all())
                ->latest('ordered_at')
                ->limit(300)
                ->get();
            if ($extra->isNotEmpty()) {
                $orders = $orders->concat($extra)->sortByDesc('ordered_at')->values();
            }
        }

        // Halaman Orders perlu menampilkan posisi terbaru di marketplace, bukan
        // hanya status terakhir yang tersimpan di database. Endpoint lain tetap
        // memakai data lokal agar tidak menambah traffic API secara tidak perlu.
        // Letakkan setelah order kilat tambahan digabung agar order lama juga ikut
        // diverifikasi bila masih berada di salah satu tab aktif.
        $liveStatusScope = strtolower((string) request()->query('live_status_scope', 'active'));
        $liveOrderStatuses = request()->boolean('live_status')
            ? $this->fetchLiveOrderStatuses($orders, $kilatMap, $liveStatusScope)
            : [];

        $mapped = $orders->map(function ($o) use ($hasScanLog, $kilatMap, $liveOrderStatuses) {
            $arr = $o->toArray();
            // Kilat = punya booking DAN BUKAN Instan (same-day). Keduanya berbeda:
            // Instan dideteksi dari nama kurir, ditangani di tab "Instan" tersendiri.
            $carrier   = strtolower((string) $o->shipping_carrier);
            $isInstant = str_contains($carrier, 'instant') || str_contains($carrier, 'same day') || str_contains($carrier, 'sameday');
            $bookingSn = $o->booking_sn ?? ($kilatMap[$o->channel_order_id] ?? ($kilatMap[$o->external_order_id] ?? null));
            $arr['is_kilat']               = (!empty($bookingSn)) && ! $isInstant;
            $arr['booking_sn']             = $bookingSn ?: null;
            $liveStatus = $liveOrderStatuses[$o->id]['status'] ?? null;
            $liveNeedsShipping = $liveOrderStatuses[$o->id]['needs_shipping'] ?? null;
            $liveLogisticsStatus = $liveOrderStatuses[$o->id]['logistics_status'] ?? null;
            $livePlatformPending = $liveOrderStatuses[$o->id]['platform_pending'] ?? null;
            $arr['api_order_status']       = $liveStatus;
            $arr['api_logistics_status']   = $liveLogisticsStatus;
            $arr['api_platform_pending']   = $livePlatformPending;
            $arr['status_source']          = $liveStatus ? 'api' : 'database';
            if ($liveStatus) {
                $arr['order_status'] = ($liveNeedsShipping === true)
                    ? 'READY_TO_SHIP'
                    : $liveStatus;
            }
            $arr['fulfillment_id']         = $o->fulfillment?->id;
            $arr['fulfillment_status']     = $o->fulfillment?->status; // null|draft|pending_review|confirmed|cancelled
            $arr['print_count']            = $o->print_count ?? 0;
            $arr['printed_at']             = $o->printed_at;
            $arr['has_unresolved_lines']   = $o->fulfillment
                ? $o->fulfillment->lines->whereNull('item_id')->isNotEmpty()
                : false;
            $arr['needs_shipping_arrangement'] = $liveNeedsShipping ?? $o->needs_shipping_arrangement;
            // Issues: ada item dengan data_status != 'valid' (sama seperti halaman /marketplace/issues)
            $arr['has_data_issues'] = $o->items->contains(
                fn ($item) => ($item->data_status ?? 'incomplete') !== 'valid'
            );

            // Logistics status dari raw_json (untuk order Shopee)
            $arr['logistics_status'] = $o->raw_json['package_list'][0]['logistics_status'] ?? null;

            // Item terscan untuk tab Sedang Packing & Sudah Proses
            // Priority: scan_log (raw scan baru) → picked_at lines (lama/manual) → null
            $arr['fulfillment_scan_log'] = null;
            if ($o->fulfillment) {
                $raw = $hasScanLog ? ($o->fulfillment->scan_log ?? null) : null;

                if ($raw) {
                    // scan_log tersedia (hasil packOrder() baru) — filter item tanpa code
                    $decoded = json_decode($raw, true) ?? [];
                    $arr['fulfillment_scan_log'] = array_values(
                        array_filter($decoded, fn ($s) => ! empty($s['code']) && ($s['qty'] ?? 0) > 0)
                    );
                } else {
                    // Fallback: lines yang sudah di-scan (picked_at not null, qty_fulfilled > 0)
                    // Untuk status packed, juga ambil semua lines dengan qty_fulfilled > 0 (bisa 0 jika tidak dipacking)
                    $status = $o->fulfillment->status;
                    $usePickedAt = in_array($status, ['picking', 'pending_review']);
                    $usePacked   = in_array($status, ['packed', 'confirmed']);

                    $scannedLines = $o->fulfillment->lines
                        ->where('is_split_parent', false)
                        ->filter(function ($l) use ($usePickedAt, $usePacked) {
                            if (! $l->item_id) return false;
                            if ($usePacked)   return ($l->qty_fulfilled ?? 0) > 0;
                            if ($usePickedAt) return $l->picked_at !== null && ($l->qty_fulfilled ?? 0) > 0;
                            return false;
                        });

                    if ($scannedLines->isNotEmpty()) {
                        // Group by item_id, sum qty
                        $grouped = $scannedLines->groupBy('item_id')->map(fn ($g, $itemId) => [
                            'item_id' => (int) $itemId,
                            'qty'     => $g->sum('qty_fulfilled'),
                            'code'    => $g->first()->item?->code ?? null,
                            'name'    => $g->first()->item?->name ?? null,
                        ]);
                        $arr['fulfillment_scan_log'] = $grouped->values()->all();
                    }
                }
            }

            // Item Resolve: fulfillment lines untuk packing orders (picking/packed/pending_review)
            $packingStatuses = ['picking', 'packed', 'pending_review', 'draft'];
            if ($o->fulfillment && in_array($o->fulfillment->status, $packingStatuses)) {
                $lines = $o->fulfillment->lines->where('is_split_parent', false);
                $arr['fulfillment_resolve_lines'] = $lines
                    ->filter(fn ($l) => $l->item_id !== null)
                    ->map(fn ($l) => [
                        'qty_ordered'   => $l->qty_ordered   ?? 1,
                        'qty_fulfilled' => $l->qty_fulfilled ?? 0,
                        'code'          => $l->item?->code ?? null,
                        'name'          => $l->item?->name ?? null,
                        'marketplace_sku' => $l->marketplace_sku ?? null,
                        'substituted'   => (bool) $l->substituted,
                        'split_parent_id' => $l->split_parent_id,
                    ])
                    ->values()->all();
                // Summary info untuk card display
                $totalOrdered   = $lines->sum('qty_ordered');
                $totalFulfilled = $lines->sum('qty_fulfilled');
                $arr['fulfillment_packing_summary'] = [
                    'total_ordered'   => $totalOrdered,
                    'total_fulfilled' => $totalFulfilled,
                    'has_shortage'    => $lines->filter(fn ($l) => $l->item_id)
                                               ->some(fn ($l) => ($l->qty_fulfilled ?? 0) < ($l->qty_ordered ?? 1)),
                ];
            } else {
                $arr['fulfillment_resolve_lines']    = [];
                $arr['fulfillment_packing_summary']  = null;
            }

            // Untuk tab Sudah Proses: include fulfilled lines dengan info lengkap
            if ($o->fulfillment?->status === 'confirmed') {
                $arr['fulfillment_lines'] = $o->fulfillment->lines->map(function ($l) {
                    return [
                        'id'                    => $l->id,
                        'marketplace_sku'       => $l->marketplace_sku,
                        'marketplace_item_name' => $l->marketplace_item_name,
                        'qty_ordered'           => $l->qty_ordered,
                        'qty_fulfilled'         => $l->qty_fulfilled,
                        'substituted'           => (bool) $l->substituted,
                        'is_split_parent'       => (bool) $l->is_split_parent,
                        'split_parent_id'       => $l->split_parent_id,
                        'notes'                 => $l->notes,
                        'item' => $l->item ? [
                            'id'   => $l->item->id,
                            'code' => $l->item->code,
                            'name' => $l->item->name,
                        ] : null,
                        'split_children' => $l->is_split_parent
                            ? $l->splitChildren->map(fn ($c) => [
                                'id'          => $c->id,
                                'qty_fulfilled'=> $c->qty_fulfilled,
                                'substituted' => (bool) $c->substituted,
                                'item'        => $c->item ? [
                                    'id'   => $c->item->id,
                                    'code' => $c->item->code,
                                    'name' => $c->item->name,
                                ] : null,
                            ])->values()->all()
                            : [],
                    ];
                })->values()->all();
            } else {
                $arr['fulfillment_lines'] = [];
            }

            return $arr;
        });

        // ── Pesanan Kilat murni (booking READY_TO_SHIP tanpa order lokal) ──────
        // Booking baru berstatus READY_TO_SHIP sering belum MATCHED ke order
        // (order_sn baru diberikan Shopee setelah MATCHED). Supaya tetap tampil
        // di halaman Orders (sub-tab ⚡ Pengiriman Kilat), sertakan sebagai baris
        // pseudo-order dengan flag is_booking = true.
        if (\Illuminate\Support\Facades\Schema::hasTable('marketplace_bookings')) {
            $knownSns = $orders->pluck('channel_order_id')
                ->merge($orders->pluck('external_order_id'))
                ->merge($orders->pluck('booking_sn'))
                ->filter()->flip();

            $pureBookings = \App\Models\MarketplaceBooking::with('store.channel')
                ->whereIn('booking_status', ['PENDING', 'READY_TO_SHIP', 'PROCESSED'])
                ->get()
                ->reject(function ($b) use ($knownSns) {
                    // Setelah booking berhasil dibuat sebagai order lokal,
                    // booking_sn bisa tetap menjadi channel_order_id dan
                    // order_sn masih kosong. Jangan tampilkan pseudo-order
                    // kedua untuk booking yang sama.
                    return $knownSns->has($b->booking_sn)
                        || (! empty($b->order_sn) && $knownSns->has($b->order_sn));
                });

            $allSkus = [];
            foreach ($pureBookings as $b) {
                if (is_array($b->items)) {
                    foreach ($b->items as $i) {
                        if ($sku = ($i['model_sku'] ?? $i['item_sku'] ?? null)) {
                            $allSkus[] = $sku;
                        }
                    }
                }
            }
            $allSkus = array_unique($allSkus);
            $mappedItems = \App\Models\Item::whereIn('code', $allSkus)
                ->select('id', 'code', 'item_category_id', 'name')
                ->with('category:id,code,name')
                ->get()
                ->keyBy('code');

            // PENTING: konsultasikan juga tabel sku_mappings (hasil halaman SKU Mapping).
            // Sebelumnya baris booking HANYA dicocokkan langsung ke Item.code, sehingga
            // SKU yang sudah di-map user tetap tampil "Belum Mapping" di tab ⚡ Kilat.
            $skuMapped = \App\Models\SkuMapping::with(['item' => fn ($q) => $q
                    ->select('id', 'code', 'item_category_id', 'name')
                    ->with('category:id,code,name')])
                ->whereIn('marketplace_sku', $allSkus)
                ->get()
                ->sortBy(fn ($m) => $m->channel_code === null ? 1 : 0) // spesifik channel menang atas global
                ->unique('marketplace_sku')
                ->keyBy('marketplace_sku');

            $liveBookingStatuses = request()->boolean('live_status') && $liveStatusScope !== 'matched'
                ? $this->fetchLiveBookingStatuses($pureBookings)
                : [];

            $bookingRows = $pureBookings->map(function ($b) use ($mappedItems, $skuMapped, $liveBookingStatuses) {
                $items = collect(is_array($b->items) ? $b->items : [])->map(function ($i) use ($mappedItems, $skuMapped) {
                    // Tampilkan SKU marketplace (bukan judul produk); judul hanya fallback.
                    $sku = $i['model_sku'] ?? $i['item_sku'] ?? null;
                    $title = trim(($i['item_name'] ?? '') . (! empty($i['model_name']) ? ' - ' . $i['model_name'] : '')) ?: null;
                    // Urutan: kecocokan langsung ke Item.code → tabel sku_mappings.
                    $mapped = $sku ? ($mappedItems->get($sku) ?? $skuMapped->get($sku)?->item) : null;
                    return [
                        'qty'           => $i['quantity'] ?? $i['model_quantity_purchased'] ?? 1,
                        'variant_name'  => $sku ?: $title,
                        'model_sku'     => $sku,
                        'item_sku'      => $i['item_sku'] ?? null,
                        'internal_item' => $mapped ? [
                            'id'   => $mapped->id,
                            'code' => $mapped->code,
                            'name' => $mapped->name,
                            'category' => $mapped->category ? [
                                'id'   => $mapped->category->id,
                                'code' => $mapped->category->code,
                                'name' => $mapped->category->name,
                            ] : null,
                        ] : null,
                    ];
                })->values()->all();

                $liveStatus = $liveBookingStatuses[$b->id]['status'] ?? null;
                $bookingStatus = $liveStatus ?: (string) $b->booking_status;
                $needsShipping = $liveBookingStatuses[$b->id]['needs_shipping']
                    ?? $b->needsShipping();

                return [
                    'id'                          => -$b->id, // negatif = baris booking (bukan order)
                    'store_id'                    => $b->store_id,
                    'store'                       => $b->store ? [
                        'id'      => $b->store->id,
                        'name'    => $b->store->name,
                        'channel' => $b->store->channel ? [
                            'code' => strtolower((string) $b->store->channel->code),
                            'name' => $b->store->channel->name,
                        ] : null,
                    ] : null,
                    'channel_order_id'            => $b->order_sn ?: $b->booking_sn,
                    'external_order_id'           => $b->order_sn,
                    'booking_sn'                  => $b->booking_sn,
                    // Booking tanpa bukti pengaturan kirim tetap di Perlu Dikirim,
                    // termasuk PROCESSED yang belum punya resi/package/document.
                    'order_status'                => $needsShipping ? 'READY_TO_SHIP' : $bookingStatus,
                    'api_order_status'            => $liveStatus,
                    'api_logistics_status'        => $liveBookingStatuses[$b->id]['logistics_status'] ?? null,
                    'api_platform_pending'        => $liveBookingStatuses[$b->id]['platform_pending'] ?? null,
                    // Simpan status booking lokal juga agar UI tetap bisa
                    // membedakan PENDING/PROCESSED saat live API dibatasi
                    // dengan scope matched demi menjaga kecepatan halaman.
                    'platform_status'             => $bookingStatus,
                    'status_source'               => $liveStatus ? 'api' : 'database',
                    'ordered_at'                  => $b->create_time
                        ? \Carbon\Carbon::createFromTimestamp($b->create_time)->toIso8601String()
                        : optional($b->created_at)->toIso8601String(),
                    'items'                       => $items,
                    'shipping_carrier'            => $b->shipping_carrier,
                    'shipping_awb_no'             => $b->tracking_number,
                    'is_kilat'                    => true,
                    'is_booking'                  => true,
                    'needs_shipping_arrangement'  => $needsShipping,
                    'fulfillment_id'              => null,
                    'fulfillment_status'          => null,
                    'print_count'                 => $b->print_count ?? 0,
                    'printed_at'                  => $b->printed_at ? \Carbon\Carbon::parse($b->printed_at)->toIso8601String() : null,
                    'has_unresolved_lines'        => false,
                    'has_data_issues'             => false,
                    'logistics_status'            => null,
                    'fulfillment_scan_log'        => null,
                    'fulfillment_resolve_lines'   => [],
                    'fulfillment_packing_summary' => null,
                    'fulfillment_lines'           => [],
                ];
            });

            $mapped = $mapped->concat($bookingRows);
        }

        return response()->json($mapped->values());
    }

    /**
     * Ambil status terbaru untuk order yang sedang berada di tab aktif.
     * Kegagalan API sengaja tidak menghilangkan order; UI akan memakai status DB.
     */
    private function fetchLiveOrderStatuses($orders, array $kilatMap = [], string $scope = 'active'): array
    {
        $candidateStatuses = $scope === 'matched' ? ['MATCHED'] : [
            'UNPAID',
            'READY_TO_SHIP',
            'MATCHED',
            'PROCESSED',
            'READY_TO_HANDOVER',
            'SHIPPED',
            'TO_CONFIRM_RECEIVE',
            'COMPLETED',
            'IN_CANCEL',
            'CANCELLED',
            'INVOICE_PENDING',
        ];
        $candidates = $orders->filter(fn ($order) =>
            in_array(strtoupper((string) $order->order_status), $candidateStatuses, true)
            || filled($order->booking_sn)
        );
        $result = [];

        foreach ($candidates->groupBy('store_id') as $storeOrders) {
            $store = $storeOrders->first()?->store;
            if (! $this->canReadLiveMarketplaceStatus($store)) {
                continue;
            }

            $bookingKeys = [];
            $regularKeys = [];
            $keyToOrderIds = [];

            foreach ($storeOrders as $order) {
                // Sebagian order kilat baru hanya tertaut melalui marketplace_bookings
                // (booking_sn di order lokal masih kosong). Jangan salah kirim order_sn
                // ke get_order_detail; gunakan booking detail API bila map mengetahuinya.
                $bookingSn = $order->booking_sn
                    ?: ($kilatMap[$order->channel_order_id] ?? ($kilatMap[$order->external_order_id] ?? null));
                $keys = array_filter([
                    $order->channel_order_id,
                    $order->external_order_id,
                    $bookingSn,
                ], fn ($key) => filled($key));
                foreach ($keys as $key) {
                    $key = (string) $key;
                    $keyToOrderIds[$key][] = $order->id;
                }

                if (filled($bookingSn)) {
                    $bookingKeys[] = (string) $bookingSn;
                } elseif (filled($order->channel_order_id)) {
                    $regularKeys[] = (string) $order->channel_order_id;
                }
            }

            foreach (array_chunk(array_values(array_unique($bookingKeys)), 50) as $chunk) {
                try {
                    $response = $this->gateway->getBookingDetail($store, implode(',', $chunk));
                    $this->assertMarketplaceApiResponse($response);

                    foreach ($this->extractMarketplaceBookingDetails($response) as $detail) {
                        $status = $this->canonicalLiveMarketplaceStatus(
                            $detail['booking_status'] ?? $detail['order_status'] ?? $detail['status'] ?? null
                        );
                        if (! $status) {
                            continue;
                        }

                        $keys = array_filter([$detail['booking_sn'] ?? null, $detail['order_sn'] ?? null]);
                        $package = data_get($detail, 'package_list.0', []);
                        $logisticsStatus = strtoupper((string) (
                            data_get($package, 'logistics_status')
                            ?? data_get($detail, 'logistics_status')
                            ?? ''
                        ));
                        $platformPending = $logisticsStatus === 'LOGISTICS_NOT_START';
                        $hasShippingArtifact = filled($detail['tracking_number'] ?? null)
                            || filled($detail['package_number'] ?? null)
                            || filled($detail['shipping_document_status'] ?? null)
                            || filled(data_get($package, 'tracking_number'))
                            || filled(data_get($package, 'tracking_no'))
                            || filled(data_get($package, 'package_number'));
                        $needsShipping = ! $hasShippingArtifact
                            && in_array($status, ['PENDING', 'READY_TO_SHIP', 'PROCESSED'], true);
                        foreach ($keys as $key) {
                            foreach ($keyToOrderIds[(string) $key] ?? [] as $orderId) {
                                $result[$orderId] = [
                                    'status' => $status,
                                    'needs_shipping' => $needsShipping,
                                    'logistics_status' => $logisticsStatus ?: null,
                                    'platform_pending' => $platformPending,
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gagal membaca status live booking untuk halaman Orders.', [
                        'store_id' => $store->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            foreach (array_chunk(array_values(array_unique($regularKeys)), 50) as $chunk) {
                try {
                    $response = $this->gateway->getOrderDetail($store, $chunk);
                    $this->assertMarketplaceApiResponse($response);

                    foreach ($this->extractMarketplaceOrderDetails($response) as $detail) {
                        $id = $detail['order_sn'] ?? $detail['order_id'] ?? $detail['id'] ?? null;
                        $status = $this->canonicalLiveMarketplaceStatus(
                            $detail['order_status'] ?? $detail['status'] ?? null
                        );
                        if (! filled($id) || ! $status) {
                            continue;
                        }

                        $package = data_get($detail, 'package_list.0', []);
                        $logisticsStatus = strtoupper((string) (
                            data_get($package, 'logistics_status')
                            ?? data_get($detail, 'logistics_status')
                            ?? ''
                        ));
                        $platformPending = $logisticsStatus === 'LOGISTICS_NOT_START';

                        foreach ($keyToOrderIds[(string) $id] ?? [] as $orderId) {
                            $result[$orderId] = [
                                'status' => $status,
                                'logistics_status' => $logisticsStatus ?: null,
                                'platform_pending' => $platformPending,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gagal membaca status live order untuk halaman Orders.', [
                        'store_id' => $store->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $result;
    }

    /** @param iterable<\App\Models\MarketplaceBooking> $bookings */
    private function fetchLiveBookingStatuses($bookings): array
    {
        $result = [];

        foreach ($bookings->groupBy('store_id') as $storeBookings) {
            $store = $storeBookings->first()?->store;
            if (! $this->canReadLiveMarketplaceStatus($store)) {
                continue;
            }

            $bookingIds = $storeBookings->pluck('booking_sn')->filter()->unique()->values()->all();
            $idMap = $storeBookings->keyBy('booking_sn');

            foreach (array_chunk($bookingIds, 50) as $chunk) {
                try {
                    $response = $this->gateway->getBookingDetail($store, implode(',', $chunk));
                    $this->assertMarketplaceApiResponse($response);

                    foreach ($this->extractMarketplaceBookingDetails($response) as $detail) {
                        $bookingSn = $detail['booking_sn'] ?? null;
                        $booking = $bookingSn ? $idMap->get((string) $bookingSn) : null;
                        if (! $booking) {
                            continue;
                        }

                        $status = $this->canonicalLiveMarketplaceStatus(
                            $detail['booking_status'] ?? $detail['order_status'] ?? $detail['status'] ?? null
                        );
                        if (! $status) {
                            continue;
                        }

                        $package = data_get($detail, 'package_list.0', []);
                        $logisticsStatus = strtoupper((string) (
                            data_get($package, 'logistics_status')
                            ?? data_get($detail, 'logistics_status')
                            ?? ''
                        ));
                        $hasShippingArtifact = filled($detail['tracking_number'] ?? null)
                            || filled($detail['package_number'] ?? null)
                            || filled($detail['shipping_document_status'] ?? null)
                            || filled(data_get($package, 'tracking_number'))
                            || filled(data_get($package, 'tracking_no'))
                            || filled(data_get($package, 'package_number'));

                        $result[$booking->id] = [
                            'status' => $status,
                            'logistics_status' => $logisticsStatus ?: null,
                            'platform_pending' => $logisticsStatus === 'LOGISTICS_NOT_START',
                            // Booking PROCESSED tanpa bukti pengaturan kirim tetap
                            // diperlakukan sebagai READY_TO_SHIP di UI.
                            'needs_shipping' => ! $hasShippingArtifact
                                && in_array($status, ['PENDING', 'READY_TO_SHIP', 'PROCESSED'], true),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gagal membaca status live booking murni untuk halaman Orders.', [
                        'store_id' => $store->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $result;
    }

    private function canReadLiveMarketplaceStatus(?Store $store): bool
    {
        return $store
            && $store->is_active
            && $store->status === 'active'
            && $store->connection_status === 'CONNECTED'
            && in_array(strtolower((string) $store->channel?->code), ['shopee', 'shp', 'tiktok', 'ttk', 'tt'], true);
    }

    private function assertMarketplaceApiResponse(array $response): void
    {
        if (! empty($response['error']) || (isset($response['code']) && (int) $response['code'] !== 0)) {
            throw new \RuntimeException($response['message'] ?? $response['error'] ?? 'API mengembalikan error.');
        }
    }

    private function extractMarketplaceOrderDetails(array $response): array
    {
        foreach ([
            data_get($response, 'response.order_list'),
            data_get($response, 'data.orders'),
            data_get($response, 'data.order_list'),
        ] as $details) {
            if (is_array($details)) {
                return array_values(array_filter($details, 'is_array'));
            }
        }

        return [];
    }

    private function extractMarketplaceBookingDetails(array $response): array
    {
        foreach ([
            data_get($response, 'response.booking_list'),
            data_get($response, 'response.order_list'),
            data_get($response, 'data.booking_list'),
        ] as $details) {
            if (is_array($details)) {
                return array_values(array_filter($details, 'is_array'));
            }
        }

        return [];
    }

    private function canonicalLiveMarketplaceStatus(mixed $status): ?string
    {
        if (! is_string($status) || trim($status) === '') {
            return null;
        }

        $status = strtoupper(trim($status));

        $canonical = [
            'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
            'AWAITING_COLLECTION' => 'READY_TO_HANDOVER',
            'IN_TRANSIT' => 'SHIPPED',
            'DELIVERED' => 'COMPLETED',
        ][$status] ?? $status;

        // Jangan pernah memasukkan nilai status API yang tidak dikenal ke
        // filter tab. Lebih aman memakai fallback lokal daripada memindahkan
        // order ke tab yang salah karena respons API berubah/korup.
        return in_array($canonical, [
            'PENDING',
            'UNPAID',
            'READY_TO_SHIP',
            'MATCHED',
            'PROCESSED',
            'READY_TO_HANDOVER',
            'SHIPPED',
            'TO_CONFIRM_RECEIVE',
            'COMPLETED',
            'CANCELLED',
            'CANCELLED_BEFORE_SHIPPING',
            'IN_CANCEL',
            'INVOICE_PENDING',
            'TO_RETURN',
            'RETURNING',
            'RETURNED',
            'REFUNDED',
        ], true) ? $canonical : null;
    }

    public function syncSettlements(Request $request, Store $store): JsonResponse
    {
        $backfillMonths = (int) $request->input('backfill_months', 0);
        if ($backfillMonths < 0 || $backfillMonths > 3) {
            return response()->json([
                'success' => false,
                'message' => 'backfill_months hanya boleh 1, 2, atau 3.',
            ], 422);
        }

        $isBackfill = $backfillMonths > 0;
        set_time_limit($isBackfill ? 0 : 180);
        $store->loadMissing('channel');

        if ($response = $this->ensureSettlementStoreSyncable($store)) {
            return $response;
        }

        $channelName = $store->channel?->name ?? 'channel marketplace';
        $channelCode = $store->channel?->code;
        $connectUrl = in_array(strtolower((string) $channelCode), ['shopee', 'shp'], true)
            ? url('/marketplace/shopee/connect')
            : url('/marketplace/settings');

        // Kunci yang SAMA dengan yang dipakai `marketplace:sync-settlements` (lihat
        // config marketplace.settlement_lock_ttl) supaya sync manual dari tombol UI dan
        // sync dari scheduler/CLI untuk toko yang sama tidak bisa tumpang tindih.
        $lock = \Illuminate\Support\Facades\Cache::lock(
            "sync_settlements_store_{$store->id}",
            (int) config('marketplace.settlement_lock_ttl', 3600)
        );

        if (! $lock->get()) {
            return response()->json([
                'message' => "Sync settlement sedang berjalan untuk toko {$store->name} (dari proses lain atau scheduler). Mohon tunggu beberapa saat lalu coba lagi.",
            ], 429);
        }

        $status = $store->connection_status;

        if ($status === 'TOKEN_EXPIRED') {
            try {
                if (in_array(strtolower((string) $channelCode), ['shopee', 'shp'], true)) {
                    /** @var \App\Services\Channels\Shopee\ShopeeChannel $shopee */
                    $shopee = app(\App\Services\Marketplace\MarketplaceApiGateway::class);
                    $shopee->refreshToken($store);
                    $store->refresh();
                    $store->loadMissing('channel');
                    $channelName = $store->channel?->name ?? $channelName;
                    $channelCode = $store->channel?->code ?? $channelCode;
                    $connectUrl = in_array(strtolower((string) $channelCode), ['shopee', 'shp'], true)
                        ? url('/marketplace/shopee/connect')
                        : url('/marketplace/settings');
                    $status = $store->connection_status;
                }
            } catch (\Throwable $e) {
                $status = 'AUTH_REQUIRED';
                \Illuminate\Support\Facades\Log::warning('Token refresh failed during settlement sync', [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($status === 'NOT_CONNECTED') {
            $lock->release();
            return response()->json([
                'success' => false,
                'code'    => 'STORE_NOT_CONNECTED',
                'message' => "Toko {$store->name} belum terhubung ke {$channelName}.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Hubungkan ' . $channelName,
                    'url'   => $connectUrl,
                ],
            ], 422);
        }

        if ($status !== 'CONNECTED') {
            $lock->release();
            return response()->json([
                'success' => false,
                'code'    => 'SHOPEE_AUTH_REQUIRED',
                'message' => "Koneksi {$channelName} untuk toko {$store->name} sudah tidak aktif. Login ulang diperlukan sebelum settlement bisa ditarik.",
                'action'  => [
                    'type'  => 'redirect',
                    'label' => 'Login Ulang ' . $channelName,
                    'url'   => $connectUrl,
                ],
            ], 401);
        }

        $timeFrom = $request->input('time_from') ? (int) $request->input('time_from') : null;
        $timeTo   = $request->input('time_to') ? (int) $request->input('time_to') : null;

        if ($isBackfill) {
            $timeTo = $timeTo ?: now()->endOfDay()->timestamp;
            $timeFrom = $timeFrom ?: now()->subMonthsNoOverflow($backfillMonths)->startOfDay()->timestamp;
        }

        $progressKey = "marketplace:settlement_sync_progress:{$store->id}";
        Cache::forget($progressKey);

        try {
            if ($isBackfill) {
                $result = $this->sync->syncSettlementsBackfill(
                    $store,
                    $timeFrom,
                    $timeTo,
                );
            } else {
                $result = $this->sync->syncSettlements(
                    $store,
                    $timeFrom,
                    $timeTo,
                );
            }

            Cache::put($progressKey, array_merge([
                'status' => ($result['errors'] ?? 0) > 0
                    ? (($result['synced'] ?? 0) > 0 ? 'warn' : 'error')
                    : (($result['skipped'] ?? 0) > 0 ? 'warn' : 'success'),
                'phase' => 'store_done',
                'percent' => 100,
                'store_id' => $store->id,
                'store_name' => $store->name,
            ], $result), 1800);
        } catch (\Throwable $e) {
            // Detail exception (bisa memuat pesan driver HTTP, query, dsb) HANYA masuk log —
            // TIDAK dikirim ke client. Response ke UI selalu pesan generik yang ramah,
            // konsisten dengan pola catch(\Throwable) di syncOrders() (baris ~665-677).
            \Illuminate\Support\Facades\Log::error('Settlement sync gagal total', [
                'store_id'   => $store->id,
                'store_name' => $store->name,
                'error'      => $e->getMessage(),
                'exception'  => get_class($e),
            ]);
            Cache::put($progressKey, [
                'status' => 'error',
                'phase' => 'error',
                'percent' => 100,
                'label' => 'Sinkronisasi settlement gagal.',
                'store_id' => $store->id,
                'store_name' => $store->name,
            ], 1800);

            return response()->json([
                'success' => false,
                'code'    => 'SETTLEMENT_SYNC_ERROR',
                'message' => "Sinkronisasi settlement untuk toko {$store->name} belum berhasil. Detail teknis sudah dicatat di log server — hubungi admin bila berulang.",
            ], 502);
        } finally {
            $lock->release();
        }

        return response()->json(array_merge($result, [
            'mode' => $isBackfill ? 'backfill' : 'regular',
            'backfill_months' => $backfillMonths ?: null,
        ]));
    }

    /**
     * Versi latar belakang dari syncSettlements() — untuk backfill jumlah besar (ratusan/
     * ribuan order) yang TIDAK mungkin selesai dalam satu siklus request HTTP tanpa
     * timeout. Mengikuti pola persis forceSyncBackground() (baris 546) yang sudah dipakai
     * untuk orders: dispatch command lewat Artisan::queue(), TIDAK dieksekusi langsung di
     * request ini. Butuh queue worker aktif di server (lihat catatan di response).
     *
     * Beda dengan syncSettlements() (tombol "Tarik Settlement Baru", satu batch @200 order,
     * sinkron/blocking): endpoint ini memicu `marketplace:sync-settlements --store=X --all`
     * yang mengulang batch sampai habis atau kena batas pengaman command (20 batch/12 menit
     * per eksekusi, lihat SyncSettlementsCommand::ALL_MAX_BATCHES/ALL_MAX_RUNTIME_SECONDS) —
     * cukup untuk backlog saat ini, dan aman diulang (idempotent, lock per toko) kalau
     * belum habis dalam satu run.
     */
    public function syncSettlementsBackground(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'backfill_months' => ['nullable', 'integer', 'min:1', 'max:3'],
        ]);

        @set_time_limit(0);

        if ($response = $this->ensureSettlementStoreSyncable($store)) {
            return $response;
        }

        $backfillMonths = (int) ($data['backfill_months'] ?? 0);
        $isBackfill = $backfillMonths > 0;
        $isAll = ! $isBackfill && $request->boolean('all');

        // Background dispatch tidak melakukan refresh token di request path.
        // Kalau token expired, worker akan menangani refresh saat job benar-benar jalan.
        if ($resp = $this->ensureStoreReadyForBackgroundSync($store, false)) {
            return $resp;
        }

        $progressKey = "marketplace:settlement_sync_progress:{$store->id}";
        $fromDate = $isBackfill ? now()->subMonthsNoOverflow($backfillMonths)->startOfDay()->toDateString() : null;
        $toDate   = $isBackfill ? now()->endOfDay()->toDateString() : null;

        \Illuminate\Support\Facades\Cache::put($progressKey, [
            'status'     => 'queued',
            'phase'      => 'queued',
            'percent'    => 2,
            'label'      => $isBackfill
                ? "Settlement backfill {$backfillMonths} bulan sedang antre…"
                : ($isAll
                    ? "Settlement sync semua batch untuk {$store->name} sedang antre…"
                    : "Settlement sync untuk {$store->name} sedang antre…"),
            'store_id'   => $store->id,
            'store_name' => $store->name,
            'mode'       => $isBackfill ? 'backfill' : ($isAll ? 'all' : 'regular'),
            'backfill_months' => $backfillMonths ?: null,
            'from'       => $fromDate,
            'to'         => $toDate,
            'updated_at' => now()->toISOString(),
        ], 1800);

        $options = [
            '--store' => $store->id,
        ];

        if ($isBackfill) {
            $options['--from'] = $fromDate;
            $options['--to'] = $toDate;
            $options['--all'] = true;
        } elseif ($isAll) {
            $options['--all'] = true;
        }

        // Dispatch ke queue — TIDAK dieksekusi sekarang. Kalau dispatch gagal,
        // jangan tinggalkan progress palsu berstatus queued selama 30 menit.
        try {
            $pending = \Illuminate\Support\Facades\Artisan::queue('marketplace:sync-settlements', $options);
            if ($isBackfill || $isAll) {
                $pending->onQueue('heavy'); // backfill escrow per order = lama → jangan sumbat queue default
            }
        } catch (\Throwable $e) {
            Cache::put($progressKey, [
                'status'     => 'error',
                'phase'      => 'dispatch_failed',
                'percent'    => 100,
                'label'      => 'Gagal memasukkan settlement sync ke queue.',
                'store_id'   => $store->id,
                'store_name' => $store->name,
                'mode'       => $isBackfill ? 'backfill' : ($isAll ? 'all' : 'regular'),
                'updated_at' => now()->toISOString(),
            ], 1800);
            Log::error('Settlement background sync gagal di-queue', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'SETTLEMENT_QUEUE_ERROR',
                'message' => 'Settlement sync gagal dimasukkan ke antrian. Periksa queue worker/server lalu coba lagi.',
            ], 503);
        }

        $queuedMessage = $isBackfill
            ? "Backfill settlement {$backfillMonths} bulan untuk {$store->name} telah dikirim ke latar belakang. Proses akan berjalan bertahap dan progress bisa dipantau di bar bawah. PENTING: proses ini butuh queue worker aktif di server (php artisan queue:work) — kalau tidak ada worker yang berjalan, job akan menunggu di antrian tanpa pernah dieksekusi."
            : ($isAll
                ? "Settlement sync semua batch untuk {$store->name} telah dikirim ke latar belakang. Proses akan berjalan bertahap dan progress bisa dipantau di bar bawah. PENTING: proses ini butuh queue worker aktif di server (php artisan queue:work) — kalau tidak ada worker yang berjalan, job akan menunggu di antrian tanpa pernah dieksekusi."
                : "Sinkronisasi settlement toko {$store->name} telah dikirim ke latar belakang. Proses berjalan bertahap dan progress bisa dipantau di bar bawah. PENTING: proses ini butuh queue worker aktif di server (php artisan queue:work) — kalau tidak ada worker yang berjalan, job akan menunggu di antrian tanpa pernah dieksekusi.");

        return response()->json([
            'success' => true,
            'status'  => 'queued',
            'mode'    => $isBackfill ? 'backfill' : ($isAll ? 'all' : 'regular'),
            'message' => $queuedMessage,
            'progress_key' => $progressKey,
        ]);
    }

    public function syncSettlementsProgress(Store $store): JsonResponse
    {
        $progress = \Illuminate\Support\Facades\Cache::get("marketplace:settlement_sync_progress:{$store->id}");

        if (! $progress) {
            return response()->json([
                'status' => 'idle',
                'percent' => null,
                'label' => null,
                'store_id' => $store->id,
                'store_name' => $store->name,
            ]);
        }

        return response()->json($progress);
    }

    public function settlements(Request $request): JsonResponse
    {
        $query = MarketplaceOrderSettlement::with(['store:id,name', 'order:id,channel_order_id,order_status,ordered_at,subtotal_items,total_paid_customer', 'order.items:id,marketplace_order_id,hpp_snapshot,qty,item_name,variant_name,model_sku,item_sku,image_url,mapping_status,internal_item_id']);

        $sortBy = $request->input('sort_by', 'settlement_time');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $settlementStatus = strtolower((string) $request->input('settlement_status', ''));
        $includeMissingPending = $settlementStatus === 'belum_cair';

        if ($sortBy === 'ordered_at') {
            $query->orderBy(
                \App\Models\MarketplaceOrder::select('ordered_at')
                    ->whereColumn('marketplace_orders.id', 'marketplace_order_settlements.order_id')
                    ->limit(1),
                $sortDir
            );
        } else if (in_array($sortBy, ['settlement_time', 'final_income', 'buyer_payment_amount'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('settlement_time');
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('status')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('order_status', $request->status);
            });
        }

        if ($request->input('cogs_zero') === '1') {
            $query->whereHas('order', function ($q) {
                $q->where(function ($q2) {
                    $q2->doesntHave('items')
                       ->orWhereHas('items', function ($q3) {
                           $q3->whereNull('hpp_snapshot')->orWhere('hpp_snapshot', '<=', 0);
                       });
                });
            });
        }

        if ($request->filled('order_date_from')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->whereDate('ordered_at', '>=', $request->order_date_from);
            });
        }

        if ($request->filled('order_date_to')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->whereDate('ordered_at', '<=', $request->order_date_to);
            });
        }

        $isUnsettledFilter = $request->input('tab') === 'belum_cair' || $includeMissingPending;

        if ($request->tab === 'semua') {
            if ($request->filled('settlement_date_from')) {
                $from = $request->settlement_date_from;
                $query->where(function ($q) use ($from) {
                    $q->whereDate('settlement_time', '>=', $from)
                      ->orWhere(function ($q2) use ($from) {
                          $q2->whereNull('settlement_time')
                             ->whereHas('order', function ($oq) use ($from) {
                                 $oq->whereDate('ordered_at', '>=', $from);
                             });
                      });
                });
            }

            if ($request->filled('settlement_date_to')) {
                $to = $request->settlement_date_to;
                $query->where(function ($q) use ($to) {
                    $q->whereDate('settlement_time', '<=', $to)
                      ->orWhere(function ($q2) use ($to) {
                          $q2->whereNull('settlement_time')
                             ->whereHas('order', function ($oq) use ($to) {
                                 $oq->whereDate('ordered_at', '<=', $to);
                             });
                      });
                });
            }
        } elseif (! $isUnsettledFilter) {
            if ($request->filled('settlement_date_from')) {
                $query->whereDate('settlement_time', '>=', $request->settlement_date_from);
            }

            if ($request->filled('settlement_date_to')) {
                $query->whereDate('settlement_time', '<=', $request->settlement_date_to);
            }
        }

        if ($request->filled('tab')) {
            if ($request->tab === 'cair') {
                $query->whereNotNull('settlement_time')
                      ->where(function ($q) {
                          $q->whereNull('drc_adjustable_refund')->orWhere('drc_adjustable_refund', 0);
                      })
                      ->whereDoesntHave('order', function ($q) {
                          $q->whereIn('order_status', ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);
                      });
            } elseif ($request->tab === 'belum_cair') {
                $query->whereNull('settlement_time')
                      ->where(function ($q) {
                          $q->whereNull('drc_adjustable_refund')->orWhere('drc_adjustable_refund', 0);
                      });

                if ($request->filled('sub_tab')) {
                    if ($request->sub_tab === 'shipped') {
                        $query->whereHas('order', function ($q) {
                            $q->whereIn('order_status', ['SHIPPED', 'DIKIRIM']);
                        });
                    } elseif ($request->sub_tab === 'to_confirm') {
                        $query->whereHas('order', function ($q) {
                            $q->whereIn('order_status', ['TO_CONFIRM_RECEIVE', 'COMPLETED', 'SELESAI']);
                        });
                    } elseif ($request->sub_tab === 'returning') {
                        $query->whereHas('order', function ($q) {
                            $q->whereIn('order_status', ['TO_RETURN']);
                        });
                    } elseif ($request->sub_tab === 'return') {
                        $query->whereHas('order', function ($q) {
                            $q->whereIn('order_status', ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);
                        });
                    }
                } else {
                    $query->whereDoesntHave('order', function ($q) {
                        $q->whereIn('order_status', ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);
                    });
                }
            } elseif ($request->tab === 'batal_return') {
                $query->where(function ($q) {
                    $q->whereHas('order', function ($q2) {
                        $q2->whereIn('order_status', ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);
                    })
                    ->orWhere(function ($q3) {
                        $q3->whereNotNull('drc_adjustable_refund')->where('drc_adjustable_refund', '!=', 0);
                    });
                });
            }
        }

        // Filter dropdown pada halaman settlement menggunakan settlement_status.
        // Tetap pisahkan dari tab lama agar keduanya dapat dipakai tanpa
        // mengubah perilaku filter tab yang sudah ada.
        if ($settlementStatus === 'cair') {
            $query->whereNotNull('settlement_time')
                ->where(function ($q) {
                    $q->whereNull('drc_adjustable_refund')->orWhere('drc_adjustable_refund', 0);
                })
                ->whereDoesntHave('order', function ($q) {
                    $q->whereIn('order_status', ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);
                });
        } elseif ($settlementStatus === 'belum_cair') {
            $query->whereNull('settlement_time')
                ->where(function ($q) {
                    $q->whereNull('drc_adjustable_refund')->orWhere('drc_adjustable_refund', 0);
                })
                ->whereDoesntHave('order', function ($q) {
                    $q->whereIn('order_status', ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);
                });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('channel_order_id', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($orderQuery) use ($search) {
                      $orderQuery->where('channel_order_id', 'like', "%{$search}%")
                          ->orWhereHas('items', function ($itemQuery) use ($search) {
                              $itemQuery->where(function ($itemFilter) use ($search) {
                                  $itemFilter->where('item_name', 'like', "%{$search}%")
                                      ->orWhere('variant_name', 'like', "%{$search}%")
                                      ->orWhere('model_sku', 'like', "%{$search}%")
                                      ->orWhere('item_sku', 'like', "%{$search}%")
                                      ->orWhere('marketplace_sku', 'like', "%{$search}%")
                                      ->orWhere('external_sku', 'like', "%{$search}%");
                              });
                          });
                  });
            });
        }

        $pendingRows = collect();
        if ($includeMissingPending) {
            $pendingOrdersQuery = MarketplaceOrder::with([
                'store:id,name,channel_id',
                'items:id,marketplace_order_id,hpp_snapshot,qty,item_name,variant_name,model_sku,item_sku,image_url,mapping_status,internal_item_id',
            ])
                ->whereDoesntHave('settlement')
                ->whereNotIn('order_status', ['UNPAID', 'CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);

            if ($request->filled('store_id')) {
                $pendingOrdersQuery->where('store_id', $request->store_id);
            }
            if ($request->filled('status')) {
                $pendingOrdersQuery->where('order_status', $request->status);
            }
            if ($request->input('cogs_zero') === '1') {
                $pendingOrdersQuery->where(function ($q) {
                    $q->doesntHave('items')->orWhereHas('items', function ($q2) {
                        $q2->whereNull('hpp_snapshot')->orWhere('hpp_snapshot', '<=', 0);
                    });
                });
            }
            if ($request->filled('order_date_from')) {
                $pendingOrdersQuery->whereDate('ordered_at', '>=', $request->order_date_from);
            }
            if ($request->filled('order_date_to')) {
                $pendingOrdersQuery->whereDate('ordered_at', '<=', $request->order_date_to);
            }
            if ($request->input('tab') === 'semua') {
                if ($request->filled('settlement_date_from')) {
                    $pendingOrdersQuery->whereDate('ordered_at', '>=', $request->settlement_date_from);
                }
                if ($request->filled('settlement_date_to')) {
                    $pendingOrdersQuery->whereDate('ordered_at', '<=', $request->settlement_date_to);
                }
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $pendingOrdersQuery->where(function ($q) use ($search) {
                    $q->where('channel_order_id', 'like', "%{$search}%")
                        ->orWhereHas('items', function ($itemQuery) use ($search) {
                            $itemQuery->where(function ($itemFilter) use ($search) {
                                $itemFilter->where('item_name', 'like', "%{$search}%")
                                    ->orWhere('variant_name', 'like', "%{$search}%")
                                    ->orWhere('model_sku', 'like', "%{$search}%")
                                    ->orWhere('item_sku', 'like', "%{$search}%")
                                    ->orWhere('marketplace_sku', 'like', "%{$search}%")
                                    ->orWhere('external_sku', 'like', "%{$search}%");
                            });
                        });
                });
            }

            $pendingRows = $pendingOrdersQuery->get()->map(function (MarketplaceOrder $order) {
                $settlement = new MarketplaceOrderSettlement([
                    'store_id' => $order->store_id,
                    'order_id' => $order->id,
                    'channel_order_id' => $order->channel_order_id,
                    'buyer_payment_amount' => $order->total_paid_customer ?? $order->subtotal_items ?? $order->total_amount ?? 0,
                    'final_income' => 0,
                    'settlement_time' => null,
                    'synced_at' => null,
                    'raw_json' => [],
                ]);
                $settlement->setRelation('store', $order->store);
                $settlement->setRelation('order', $order);
                $settlement->setAttribute('is_missing_settlement', true);

                return $settlement;
            });
        }

        $metaQuery = clone $query;
        $formatSettlement = function ($s) {
            $breakdown = $s->marketplaceFeeBreakdown();
            $feeTotals = $s->marketplaceFeeCategoryTotals($breakdown);
            $sellerBurdenTotal = (float) ($feeTotals['seller'] ?? 0);
            $buyerBurdenTotal = (float) ($feeTotals['buyer'] ?? 0);
            $platformBurdenTotal = (float) ($feeTotals['platform'] ?? 0);
            $voucherTokoAmount = $this->settlementVoucherTokoAmount($s);
            $adjustmentTotal = (float) ($feeTotals['adjustment'] ?? 0);
            $allBurdenTotal = (float) ($feeTotals['total'] ?? 0);
            $sellerDiscount = (float) data_get($s->raw_json, 'seller_discount', 0);
            $grossAmount = (float) ($s->order?->subtotal_items ?? $s->buyer_payment_amount) - $sellerDiscount;
            $buyerPaidAmount = $this->settlementBuyerPaidAmount($s);
            $voucherPlatformAmount = $this->settlementVoucherPlatformAmount($s);
            $voucherAmount = $voucherTokoAmount + $voucherPlatformAmount;
            $grossAfterVoucherTotal = max($grossAmount - $voucherAmount, 0);
            $grossAfterVoucherToko = max($grossAmount - $voucherTokoAmount, 0);
            $affiliateDisplay = (float) (
                data_get($s->raw_json, 'affiliate_commission')
                ?? data_get($s->raw_json, 'affiliate_commission_amount')
                ?? data_get($s->raw_json, 'affiliate_fee')
                ?? data_get($s->raw_json, 'affiliate_commission_fee')
                ?? data_get($s->raw_json, 'seller_affiliate_fee')
                ?? $s->activity_fee
                ?? 0
            );
            $feePercent = $grossAfterVoucherToko > 0 ? round(($sellerBurdenTotal / $grossAfterVoucherToko) * 100, 1) : 0.0;
            $affiliatePercent = $grossAfterVoucherToko > 0 ? round(($affiliateDisplay / $grossAfterVoucherToko) * 100, 1) : 0.0;
            $marketplaceFeeAfterAffiliate = max($sellerBurdenTotal - $affiliateDisplay, 0);
            $marketplaceFeePercent = $grossAfterVoucherToko > 0 ? round(($marketplaceFeeAfterAffiliate / $grossAfterVoucherToko) * 100, 1) : 0.0;
            $feePercentToko = $grossAfterVoucherToko > 0 ? round(($sellerBurdenTotal / $grossAfterVoucherToko) * 100, 1) : 0.0;
            $cogs = $s->order ? $s->order->items->sum(function ($item) { return (float) $item->hpp_snapshot * (float) $item->qty; }) : 0;

            $netIncome = (float) $s->final_income;
            $isEstimatedIncome = false;
            $status = strtoupper($s->order?->order_status ?? '');
            $isCancelledOrReturned = in_array($status, ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);
            $isReturning = in_array($status, ['TO_RETURN', 'RETURNING']);

            if ($isCancelledOrReturned) {
                $netIncome = 0;
                $cogs = 0;
            } elseif ($isReturning) {
                $netIncome = 0;
                // cogs tetap terisi
            } elseif ($netIncome <= 0 && $grossAfterVoucherToko > 0) {
                $estimatedFee = round($grossAfterVoucherToko * 0.24);
                $netIncome = max($grossAfterVoucherToko - $estimatedFee - $affiliateDisplay, 0);
                $isEstimatedIncome = true;
            }
            $grossProfit = $netIncome - $cogs;

            return [
                'id'                    => $s->id,
                'channel_order_id'      => $s->channel_order_id,
                'order_status'          => $s->order?->order_status,
                'ordered_at'            => $s->order?->ordered_at?->toISOString(),
                'store'                 => $s->store,
                'order'                 => $s->order,
                'buyer_payment_amount'  => (float) $s->buyer_payment_amount,
                'subtotal_items'        => (float) ($s->order?->subtotal_items ?? $s->buyer_payment_amount),
                'total_paid_customer'   => (float) ($s->order?->total_paid_customer ?? $s->buyer_payment_amount),
                'seller_discount'       => $sellerDiscount,
                'gross_amount'          => $grossAmount,
                'buyer_paid_amount'     => $buyerPaidAmount,
                'commission_fee'        => (float) $s->commission_fee,
                'service_fee'           => (float) $s->service_fee,
                'transaction_fee'       => (float) $s->transaction_fee,
                'affiliate_fee'         => (float) $s->affiliate_fee,
                'seller_voucher'        => (float) $s->seller_voucher,
                'voucher_platform_total' => $voucherPlatformAmount,
                'voucher_toko_total'    => $voucherTokoAmount,
                'voucher_total'         => $voucherAmount,
                'seller_coin_cash_back' => (float) $s->seller_coin_cash_back,
                'actual_shipping_fee'   => (float) $s->actual_shipping_fee,
                'shipping_fee_subsidy'  => (float) $s->shipping_fee_subsidy,
                'reverse_shipping_fee'  => (float) $s->reverse_shipping_fee,
                'premi'                 => (float) (data_get($s->raw_json, 'premi') ?? data_get($s->raw_json, 'shipping_insurance') ?? data_get($s->raw_json, 'insurance_fee') ?? 0),
                'seller_transaction_fee' => (float) (data_get($s->raw_json, 'seller_transaction_fee') ?? data_get($s->raw_json, 'transaction_fee') ?? 0),
                'seller_order_processing_fee' => (float) (data_get($s->raw_json, 'seller_order_processing_fee') ?? 0),
                'order_ams_commission_fee' => (float) (data_get($s->raw_json, 'order_ams_commission_fee') ?? data_get($s->raw_json, 'ams_commission_fee') ?? $s->activity_fee ?? 0),
                'biaya_affiliate'       => (float) (data_get($s->raw_json, 'affiliate_fee') ?? data_get($s->raw_json, 'affiliate_commission_fee') ?? data_get($s->raw_json, 'seller_affiliate_fee') ?? 0),
                'affiliate_commission_fee' => (float) (data_get($s->raw_json, 'affiliate_commission_fee') ?? data_get($s->raw_json, 'seller_affiliate_fee') ?? 0),
                'seller_affiliate_fee'  => (float) (data_get($s->raw_json, 'seller_affiliate_fee') ?? 0),
                'affiliate'             => (float) (data_get($s->raw_json, 'affiliate_commission') ?? data_get($s->raw_json, 'affiliate_commission_amount') ?? 0),
                'affiliate_display'     => $affiliateDisplay,
                'affiliate_percent'     => $affiliatePercent,
                'marketplace_fee_after_affiliate' => $marketplaceFeeAfterAffiliate,
                'marketplace_fee_percent' => $marketplaceFeePercent,
                'shipping_insurance_fee' => (float) $s->shipping_insurance_fee,
                'activity_fee'          => (float) $s->activity_fee,
                'drc_adjustable_refund' => (float) $s->drc_adjustable_refund,
                'escrow_tax'            => (float) $s->escrow_tax,
                'ad_cost'               => (float) $s->ad_cost,
                'final_income'          => (float) $netIncome,
                'is_estimated_income'   => $isEstimatedIncome,
                'cogs'                  => (float) $cogs,
                'gross_profit'          => (float) $grossProfit,
                'gross_after_voucher'   => $grossAfterVoucherTotal,
                'gross_after_voucher_toko' => $grossAfterVoucherToko,
                'fee_total'             => $sellerBurdenTotal,
                'fee_breakdown_total'   => $sellerBurdenTotal,
                'seller_burden_total'   => $sellerBurdenTotal,
                'buyer_burden_total'    => $buyerBurdenTotal,
                'platform_burden_total' => $platformBurdenTotal,
                'adjustment_total'      => $adjustmentTotal,
                'total_burden_total'    => $allBurdenTotal,
                'fee_breakdown'         => $breakdown,
                'fee_percent'           => $feePercent,
                'fee_percent_toko'      => $feePercentToko,
                'settlement_status'    => $s->settlement_time ? 'cair' : 'belum_cair',
                'settlement_recorded'  => ! (bool) $s->getAttribute('is_missing_settlement'),
                'settlement_time'       => $s->settlement_time?->toISOString(),
                'synced_at'             => $s->synced_at?->toISOString(),
                'raw_json'              => is_string($s->raw_json) ? json_decode($s->raw_json, true) : $s->raw_json,
            ];
        };

        if ($includeMissingPending) {
            $allRows = $query->get()->concat($pendingRows);
            $sortValue = function ($s) use ($sortBy) {
                if ($sortBy === 'ordered_at') {
                    return $s->order?->ordered_at?->timestamp ?? 0;
                }
                if ($sortBy === 'final_income') {
                    return (float) $s->final_income;
                }
                if ($sortBy === 'buyer_payment_amount') {
                    return (float) $s->buyer_payment_amount;
                }

                return $s->settlement_time?->timestamp ?? $s->order?->ordered_at?->timestamp ?? 0;
            };
            $allRows = ($sortDir === 'asc' ? $allRows->sortBy($sortValue) : $allRows->sortByDesc($sortValue))->values();

            $perPage = (int) $request->input('per_page', 50);
            $page = max((int) $request->input('page', 1), 1);
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $allRows->forPage($page, $perPage)->values()->map($formatSettlement),
                $allRows->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $metaRowsForSettlement = $allRows;
        } else {
            $paginator = $query->paginate($request->input('per_page', 50));
            $paginator->setCollection($paginator->getCollection()->map($formatSettlement));
            $metaRowsForSettlement = null;
        }

        if ($request->input('page', 1) == 1) {
            if ($includeMissingPending) {
                $metaRows = $metaRowsForSettlement;
            } else {
                $metaQuery->with('order.items:id,marketplace_order_id,hpp_snapshot,qty');
                $metaRows = $metaQuery->get([
                'id',
                'buyer_payment_amount',
                'order_id',
                'store_id',
                'commission_fee',
                'service_fee',
                'transaction_fee',
                'affiliate_fee',
                'seller_voucher',
                'seller_coin_cash_back',
                'actual_shipping_fee',
                'shipping_fee_subsidy',
                'reverse_shipping_fee',
                'shipping_insurance_fee',
                'activity_fee',
                'drc_adjustable_refund',
                'escrow_tax',
                'ad_cost',
                'final_income',
                'raw_json',
                ]);
            }
            $feeTotals = $metaRows->reduce(function (array $carry, MarketplaceOrderSettlement $settlement) {
                $totals = $settlement->marketplaceFeeCategoryTotals($settlement->marketplaceFeeBreakdown());
                $carry['seller'] += (float) ($totals['seller'] ?? 0);
                $carry['buyer'] += (float) ($totals['buyer'] ?? 0);
                $carry['platform'] += (float) ($totals['platform'] ?? 0);
                $carry['voucher'] += (float) ($totals['voucher'] ?? 0);
                $carry['adjustment'] += (float) ($totals['adjustment'] ?? 0);
                $carry['total'] += (float) ($totals['total'] ?? 0);
                return $carry;
            }, ['seller' => 0.0, 'buyer' => 0.0, 'platform' => 0.0, 'voucher' => 0.0, 'adjustment' => 0.0, 'total' => 0.0]);
            $sellerFeeTotal = (float) ($feeTotals['seller'] ?? 0);
            $buyerFeeTotal = (float) ($feeTotals['buyer'] ?? 0);
            $platformFeeTotal = (float) ($feeTotals['platform'] ?? 0);
            $voucherFeeTotal = (float) ($feeTotals['voucher'] ?? 0);
            $adjustmentFeeTotal = (float) ($feeTotals['adjustment'] ?? 0);
            $allFeeTotal = (float) ($feeTotals['total'] ?? 0);
            $grossTotal = (float) $metaRows->sum(function (MarketplaceOrderSettlement $settlement) {
                $sellerDiscount = (float) data_get($settlement->raw_json, 'seller_discount', 0);
                return (float) ($settlement->order?->subtotal_items ?? $settlement->buyer_payment_amount) - $sellerDiscount;
            });
            $buyerPaidTotal = (float) $metaRows->sum(function (MarketplaceOrderSettlement $settlement) {
                return (float) ($settlement->order?->total_paid_customer ?? $settlement->buyer_payment_amount);
            });
            $voucherTokoTotal = (float) $metaRows->sum(fn (MarketplaceOrderSettlement $settlement) => $this->settlementVoucherTokoAmount($settlement));
            $voucherPlatformTotal = (float) $metaRows->sum(fn (MarketplaceOrderSettlement $settlement) => $this->settlementVoucherPlatformAmount($settlement));
            $buyerPaidTotal = (float) $metaRows->sum(fn (MarketplaceOrderSettlement $settlement) => $this->settlementBuyerPaidAmount($settlement));
            $grossAfterVoucher = max($grossTotal - ($voucherTokoTotal + $voucherPlatformTotal), 0);
            $grossAfterVoucherToko = max($grossTotal - $voucherTokoTotal, 0);
            $countSelesai = 0;
            $countBatal = 0;
            $countPenyesuaian = 0;
            $countShipped = 0;
            $countToConfirm = 0;
            $countReturning = 0;
            $countUnsettled = 0;
            $sellerFeeTotal = 0.0;
            $kpiNet = 0.0;
            $kpiCogs = 0.0;
            $kpiAffiliate = 0.0;
            $kpiMarketplace = 0.0;
            foreach ($metaRows as $s) {
                $st = strtoupper($s->order?->order_status ?? '');
                $isCompleted = $st === 'COMPLETED';
                $isShipped = in_array($st, ['SHIPPED', 'DIKIRIM']);
                $isToConfirm = in_array($st, ['TO_CONFIRM_RECEIVE', 'COMPLETED']);
                $isReturning = in_array($st, ['TO_RETURN', 'RETURNING']);
                if ($st === 'COMPLETED') {
                    $countSelesai++;
                }
                if ($isShipped) {
                    $countShipped++;
                }
                if ($isToConfirm) {
                    $countToConfirm++;
                }
                if ($isReturning) {
                    $countReturning++;
                }
                if (is_null($s->settlement_time) && ! in_array($st, ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND'])) {
                    $countUnsettled++;
                }
                if (in_array($st, ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND'])) {
                    $countBatal++;
                }
                $breakdown = $s->marketplaceFeeBreakdown();
                $feeTots = $s->marketplaceFeeCategoryTotals($breakdown);
                if (($feeTots['adjustment'] ?? 0) < 0) {
                    $countPenyesuaian++;
                }

                $cogs = $s->order ? $s->order->items->sum(function ($item) { return (float) $item->hpp_snapshot * (float) $item->qty; }) : 0;
                $isCancelledOrReturned = in_array($st, ['CANCELLED', 'BATAL', 'RETURNED', 'REFUND']);
                $isReturning = in_array($st, ['TO_RETURN', 'RETURNING']);

                if ($isCancelledOrReturned) {
                    $cogs = 0;
                }
                $kpiCogs += $cogs;

                if ($isCancelledOrReturned || $isReturning) {
                    // Jika batal/return atau sedang dikembalikan, maka tidak ada pendapatan dan potongan.
                } elseif ((float) $s->final_income <= 0) {
                    $sellerDiscount = (float) data_get($s->raw_json, 'seller_discount', 0);
                    $grossAmt = (float) ($s->order?->subtotal_items ?? $s->buyer_payment_amount) - $sellerDiscount;
                    $tokoVoucher = $this->settlementVoucherTokoAmount($s);
                    $grossAfterVT = max($grossAmt - $tokoVoucher, 0);

                    $affiliateCommission = (float) (
                        data_get($s->raw_json, 'affiliate_commission')
                        ?? data_get($s->raw_json, 'affiliate_commission_amount')
                        ?? data_get($s->raw_json, 'affiliate_fee')
                        ?? data_get($s->raw_json, 'affiliate_commission_fee')
                        ?? data_get($s->raw_json, 'seller_affiliate_fee')
                        ?? $s->activity_fee
                        ?? 0
                    );

                    $estimatedFee = round($grossAfterVT * 0.24);
                    $estimatedNet = max($grossAfterVT - $estimatedFee - $affiliateCommission, 0);

                    $kpiAffiliate += $affiliateCommission;
                    $kpiMarketplace += $estimatedFee;
                    $sellerFeeTotal += $estimatedFee + $affiliateCommission;
                    $kpiNet += $estimatedNet;
                } else {
                    $affiliateCommission = (float) (
                        data_get($s->raw_json, 'affiliate_commission')
                        ?? data_get($s->raw_json, 'affiliate_commission_amount')
                        ?? data_get($s->raw_json, 'affiliate_fee')
                        ?? data_get($s->raw_json, 'affiliate_commission_fee')
                        ?? data_get($s->raw_json, 'seller_affiliate_fee')
                        ?? $s->activity_fee
                        ?? 0
                    );
                    $feeSeller = (float) ($feeTots['seller'] ?? 0);
                    $kpiAffiliate += $affiliateCommission;
                    $kpiMarketplace += max($feeSeller - $affiliateCommission, 0);
                    $sellerFeeTotal += $feeSeller;
                    $kpiNet += (float) $s->final_income;
                }
            }

            $feePercent = $grossAfterVoucherToko > 0 ? round(($sellerFeeTotal / $grossAfterVoucherToko) * 100, 1) : 0.0;
            $feePercentToko = $grossAfterVoucherToko > 0 ? round(($sellerFeeTotal / $grossAfterVoucherToko) * 100, 1) : 0.0;

            $meta = [
                'kpi_count'             => (int) $metaRows->count(),
                'kpi_count_selesai'     => $countSelesai,
                'kpi_count_batal'       => $countBatal,
                'kpi_count_penyesuaian' => $countPenyesuaian,
                'kpi_count_shipped'     => $countShipped,
                'kpi_count_to_confirm'  => $countToConfirm,
                'kpi_count_returning'   => $countReturning,
                'kpi_count_unsettled'   => $countUnsettled,
                'kpi_gross'             => $grossTotal,
                'kpi_buyer_paid'        => $buyerPaidTotal,
                'kpi_voucher'           => $voucherTokoTotal + $voucherPlatformTotal,
                'kpi_voucher_toko'      => $voucherTokoTotal,
                'kpi_voucher_platform'  => $voucherPlatformTotal,
                'kpi_gross_after_voucher' => $grossAfterVoucher,
                'kpi_gross_after_voucher_toko' => $grossAfterVoucherToko,
                'kpi_net'               => $kpiNet,
                'kpi_cogs'              => $kpiCogs,
                'kpi_gross_profit'      => $kpiNet - $kpiCogs,
                'kpi_aov'               => $metaRows->count() > 0 ? round($buyerPaidTotal / $metaRows->count()) : 0,
                'kpi_fees'              => $sellerFeeTotal,
                'kpi_affiliate'         => $kpiAffiliate,
                'kpi_marketplace'       => $kpiMarketplace,
                'kpi_seller_burden'     => $sellerFeeTotal,
                'kpi_buyer_burden'      => $buyerFeeTotal,
                'kpi_platform_burden'   => $platformFeeTotal,
                'kpi_adjustment_total'  => $adjustmentFeeTotal,
                'kpi_total_burden'      => $allFeeTotal,
                'kpi_fee_pct'           => $feePercent,
                'kpi_fee_pct_toko'      => $feePercentToko,
            ];
        } else {
            $meta = null; // Frontend can preserve previous KPI state on page change
        }

        return response()->json([
            'paginator' => $paginator,
            'meta'      => $meta
        ]);
    }

    /**
     * Build a dynamic fee breakdown from synced settlement payload.
     * Known fee fields are mapped to friendly labels; any new fee-like field
     * from the platform is surfaced as "Biaya Tambahan (field_name)" so we do
     * not silently lose money-related detail when the platform adds columns.
     */
    private function buildSettlementFeeBreakdown(MarketplaceOrderSettlement $s): array
    {
        return $s->marketplaceFeeBreakdown();
    }

    private function settlementFeeAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        if (is_numeric($value)) {
            return abs((float) $value);
        }

        $normalized = str_replace(['Rp', 'rp', ' '], '', (string) $value);
        $normalized = preg_replace('/[^0-9,\.\-]/', '', $normalized) ?? '';
        if ($normalized === '' || $normalized === '-') {
            return 0.0;
        }

        $normalized = str_replace(',', '', $normalized);

        return abs((float) $normalized);
    }

    private function settlementVoucherPlatformAmount(MarketplaceOrderSettlement $s): float
    {
        $raw = is_array($s->raw_json) ? $s->raw_json : [];

        foreach (['voucher_from_shopee', 'voucher_from_platform', 'platform_voucher'] as $key) {
            $value = data_get($raw, $key);
            if ($value !== null && $value !== '') {
                return $this->settlementFeeAmount($value);
            }
        }

        return 0.0;
    }

    private function settlementBuyerPaidAmount(MarketplaceOrderSettlement $s): float
    {
        $raw = is_array($s->raw_json) ? $s->raw_json : [];

        foreach (['buyer_total_amount', 'buyer_paid_amount', 'total_paid_customer'] as $key) {
            $value = data_get($raw, $key);
            if ($value !== null && $value !== '') {
                return $this->settlementFeeAmount($value);
            }
        }

        $orderPaid = $s->order?->total_paid_customer;
        if ($orderPaid !== null && $orderPaid !== '') {
            return (float) $orderPaid;
        }

        return (float) $s->buyer_payment_amount;
    }

    private function settlementVoucherTokoAmount(MarketplaceOrderSettlement $s): float
    {
        $raw = is_array($s->raw_json) ? $s->raw_json : [];

        foreach (['voucher_from_seller', 'seller_voucher_rebate', 'seller_voucher'] as $key) {
            $value = data_get($raw, $key);
            if ($value !== null && $value !== '') {
                return $this->settlementFeeAmount($value);
            }
        }

        return abs((float) $s->seller_voucher);
    }

    private function settlementAdjustmentAmount(MarketplaceOrderSettlement $s): float
    {
        $raw = is_array($s->raw_json) ? $s->raw_json : [];

        foreach (['drc_adjustable_refund', 'seller_return_refund_amount', 'seller_return_refund'] as $key) {
            $value = data_get($raw, $key);
            if ($value !== null && $value !== '') {
                return $this->settlementFeeAmount($value);
            }
        }

        return abs((float) $s->drc_adjustable_refund);
    }

    private function looksLikeFeeField(string $key): bool
    {
        $key = strtolower($key);

        return (bool) preg_match('/(?:fee|commission|tax|insurance|premi|campaign|promo|refund|adjust|surcharge|charge|deduct|potongan|subsidy|ads?|ad_cost)/i', $key)
            && ! preg_match('/(?:buyer_|voucher|coin|gross|net|payment|amount_paid|buyer_total|order_selling_price|cost_of_goods_sold|escrow_amount|settlement_time|create_time|update_time)/i', $key);
    }

    /**
     * Field yang memang ada di payload tetapi bukan potongan seller.
     * Ini sengaja dikeluarkan dari breakdown agar UI tidak menyesatkan.
     */
    private function isExcludedFromSellerFeeBreakdown(string $key): bool
    {
        $key = strtolower($key);

        return in_array($key, [
            'actual_shipping_fee',
            'estimated_shipping_fee',
            'credit_card_transaction_fee',
            'buyer_paid_shipping_fee',
            'shipping_fee_subsidy',
        ], true);
    }

    public function purgeSettlements(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'owner', 403, 'Hanya owner yang boleh menghapus data settlement.');

        $data = $request->validate([
            'confirm' => ['required', 'string'],
        ]);

        $confirmText = trim(strtoupper($data['confirm']));
        if ($confirmText !== 'HAPUS SEMUA SETTLEMENT') {
            return response()->json([
                'message' => 'Konfirmasi tidak cocok. Ketik "HAPUS SEMUA SETTLEMENT" untuk melanjutkan.',
            ], 422);
        }

        // Penghapusan ini sengaja dibuat secepat mungkin untuk kebutuhan testing.
        // Delete langsung lebih ringan daripada count + delete, dan set_time_limit(0)
        // mencegah request gagal di PHP timeout 30 detik saat data settlement besar.
        @set_time_limit(0);

        $deleted = 0;
        $logsDeleted = 0;
        $ordersReset = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use (&$deleted, &$logsDeleted, &$ordersReset) {
            $deleted = MarketplaceOrderSettlement::query()->delete();

            $logsDeleted = \App\Models\MarketplaceSyncLog::query()
                ->where('action', 'sync_settlements')
                ->delete();

            $ordersReset = \Illuminate\Support\Facades\DB::table('marketplace_orders')
                ->where(function ($q) {
                    $q->whereNotNull('settlement_sync_error_code')
                      ->orWhereNotNull('settlement_sync_failed_at');
                })
                ->update([
                    'settlement_sync_error_code' => null,
                    'settlement_sync_failed_at' => null,
                    'settlement_sync_last_attempt_at' => null,
                ]);
        });

        foreach (Store::query()->pluck('id') as $storeId) {
            Cache::forget("marketplace:settlement_sync_progress:{$storeId}");
        }

        return response()->json([
            'message' => $deleted > 0 || $logsDeleted > 0 || $ordersReset > 0
                ? "Semua data settlement dan data terkait berhasil dihapus. Settlement: {$deleted}, log sync: {$logsDeleted}, flag order di-reset: {$ordersReset}."
                : 'Tidak ada data settlement atau data terkait untuk dihapus.',
            'deleted'       => $deleted,
            'logs_deleted'  => $logsDeleted,
            'orders_reset'   => $ordersReset,
        ]);
    }

    public function purgeMarketplaceData(Request $request, Store $store): JsonResponse
    {
        abort_unless($request->user()?->role === 'owner', 403, 'Hanya owner yang boleh menghapus data marketplace.');

        $data = $request->validate([
            'confirm' => ['required', 'string'],
        ]);

        $confirmText = trim(strtoupper($data['confirm']));
        if ($confirmText !== 'HAPUS SEMUA DATA MARKETPLACE') {
            return response()->json([
                'message' => 'Konfirmasi tidak cocok. Ketik "HAPUS SEMUA DATA MARKETPLACE" untuk melanjutkan.',
            ], 422);
        }

        @set_time_limit(0);

        $legacyStoreIds = $this->resolveLegacyMarketplaceStoreIds($store);
        $legacyOrderIds = DB::table('marketplace_orders')
            ->whereIn('store_id', $legacyStoreIds)
            ->pluck('id')
            ->all();

        $productIds = DB::table('marketplace_products')
            ->where('store_id', $store->id)
            ->pluck('id')
            ->all();

        $campaignIds = DB::table('marketplace_ad_campaigns')
            ->where('store_id', $store->id)
            ->pluck('id')
            ->all();

        $returnIds = DB::table('marketplace_returns')
            ->where('store_id', $store->id)
            ->pluck('id')
            ->all();

        $deleted = [
            'settlements'         => 0,
            'order_items'         => 0,
            'fulfillments'        => 0,
            'sync_logs'           => 0,
            'conversations'       => 0,
            'chat_messages'       => 0,
            'returns'             => 0,
            'return_items'        => 0,
            'bookings'            => 0,
            'product_models'      => 0,
            'product_dailies'     => 0,
            'products'            => 0,
            'ads_dailies'         => 0,
            'ads_balance_logs'    => 0,
            'ads_item_dailies'    => 0,
            'ads_hourly'          => 0,
            'ads_sync_runs'       => 0,
            'ads_settings'        => 0,
            'ad_item_maps'        => 0,
            'ad_campaign_dailies' => 0,
            'campaign_items'      => 0,
            'ad_campaigns'        => 0,
            'boost_logs'          => 0,
            'boost_schedules'     => 0,
            'boost_pool'          => 0,
            'mp_incomes'          => 0,
        ];

        DB::transaction(function () use ($store, $legacyOrderIds, $productIds, $campaignIds, $returnIds, &$deleted) {
            $deleted['settlements'] = DB::table('marketplace_order_settlements')
                ->where(function ($q) use ($store, $legacyOrderIds) {
                    $q->where('store_id', $store->id);
                    if (! empty($legacyOrderIds)) {
                        $q->orWhereIn('order_id', $legacyOrderIds);
                    }
                })
                ->delete();

            if (! empty($legacyOrderIds)) {
                $deleted['order_items'] = DB::table('marketplace_order_items')
                    ->whereIn('order_id', $legacyOrderIds)
                    ->delete();

                $deleted['fulfillments'] = DB::table('order_fulfillments')
                    ->whereIn('marketplace_order_id', $legacyOrderIds)
                    ->delete();
            }

            DB::table('marketplace_orders')
                ->where(function ($q) use ($store, $legacyOrderIds) {
                    $q->where('store_id', $store->id);
                    if (! empty($legacyOrderIds)) {
                        $q->orWhereIn('id', $legacyOrderIds);
                    }
                })
                ->update([
                    'settlement_sync_error_code' => null,
                    'settlement_sync_failed_at' => null,
                    'settlement_sync_last_attempt_at' => null,
                ]);

            $deleted['sync_logs'] = DB::table('marketplace_sync_logs')
                ->where('store_id', $store->id)
                ->whereNotIn('action', ['sync_orders', 'sync_finance'])
                ->delete();

            $deleted['chat_messages'] = DB::table('marketplace_chat_messages')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['conversations'] = DB::table('marketplace_conversations')
                ->where('store_id', $store->id)
                ->delete();

            if (! empty($returnIds)) {
                $deleted['return_items'] = DB::table('marketplace_return_items')
                    ->whereIn('marketplace_return_id', $returnIds)
                    ->delete();
            }

            $deleted['returns'] = DB::table('marketplace_returns')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['bookings'] = DB::table('marketplace_bookings')
                ->where('store_id', $store->id)
                ->delete();

            if (! empty($productIds)) {
                $deleted['product_models'] = DB::table('marketplace_product_models')
                    ->whereIn('marketplace_product_id', $productIds)
                    ->delete();
            }

            $deleted['product_dailies'] = DB::table('marketplace_product_dailies')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['products'] = DB::table('marketplace_products')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['ads_dailies'] = DB::table('marketplace_ads_dailies')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['ads_balance_logs'] = DB::table('marketplace_ads_balance_logs')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['ads_item_dailies'] = DB::table('marketplace_ads_item_dailies')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['ads_hourly'] = DB::table('marketplace_ads_hourly_performances')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['ads_sync_runs'] = DB::table('marketplace_ads_sync_runs')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['ads_settings'] = DB::table('marketplace_ads_settings')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['ad_item_maps'] = DB::table('marketplace_ad_item_maps')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['ad_campaign_dailies'] = DB::table('marketplace_ad_campaign_dailies')
                ->where('store_id', $store->id)
                ->delete();

            if (! empty($campaignIds)) {
                $deleted['campaign_items'] = DB::table('marketplace_ads_campaign_items')
                    ->whereIn('campaign_id', $campaignIds)
                    ->delete();
            }

            $deleted['ad_campaigns'] = DB::table('marketplace_ad_campaigns')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['boost_logs'] = DB::table('marketplace_boost_logs')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['boost_schedules'] = DB::table('marketplace_boost_schedules')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['boost_pool'] = DB::table('marketplace_boost_pool')
                ->where('store_id', $store->id)
                ->delete();

            $deleted['mp_incomes'] = DB::table('mp_incomes')
                ->where('store_id', $store->id)
                ->delete();
        });

        Cache::forget("marketplace:settlement_sync_progress:{$store->id}");
        Cache::forget("marketplace:sync_progress:{$store->id}");

        $totalDeleted = array_sum($deleted);

        return response()->json([
            'message' => $totalDeleted > 0
                ? "Data marketplace untuk {$store->name} berhasil dihapus. Order utama tetap disimpan, log sync_orders/sync_finance juga dipertahankan."
                : 'Tidak ada data marketplace untuk dihapus.',
            'store_id' => $store->id,
            'store_name' => $store->name,
            'deleted' => $deleted,
            'total_deleted' => $totalDeleted,
            'kept' => [
                'marketplace_orders' => true,
                'sync_orders_logs' => true,
                'sync_finance_logs' => true,
            ],
        ]);
    }

    public function orderProfits(Request $request): JsonResponse
    {
        $query = MarketplaceOrder::with([
            'store:id,name,channel_id',
            'store.channel:id,code,name',
            'settlement',
            'items:id,marketplace_order_id,model_sku,item_sku,qty,price,mapping_status,internal_item_id,hpp_snapshot',
        ])
        ->where(function($q) {
            $q->whereNotIn('order_status', ['UNPAID', 'CANCELLED'])
              ->orWhereHas('settlement');
        });

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('order_date_from')) {
            $query->whereDate('ordered_at', '>=', $request->order_date_from);
        }

        if ($request->filled('order_date_to')) {
            $query->whereDate('ordered_at', '<=', $request->order_date_to);
        }

        if ($request->filled('settlement_date_from')) {
            $query->whereHas('settlement', function ($q) use ($request) {
                $q->whereDate('settlement_time', '>=', $request->settlement_date_from);
            });
        }

        if ($request->filled('settlement_date_to')) {
            $query->whereHas('settlement', function ($q) use ($request) {
                $q->whereDate('settlement_time', '<=', $request->settlement_date_to);
            });
        }

        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        if ($request->filled('settlement_status')) {
            if ($request->settlement_status === 'cair') {
                $query->whereHas('settlement', function($q) {
                    $q->whereNotNull('settlement_time');
                });
            } elseif ($request->settlement_status === 'belum_cair') {
                $query->where(function($q) {
                    $q->doesntHave('settlement')
                      ->orWhereHas('settlement', function($q2) {
                          $q2->whereNull('settlement_time');
                      });
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('channel_order_id', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($qi) use ($search) {
                      $qi->where('item_name', 'like', "%{$search}%")
                         ->orWhere('model_sku', 'like', "%{$search}%")
                         ->orWhere('item_sku', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->get();

        // Pre-load HPP: build map sku → hpp_unit
        // Collect all unique SKUs from all items
        $allSkus = $orders->flatMap(fn ($o) => $o->items ?? collect())
            ->map(fn ($item) => $item->model_sku ?: $item->item_sku)
            ->filter()
            ->unique()
            ->values();

        $channelCode = $orders->first()?->store?->channel?->code;

        // Build sku → item_id map via SkuMapping
        $skuMappings = SkuMapping::whereIn('marketplace_sku', $allSkus)
            ->when($channelCode, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('channel_code', $channelCode)->orWhereNull('channel_code')
            ))
            ->get()
            ->groupBy('marketplace_sku');

        // Get item IDs and load active HPP snapshots
        $itemIds = $skuMappings->map(fn ($group) => $group->sortByDesc('channel_code')->first()->item_id)->unique();

        $costSnapshots = ItemCostSnapshot::whereIn('item_id', $itemIds)
            ->active()
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('item_id')
            ->map(fn ($snaps) => (float) $snaps->first()->unit_cost);

        // Build final sku → hpp_unit map
        $skuToHpp = [];
        foreach ($skuMappings as $sku => $group) {
            $itemId = $group->sortByDesc('channel_code')->first()->item_id;
            $skuToHpp[$sku] = $costSnapshots[$itemId] ?? null;
        }

        $rows = $orders->map(function ($order) use ($skuToHpp) {
            $s = $order->settlement;
            $items    = $order->items ?? collect();
            $hppTotal = 0.0;
            $hppMapped = true;

            $itemDetails = [];
            foreach ($items as $item) {
                $sku = $item->model_sku ?: $item->item_sku;
                $isMapped = $item->mapping_status === \App\Services\MarketplaceIssueService::MAPPING_MAPPED || !empty($item->internal_item_id);

                $hpp = (float) $item->hpp_snapshot;
                // Fallback to active snapshot if hpp_snapshot is 0
                if ($hpp <= 0 && $sku && isset($skuToHpp[$sku])) {
                    $hpp = (float) $skuToHpp[$sku];
                }

                if ($isMapped && $hpp > 0) {
                    $hppTotal += $hpp * (int) $item->qty;
                } else {
                    $hppMapped = false;
                }

                $itemDetails[] = [
                    'sku' => $sku ?: 'No SKU',
                    'qty' => (int) $item->qty,
                    'mapped' => $isMapped && $hpp > 0
                ];
            }

            $rawJson = $s && $s->raw_json ? $s->raw_json : $order->raw_json;
            if (is_string($rawJson)) {
                $rawJson = json_decode($rawJson, true);
            }

            $baseAmount = (float) ($order->total_paid_customer > 0 ? $order->total_paid_customer : $order->total_amount);
            $inc = $rawJson['income_details'] ?? [];
            $escrowAmount = (float)($inc['escrow_amount'] ?? $rawJson['payment_info']['net_revenue'] ?? 0);
            $isCompleted = in_array(strtoupper($order->order_status ?: $order->status), ['COMPLETED', 'SELESAI']);

            if ($s && $s->final_income !== null && (float)$s->final_income > 0) {
                $finalIncome = (float) $s->final_income;
            } else if ($isCompleted && $escrowAmount > 0) {
                $finalIncome = $escrowAmount;
            } else if ($isCompleted && $order->net_payout_estimated > 0) {
                $finalIncome = (float) $order->net_payout_estimated;
            } else {
                // Estimasi potong admin fee 24% (Hanya untuk belum selesai / belum ada data)
                $finalIncome = $baseAmount * 0.76;
            }

            $adCost      = $s ? (float) $s->ad_cost : 0.0;
            $profitNet   = $finalIncome - $hppTotal - $adCost;
            $buyerPayment = $s ? (float) $s->buyer_payment_amount : ($isCompleted && !empty($inc['buyer_total_amount']) ? (float)$inc['buyer_total_amount'] : $baseAmount);

            $omzetGross = (float) ($inc['cost_of_goods_sold'] ?? $inc['order_selling_price'] ?? $rawJson['cost_of_goods_sold'] ?? $rawJson['order_selling_price'] ?? $buyerPayment);

            $data = [
                'id'                    => $s ? $s->id : null,
                'channel_order_id'      => $order->channel_order_id,
                'store'                 => $order->store ? ['id' => $order->store->id, 'name' => $order->store->name] : null,
                'order'                 => [
                    'id'           => $order->id,
                    'order_status' => $order->order_status,
                    'ordered_at'   => $order->ordered_at?->toISOString(),
                ],
                'items'                 => $itemDetails,
                'buyer_payment_amount'  => $buyerPayment,
                'final_income'          => $finalIncome,
                'hpp_total'             => $hppTotal,
                'hpp_mapped'            => $hppMapped,
                'ad_cost'               => $adCost,
                'profit_gross'          => $finalIncome - $hppTotal,  // before ad cost
                'profit_net'            => $profitNet,
                'margin_pct'            => $omzetGross > 0
                    ? round($profitNet / $omzetGross * 100, 1)
                    : null,
                'settlement_time'       => $s ? $s->settlement_time?->toISOString() : null,
                'raw_json'              => $rawJson,
                // Detail potongan (untuk tooltip)
                'commission_fee'        => $s ? (float) $s->commission_fee : 0.0,
                'service_fee'           => $s ? (float) $s->service_fee : 0.0,
                'transaction_fee'       => $s ? (float) $s->transaction_fee : 0.0,
                'activity_fee'          => $s ? (float) $s->activity_fee : 0.0,
                'seller_voucher'        => $s ? (float) $s->seller_voucher : 0.0,
                'seller_coin_cash_back' => $s ? (float) $s->seller_coin_cash_back : 0.0,
                'shipping_fee_subsidy'  => $s ? (float) $s->shipping_fee_subsidy : 0.0,
            ];

            $itemsDiscount = 0;
            if (isset($rawJson['items']) && is_array($rawJson['items'])) {
                foreach ($rawJson['items'] as $it) {
                    $sellPrice = (float)($it['selling_price'] ?? 0);
                    $discPrice = (float)($it['discounted_price'] ?? 0);
                    if ($sellPrice > $discPrice) {
                        $itemsDiscount += ($sellPrice - $discPrice);
                    }
                }
            }

            $data['raw_json'] = $rawJson;
            $data['seller_discount'] = $itemsDiscount;

            return $data;
        });

        if ($request->filled('hpp_status')) {
            if ($request->hpp_status === 'empty') {
                $rows = $rows->where('hpp_mapped', false)->values();
            } elseif ($request->hpp_status === 'mapped') {
                $rows = $rows->where('hpp_mapped', true)->values();
            }
        }

        // 1. Calculate Global KPIs
        $kpiOmzet = 0;
        $kpiHpp = 0;
        $kpiNet = 0;
        $kpiProfit = 0;
        $kpiCount = $rows->count();

        foreach ($rows as $row) {
            $inc = $row['raw_json']['income_details'] ?? [];
            $omzetGross = (float)($inc['cost_of_goods_sold'] ?? $inc['order_selling_price'] ?? $row['raw_json']['cost_of_goods_sold'] ?? $row['raw_json']['order_selling_price'] ?? $row['buyer_payment_amount']);

            $kpiOmzet += $omzetGross;
            $kpiHpp += (float) $row['hpp_total'];
            $kpiNet += (float) $row['final_income'];
            $kpiProfit += (float) $row['profit_net'];
        }
        $kpiMargin = $kpiOmzet > 0 ? round(($kpiProfit / $kpiOmzet) * 100, 1) : null;
        $avgProfit = $kpiCount > 0 ? round($kpiProfit / $kpiCount) : 0;

        /*
        | Biaya iklan — ditarik dari marketplace_ads_dailies (sumber yang sama
        | dengan Ads Dashboard). Basis rentang = TANGGAL PESANAN DIBUAT.
        | Dikali 1.11 karena topup iklan kena PPN 11%. Profit final = profit +
        | ad_cost manual per-order (di-nol-kan dulu agar tidak dobel) - iklan+PPN.
        */
        $adsFrom = $request->input('order_date_from');
        $adsTo   = $request->input('order_date_to');

        // Tanpa filter tanggal order eksplisit, ikuti min-max tanggal pesanan
        // dari baris HASIL filter, dikonversi ke timezone aplikasi.
        if (!$adsFrom || !$adsTo) {
            $orderDates = $rows->pluck('order.ordered_at')
                ->filter()
                ->map(fn ($iso) => \Carbon\Carbon::parse($iso)
                    ->timezone(config('app.timezone'))
                    ->toDateString());
            if ($orderDates->isNotEmpty()) {
                $adsFrom = $adsFrom ?: $orderDates->min();
                $adsTo   = $adsTo   ?: $orderDates->max();
            }
        }

        $kpiAdsSpend = 0.0;
        if ($adsFrom && $adsTo) {
            $kpiAdsSpend = (float) \App\Models\MarketplaceAdsDaily::query()
                ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->store_id))
                ->whereDate('date', '>=', $adsFrom)
                ->whereDate('date', '<=', $adsTo)
                ->sum('spend');
        }
        $kpiAdsTotal = round($kpiAdsSpend * 1.11, 2); // + PPN 11%

        $kpiAdCostManual = (float) $rows->sum(fn ($r) => (float) $r['ad_cost']);
        $kpiProfitFinal  = $kpiProfit + $kpiAdCostManual - $kpiAdsTotal;
        $kpiMarginFinal  = $kpiOmzet > 0 ? round(($kpiProfitFinal / $kpiOmzet) * 100, 1) : null;
        $avgProfitFinal  = $kpiCount > 0 ? round($kpiProfitFinal / $kpiCount) : 0;

        // 2. Sort the Collection
        if ($request->filled('sort')) {
            $sort = $request->sort;
            if ($sort === 'margin_asc') $rows = $rows->sortBy('margin_pct')->values();
            elseif ($sort === 'margin_desc') $rows = $rows->sortByDesc('margin_pct')->values();
            elseif ($sort === 'profit_asc') $rows = $rows->sortBy('profit_net')->values();
            elseif ($sort === 'profit_desc') $rows = $rows->sortByDesc('profit_net')->values();
            elseif ($sort === 'date_asc') $rows = $rows->sortBy(function ($r) { return $r['settlement_time'] ?? $r['order']['ordered_at'] ?? ''; })->values();
            elseif ($sort === 'date_desc') $rows = $rows->sortByDesc(function ($r) { return $r['settlement_time'] ?? $r['order']['ordered_at'] ?? ''; })->values();
        } else {
            // Default sort: latest settlement time or ordered at
            $rows = $rows->sortByDesc(function ($r) { return $r['settlement_time'] ?? $r['order']['ordered_at'] ?? ''; })->values();
        }

        // 3. Export to CSV if requested
        if ($request->export === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="profit_export.csv"',
            ];

            $callback = function() use ($rows) {
                $file = fopen('php://output', 'w');
                // CSV Header
                fputcsv($file, ['Order SN', 'Toko', 'Status', 'Tgl Order', 'Tgl Cair', 'Harga Jual', 'Promosi Seller (Voucher)', 'Promosi Seller (Koin)', 'Dana Cair', 'HPP', 'Profit', 'Margin %']);

                foreach ($rows as $row) {
                    $inc = $row['raw_json']['income_details'] ?? [];
                    $omzetGross = (float)($inc['cost_of_goods_sold'] ?? $inc['order_selling_price'] ?? $row['raw_json']['cost_of_goods_sold'] ?? $row['raw_json']['order_selling_price'] ?? $row['buyer_payment_amount']);

                    fputcsv($file, [
                        $row['channel_order_id'],
                        $row['store']['name'] ?? '',
                        $row['order']['order_status'] ?? '',
                        $row['order']['ordered_at'] ?? '',
                        $row['settlement_time'] ?? 'Belum Cair',
                        $omzetGross,
                        $row['seller_voucher'],
                        $row['seller_coin_cash_back'],
                        $row['final_income'],
                        $row['hpp_total'],
                        $row['profit_net'],
                        $row['margin_pct']
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // 4. Manual Pagination
        $perPage = (int) $request->input('per_page', 50);
        $page = (int) $request->input('page', 1);
        $pagedData = $rows->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($pagedData, $kpiCount, $perPage, $page);

        $lastSync = \App\Models\MarketplaceSyncLog::whereIn('action', ['sync_finance', 'sync_settlements'])
            ->latest()
            ->value('created_at');

        return response()->json([
            'paginator' => $paginator,
            'meta'      => [
                'kpi_omzet'        => $kpiOmzet,
                'kpi_hpp'          => $kpiHpp,
                'kpi_net'          => $kpiNet,
                'kpi_profit'       => $kpiProfit,
                'kpi_margin'       => $kpiMargin,
                'avg_profit'       => $avgProfit,
                'kpi_ads_spend'    => $kpiAdsSpend,
                'kpi_ads_total'    => $kpiAdsTotal,
                'kpi_profit_final' => $kpiProfitFinal,
                'kpi_margin_final' => $kpiMarginFinal,
                'avg_profit_final' => $avgProfitFinal,
                'kpi_count'        => $kpiCount,
                'last_sync'        => $lastSync ? $lastSync->toISOString() : null
            ]
        ]);
    }

    public function updateSettlementAdCost(Request $request, MarketplaceOrderSettlement $settlement): JsonResponse
    {
        $data = $request->validate([
            'ad_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $settlement->update(['ad_cost' => $data['ad_cost']]);

        return response()->json(['message' => 'Ad cost diperbarui.', 'ad_cost' => (float) $settlement->ad_cost]);
    }

    public function syncAdCampaigns(Request $request, Store $store): JsonResponse
    {
        set_time_limit(180); // 3 menit — banyak campaign

        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        if (app()->environment('local') && function_exists('exec') && false === stripos(ini_get('disable_functions'), 'exec')) {
            try {
                $result = $this->sync->syncAdCampaigns($store, $dateFrom, $dateTo);
                return response()->json($result);
            } catch (\Throwable $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        // Production: dispatch ke background menggunakan Job khusus (dengan timeout besar)
        \App\Jobs\SyncAdCampaignsJob::dispatch($store, $dateFrom, $dateTo);

        return response()->json([
            'status' => 'success',
            'message' => 'Proses sinkronisasi campaign berjalan di background.'
        ]);
    }

    /**
     * Saldo iklan (ads credit) sebuah toko — v2.ads.get_total_balance
     */
    public function adsBalance(Store $store): JsonResponse
    {
        $res = $this->gateway->getAdsTotalBalance($store);

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? $res['error']], 422);
        }

        $data = $res['response'] ?? [];

        return response()->json([
            'balance'  => $data['total_balance'] ?? $data['balance'] ?? null,
            'currency' => $data['currency'] ?? 'IDR',
            'raw'      => $data,
        ]);
    }

    /**
     * Performa iklan harian level toko — v2.ads.get_all_cpc_ads_daily_performance
     */
    public function adsShopPerformance(Request $request, Store $store): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $res = $this->gateway->getAdsShopDailyPerformance(
            $store,
            \Carbon\Carbon::parse($dateFrom)->format('d-m-Y'),
            \Carbon\Carbon::parse($dateTo)->format('d-m-Y'),
        );

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? $res['error']], 422);
        }

        // Bentuk respons bervariasi antar region: response.day_list / array langsung
        $days = data_get($res, 'response.day_list')
            ?? data_get($res, 'response.daily_performance')
            ?? (is_array($res['response'] ?? null) && array_is_list($res['response']) ? $res['response'] : []);

        $rows = collect($days)->map(fn ($d) => [
            'date'        => $d['date'] ?? null,
            'impressions' => $d['impression'] ?? $d['impressions'] ?? 0,
            'clicks'      => $d['clicks'] ?? $d['click'] ?? 0,
            'ctr'         => $d['ctr'] ?? null,
            'spend'       => $d['expense'] ?? $d['spend'] ?? 0,
            'orders'      => $d['broad_order'] ?? $d['orders'] ?? 0,
            'gmv'         => $d['broad_gmv'] ?? $d['broad_order_amount'] ?? $d['gmv'] ?? 0,
            'roas'        => $d['broad_roi'] ?? $d['roas'] ?? null,
        ])->values();

        return response()->json(['days' => $rows]);
    }

    /**
     * Saldo iklan SEMUA toko Shopee aktif (untuk filter "semua toko").
     */
    public function adsBalanceAll(): JsonResponse
    {
        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')->get();

        $rows = [];
        $total = 0;
        foreach ($stores as $store) {
            try {
                $res = $this->gateway->getAdsTotalBalance($store);
                $bal = data_get($res, 'response.total_balance') ?? data_get($res, 'response.balance');
                if ($bal !== null) {
                    $total += (float) $bal;
                    \App\Models\MarketplaceAdsBalanceLog::create(['store_id' => $store->id, 'balance' => $bal]);
                }
                $rows[] = ['store_id' => $store->id, 'store' => $store->name, 'balance' => $bal, 'error' => $res['message'] ?? $res['error'] ?? null];
            } catch (\Throwable $e) {
                $rows[] = ['store_id' => $store->id, 'store' => $store->name, 'balance' => null, 'error' => $e->getMessage()];
            }
        }

        return response()->json(['total' => $total, 'stores' => $rows]);
    }

    /**
     * Sync performa iklan harian ke DB (semua toko Shopee atau satu toko).
     * Simpan snapshot saldo sekalian.
     */
    public function syncAdsDaily(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        set_time_limit(300);
        // Jangan hentikan proses di tengah jalan kalau user menutup modal/tab —
        // run yang terputus akan tertinggal berstatus 'processing' selamanya.
        ignore_user_abort(true);
        \Log::info('SYNC_ADS_DAILY CALLED', $request->all());

        $syncType = $request->input('sync_type', 'today');

        if ($syncType === 'yesterday') {
            $dateFrom = now()->subDay()->toDateString();
            $dateTo   = now()->subDay()->toDateString();
        } elseif ($syncType === 'today') {
            $dateFrom = now()->toDateString();
            $dateTo   = now()->toDateString();
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            // custom
            $dateFrom = $request->input('date_from', now()->subDays(7)->toDateString());
            $dateTo   = $request->input('date_to', now()->toDateString());
        } else {
            $dateFrom = now()->toDateString();
            $dateTo   = now()->toDateString();
        }

        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')
            ->where('is_active', true)
            ->when($request->filled('store_id') && $request->input('store_id') !== 'all', fn ($q) => $q->where('id', $request->integer('store_id')))
            ->get();

        // Rentang hari yang diminta — dipakai untuk memutuskan inline vs backfill queue.
        $rangeDays = \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo));

        return response()->stream(function () use ($stores, $dateFrom, $dateTo, $rangeDays) {
            $sendEvent = function ($type, $message, $progress = null, $extra = []) {
                echo json_encode(array_merge(['type' => $type, 'message' => $message, 'progress' => $progress], $extra)) . "\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            };

            $sendEvent('log', 'Memulai sinkronisasi Iklan...', 5);
            $saved = 0;
            $errors = [];
            $totalStores = $stores->count();

            if ($totalStores === 0) {
                $sendEvent('done', 'Tidak ada toko aktif untuk disinkronisasi.', 100, ['saved' => 0, 'errors' => []]);
                return;
            }

            // Rentang panjang (>65 hari) JANGAN di-sync inline: kuota API Shopee
            // tidak cukup untuk sekali jalan (429 rate limit) dan proses HTTP
            // dibatasi 300 detik. Sampai 2 bulan (±140 call, ±2-3 menit) masih
            // aman inline dengan progress live; lebih dari itu lewat antrean
            // backfill yang otomatis retry + backoff mengikuti Retry-After.
            if ($rangeDays > 65) {
                foreach ($stores as $store) {
                    if (\Illuminate\Support\Facades\Cache::has('shopee-ads-backfill-queued:' . $store->id)) {
                        $sendEvent('log', "[{$store->name}] Backfill sudah ada di antrean — tidak ditambah lagi.", 60);
                        continue;
                    }
                    $sendEvent('log', "[{$store->name}] Rentang {$rangeDays} hari — dialihkan ke backfill background...", 20);
                    \Illuminate\Support\Facades\Artisan::call('marketplace:sync-ads', [
                        '--store'    => $store->id,
                        '--backfill' => true,
                        '--from'     => $dateFrom,
                        '--to'       => $dateTo,
                    ]);
                    $sendEvent('log', "[{$store->name}] Backfill dimasukkan ke antrean.", 60);
                }
                $sendEvent('done', 'Rentang panjang diproses di background dengan auto-retry saat rate limit. Pantau progres di Riwayat Sync — jendela ini boleh ditutup.', 100, [
                    'saved'  => 0,
                    'errors' => [],
                    'status' => 'queued',
                ]);
                return;
            }

            foreach ($stores as $index => $store) {
                $baseProgress = 5 + (($index / $totalStores) * 90);
                $sendEvent('log', "Mempersiapkan koneksi toko {$store->name}...", $baseProgress + 2);

                try {
                                    } catch (\Throwable $e) {
                    $errors[] = "[{$store->name}] " . $e->getMessage();
                    $sendEvent('log', "Gagal menghubungi {$store->name}: " . $e->getMessage(), $baseProgress + 5);
                    continue;
                }

                // Hindari tabrakan dengan sync terjadwal: pakai kunci yang SAMA
                // dengan WithoutOverlapping di ShopeeAdsSyncJob
                // (prefix 'laravel-queue-overlap:' + key 'shopee-ads-store:{id}').
                $lock = \Illuminate\Support\Facades\Cache::lock(
                    'laravel-queue-overlap:shopee-ads-store:' . $store->id,
                    1800
                );

                if (! $lock->get()) {
                    $errors[] = "[{$store->name}] Sync otomatis sedang berjalan untuk toko ini. Coba lagi beberapa menit.";
                    $sendEvent('log', "[{$store->name}] Dilewati: sync otomatis sedang berjalan.", $baseProgress + 5);
                    continue;
                }

                // Shopee masih dalam jendela rate limit? Beri tahu user dengan
                // estimasi tunggu, jangan buang 1 call untuk gagal.
                $cooldownUntil = (int) \Illuminate\Support\Facades\Cache::get('shopee-ads-cooldown:' . $store->id, 0);
                if ($cooldownUntil > time()) {
                    $waitMin = (int) ceil(($cooldownUntil - time()) / 60);
                    $errors[] = "[{$store->name}] Shopee masih membatasi permintaan (rate limit). Coba lagi dalam ±{$waitMin} menit.";
                    $sendEvent('log', "[{$store->name}] Dilewati: menunggu cooldown rate limit (±{$waitMin} menit).", $baseProgress + 5);
                    $lock->release();
                    continue;
                }

                // Auto-heal: run lama yang macet 'processing' (>2 jam) ditandai error
                // supaya riwayat sync di dashboard tidak menampilkan proses hantu.
                \App\Models\MarketplaceAdsSyncRun::where('store_id', $store->id)
                    ->where('status', 'processing')
                    ->where('started_at', '<', now()->subHours(2))
                    ->update([
                        'status'        => 'error',
                        'error_message' => 'Terputus (stale) — ditandai otomatis oleh sync berikutnya',
                        'finished_at'   => now(),
                    ]);

                $run = null;

                try {

                // 1. Snapshot saldo
                $sendEvent('log', "[{$store->name}] Sinkronisasi saldo iklan...", $baseProgress + 5);
                try {
                    $bal = data_get($this->gateway->getAdsTotalBalance($store), 'response.total_balance');
                    if ($bal !== null) {
                        \App\Models\MarketplaceAdsBalanceLog::create(['store_id' => $store->id, 'balance' => $bal]);
                        $sendEvent('log', "[{$store->name}] Saldo berhasil disimpan.", $baseProgress + 10);
                    }
                } catch (\Throwable $e) {
                    $sendEvent('log', "[{$store->name}] Gagal menarik saldo: " . $e->getMessage(), $baseProgress + 10);
                }

                $syncService = app(\App\Services\Marketplace\Ads\ShopeeAdsSyncService::class);
                $run = \App\Models\MarketplaceAdsSyncRun::create([
                    'store_id' => $store->id,
                    'sync_type' => 'manual_dashboard',
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'status' => 'processing',
                    'started_at' => now(),
                ]);

                $isRateLimited = false;
                $rateWaited = false; // tunggu-otomatis rate limit hanya sekali per toko
                $sendEvent('log', "[{$store->name}] Sinkronisasi Daftar Kampanye...", $baseProgress + 12);
                try {
                    $syncService->syncCampaignsAndSettings($store, $run);
                } catch (\App\Exceptions\ShopeeAdsRateLimitException $e) {
                    $isRateLimited = true;
                    $errors[] = "[{$store->name}] Rate limit Shopee dicapai (Tunggu " . ceil($e->retryAfter / 60) . " menit). Sinkronisasi dihentikan sementara.";
                    $sendEvent('log', "[{$store->name}] Batal: Rate limit tercapai.", $baseProgress + 15);
                } catch (\Throwable $e) {
                    $sendEvent('log', "[{$store->name}] Gagal menarik daftar kampanye: " . $e->getMessage(), $baseProgress + 15);
                }

                // 2. Performa harian, kampanye, dan produk
                if (!$isRateLimited) {
                    $sendEvent('log', "[{$store->name}] Memulai sinkronisasi performa...", $baseProgress + 15);

                    $currentStart = \Carbon\Carbon::parse($dateFrom);
                    $finalEnd     = \Carbon\Carbon::parse($dateTo);

                    while ($currentStart->lessThanOrEqualTo($finalEnd)) {
                    $currentEnd = clone $currentStart;
                    $currentEnd->addDays(29);
                    if ($currentEnd->greaterThan($finalEnd)) {
                        $currentEnd = clone $finalEnd;
                    }

                    $sendEvent('log', "[{$store->name}] Menarik periode " . $currentStart->format('d-m-Y') . " s/d " . $currentEnd->format('d-m-Y'), $baseProgress + 20);

                    try {
                        $syncService->syncShopDailyPerformance($store, $currentStart->format('Y-m-d'), $currentEnd->format('Y-m-d'), $run);
                        $sendEvent('log', "[{$store->name}] Performa harian toko tersimpan.", $baseProgress + 20);

                        $sendEvent('log', "[{$store->name}] Mengambil data performa per-jam (heatmap)...", $baseProgress + 21);
                        $hStart = clone $currentStart;
                        while ($hStart->lessThanOrEqualTo($currentEnd)) {
                            // OPTIMIZATION: Only sync hourly data for the last 3 days to save rate limit
                            if ($hStart->diffInDays(now()) <= 3) {
                                $syncService->syncShopHourlyPerformance($store, $hStart->format('Y-m-d'), $run);
                                usleep(250000); // 0.25s delay
                            }
                            $hStart->addDay();
                        }
                        $sendEvent('log', "[{$store->name}] Performa per-jam tersimpan.", $baseProgress + 22);

                        $syncService->syncCampaignDailyPerformance($store, $currentStart->format('Y-m-d'), $currentEnd->format('Y-m-d'), $run);
                        $sendEvent('log', "[{$store->name}] Performa kampanye tersimpan.", $baseProgress + 25);

                        $syncService->syncGmsDailyPerformance($store, $currentStart->format('Y-m-d'), $currentEnd->format('Y-m-d'), $run);
                        $sendEvent('log', "[{$store->name}] Performa produk tersimpan.", $baseProgress + 30);
                    } catch (\App\Exceptions\ShopeeAdsRateLimitException $e) {
                        // Jeda pendek (≤150 dtk): tunggu otomatis di sini SEKALI,
                        // lalu ulangi chunk yang sama — user tidak perlu klik ulang.
                        if (! $rateWaited && $e->retryAfter <= 150) {
                            $rateWaited = true;
                            $waitS = (int) $e->retryAfter + 5;
                            set_time_limit(300 + $waitS);
                            $sendEvent('log', "[{$store->name}] Rate limit Shopee — menunggu {$waitS} detik lalu lanjut otomatis…");
                            $slept = 0;
                            while ($slept < $waitS) {
                                sleep(10);
                                $slept += 10;
                                $sendEvent('log', "[{$store->name}] …menunggu " . max(0, $waitS - $slept) . " detik lagi");
                            }
                            $sendEvent('log', "[{$store->name}] Lanjut — mengulang periode yang terpotong…");
                            continue; // ulangi chunk yang sama (advance tanggal dilewati)
                        }

                        $isRateLimited = true;
                        $errors[] = "[{$store->name}] Rate limit Shopee dicapai (Tunggu " . ceil($e->retryAfter / 60) . " menit). Sinkronisasi dihentikan sementara — klik ulang nanti, proses lanjut dari yang belum tertarik.";
                        $sendEvent('log', "[{$store->name}] Batal: Rate limit tercapai. Silakan coba lagi nanti.");
                        break; // Stop syncing this store for now
                    } catch (\Throwable $e) {
                        $errors[] = "[{$store->name}] " . $e->getMessage();
                        $sendEvent('log', "[{$store->name}] Kesalahan pada periode ini: " . $e->getMessage());
                    }

                    $currentStart = $currentEnd->addDay();
                    usleep(500000); // 0.5 sec delay between chunks
                }
                } // End if (!$isRateLimited)

                // Rate-limit BUKAN sukses — jangan tandai success supaya
                // riwayat sync jujur dan bisa di-retry.
                $run->update([
                    'status'      => $isRateLimited ? 'rate_limited' : 'success',
                    'finished_at' => now(),
                ]);
                $saved += $run->total_updated;
                $sendEvent('log', "[{$store->name}] Berhasil memperbarui {$run->total_updated} baris data.", $baseProgress + (90 / $totalStores));

                } catch (\Throwable $e) {
                    // Jangan biarkan run tertinggal 'processing' kalau ada error tak terduga.
                    if ($run && $run->status === 'processing') {
                        $run->update([
                            'status'        => 'error',
                            'error_message' => substr($e->getMessage(), 0, 1000),
                            'finished_at'   => now(),
                        ]);
                    }
                    $errors[] = "[{$store->name}] " . $e->getMessage();
                    $sendEvent('log', "[{$store->name}] Error: " . $e->getMessage());
                } finally {
                    $lock->release();
                }
            }

            $sendEvent('done', 'Sinkronisasi selesai!', 100, [
                'saved' => $saved,
                'stores' => $totalStores,
                'errors' => $errors,
                'status' => 'success'
            ]);
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Riwayat saldo iklan (dari snapshot log) — saldo terakhir per hari.
     */
    public function adsBalanceHistory(Request $request): JsonResponse
    {
        $days = min(365, max(7, (int) $request->input('days', 60)));

        $logs = \App\Models\MarketplaceAdsBalanceLog::where('created_at', '>=', now()->subDays($days))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->with('store:id,name')
            ->orderBy('created_at')
            ->get();

        // Saldo TERAKHIR per (tanggal, toko) → lalu total per tanggal
        $byDayStore = [];
        foreach ($logs as $log) {
            $byDayStore[$log->created_at->toDateString()][$log->store_id] = (float) $log->balance;
        }

        $rows = collect($byDayStore)->map(fn ($stores, $date) => [
            'date'    => $date,
            'balance' => array_sum($stores),
            'stores'  => count($stores),
        ])->values();

        return response()->json(['days' => $rows]);
    }

    /**
     * Baca performa harian dari DB — mendukung "semua toko" (agregat) & per toko.
     */
    public function adsDaily(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $q = \App\Models\MarketplaceAdsDaily::query()
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($request->filled('store_id'), fn ($qq) => $qq->where('store_id', $request->integer('store_id')));

        // Agregat per tanggal (kalau semua toko → dijumlahkan lintas toko)
        $days = (clone $q)
            ->selectRaw('date,
                SUM(impressions) as impressions, SUM(clicks) as clicks,
                SUM(spend) as spend, SUM(orders) as orders, SUM(gmv) as gmv')
            ->groupBy('date')->orderBy('date')
            ->get()
            ->map(function ($r) {
                $r->ctr  = $r->impressions > 0 ? round($r->clicks / $r->impressions * 100, 2) : null;
                $r->roas = $r->spend > 0 ? round($r->gmv / $r->spend, 2) : null;
                return $r;
            });

        // Ringkasan per toko (untuk perbandingan antar toko)
        $perStore = (clone $q)
            ->selectRaw('store_id, SUM(spend) as spend, SUM(orders) as orders, SUM(gmv) as gmv')
            ->groupBy('store_id')
            ->with('store:id,name')
            ->get()
            ->map(fn ($r) => [
                'store' => $r->store?->name,
                'spend' => (float) $r->spend,
                'orders'=> (int) $r->orders,
                'gmv'   => (float) $r->gmv,
                'roas'  => $r->spend > 0 ? round($r->gmv / $r->spend, 2) : null,
            ]);

        return response()->json(['days' => $days, 'per_store' => $perStore]);
    }

    /** Debug: lihat raw response Shopee Ads API (hapus setelah selesai debug) */
    public function debugAdApi(Request $request, Store $store): JsonResponse
    {
        $sampleIds  = [34741562, 34741571, 65538832]; // 3 campaign pertama
        $today      = now()->format('d-m-Y');
        $monthAgo   = now()->subDays(29)->format('d-m-Y');

        return response()->json([
            'toggle_info'    => $this->gateway->getShopToggleInfo($store),
            'campaign_ids'   => $this->gateway->getCampaignIdList($store, 1, 5),
            'setting_info'   => $this->gateway->getCampaignSettingInfo($store, $sampleIds),
            'daily_perf'     => $this->gateway->getCampaignDailyPerformance($store, $sampleIds, $monthAgo, $today),
        ]);
    }

    public function adsAnalytics(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());
        $storeId  = $request->input('store_id');
        $groupBy  = $request->input('group_by', 'campaign'); // campaign | item | group

        // ── 1. Agregasi metrik harian per campaign dalam rentang tanggal ──────
        //    Grain data ada di marketplace_ad_campaign_dailies → dijumlahkan
        //    supaya rentang tanggal apapun akurat (bukan bergantung range sync).
        $agg = \DB::table('marketplace_ad_campaign_dailies')
            ->select(
                'store_id',
                'channel_campaign_id',
                \DB::raw('SUM(expense) as spend'),
                \DB::raw('SUM(broad_gmv) as gmv'),
                \DB::raw('SUM(direct_gmv) as direct_gmv'),
                \DB::raw('SUM(impressions) as impressions'),
                \DB::raw('SUM(clicks) as clicks'),
                \DB::raw('SUM(broad_order) as orders'),
                \DB::raw('SUM(direct_order) as items_sold'),
            )
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->groupBy('store_id', 'channel_campaign_id')
            ->get()
            ->keyBy(fn ($r) => $r->store_id . '|' . $r->channel_campaign_id);

        if ($agg->isEmpty()) {
            return response()->json([
                'rows'   => [],
                'groups' => $this->adGroupsPayload(),
                'view'   => $groupBy,
                'kpi'    => ['spend' => 0, 'gmv' => 0, 'roas' => null, 'acos' => null,
                             'orders' => 0, 'clicks' => 0, 'profit_after_ads' => null],
            ]);
        }

        // ── 2. Master campaign (identitas + mapping + grup + break-even) ──────
        $masters = MarketplaceAdCampaign::with(['store:id,name', 'internalItem:id,code,name', 'group:id,name,color'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereIn('channel_campaign_id', $agg->pluck('channel_campaign_id')->unique()->all())
            ->get()
            ->keyBy(fn ($c) => $c->store_id . '|' . $c->channel_campaign_id);

        // ── 3. Build baris campaign-level ─────────────────────────────────────
        $campaignRows = collect();
        foreach ($agg as $key => $a) {
            $c = $masters->get($key);
            if (! $c) continue;

            $spend  = (float) $a->spend;
            $gmv    = (float) $a->gmv;
            $orders = (int) $a->orders;
            $clicks = (int) $a->clicks;
            $units  = (int) $a->items_sold;

            $acos = $gmv > 0 && $spend > 0 ? round($spend / $gmv, 4) : null;
            $roas = GmvMaxAnalytics::roas($gmv, $spend);            // broad actual ROAS
            $directRoas = GmvMaxAnalytics::roas((float) $a->direct_gmv, $spend);
            $cpc  = $clicks > 0 ? round($spend / $clicks, 2) : null;

            // ── Setting GMV Max (Fase 2 kolom; sudah ter-load di $c, tanpa N+1) ──
            $targetRoas   = $c->target_roas !== null ? (float) $c->target_roas : null;
            $targetStatus = GmvMaxAnalytics::targetStatus($targetRoas, $roas, $c->bidding_method);

            // Break-even ACOS efektif (override manual atau derivasi HPP item)
            $beAcos = $c->break_even_acos !== null
                ? (float) $c->break_even_acos
                : $this->deriveBreakEvenAcos($c->internalItem, $gmv, $units);
            $bePct  = $beAcos !== null ? round($beAcos * 100, 1) : null;

            $profitAfterAds = ($beAcos !== null) ? round($gmv * $beAcos - $spend, 2) : null;
            $reco = $this->adsRecommendation($spend, $acos, $beAcos, $orders);

            $campaignRows->push([
                'id'              => $c->id,
                'store_id'        => $c->store_id,
                'store_name'      => $c->store?->name,
                'campaign_id'     => $c->channel_campaign_id,
                'campaign_name'   => $c->campaign_name,
                'campaign_type'   => $c->campaign_type,
                'status'          => $c->status,
                'channel_item_id' => $c->channel_item_id,
                'internal_item_id'=> $c->internal_item_id,
                'item_code'       => $c->internalItem?->code,
                'item_name'       => $c->internalItem?->name,
                'mapping_status'  => $c->mapping_status,
                'ad_group_id'     => $c->ad_group_id,
                'group_name'      => $c->group?->name,
                'group_color'     => $c->group?->color,
                // ── Setting GMV Max ──
                'ad_type'            => $c->ad_type,
                'bidding_method'     => $c->bidding_method,
                'target_roas'        => $targetRoas,
                'target_status'      => $targetStatus,
                'campaign_budget'    => $c->campaign_budget !== null ? (float) $c->campaign_budget : null,
                'campaign_status'    => $c->campaign_status,
                'campaign_placement' => $c->campaign_placement,
                'started_at'         => optional($c->started_at)->toDateTimeString(),
                'ended_at'           => optional($c->ended_at)->toDateTimeString(),
                'setting_synced_at'  => optional($c->setting_synced_at)->toDateTimeString(),

                'spend'           => $spend,
                'gmv'             => $gmv,
                'direct_gmv'      => (float) $a->direct_gmv,
                'direct_roas'     => $directRoas,
                'impressions'     => (int) $a->impressions,
                'clicks'          => $clicks,
                'orders'          => $orders,
                'items_sold'      => $units,
                'roas'            => $roas,
                'cpc'             => $cpc,
                'acos'            => $acos,
                'acos_pct'        => $acos !== null ? round($acos * 100, 1) : null,
                'break_even_acos'     => $beAcos,
                'break_even_acos_pct' => $bePct,
                'profit_after_ads'    => $profitAfterAds,
                'reco'            => $reco,
            ]);
        }

        // ── 3b. Filter server-side khusus GMV Max (opsional) ──────────────────
        $fBidding      = $request->input('bidding_method');
        $fCampStatus   = $request->input('campaign_status');
        $fTargetStatus = $request->input('target_status');
        if ($fBidding || $fCampStatus || $fTargetStatus) {
            $campaignRows = $campaignRows->filter(function ($r) use ($fBidding, $fCampStatus, $fTargetStatus) {
                if ($fBidding && ($r['bidding_method'] ?? null) !== $fBidding) return false;
                if ($fCampStatus && ($r['campaign_status'] ?? null) !== $fCampStatus) return false;
                if ($fTargetStatus && ($r['target_status'] ?? null) !== $fTargetStatus) return false;
                return true;
            })->values();
        }

        // ── 4. Regroup jika perlu (per item internal / per grup) ──────────────
        $rows = match ($groupBy) {
            'item'  => $this->aggregateAdRows($campaignRows, 'internal_item_id',
                fn ($r) => $r['item_name'] ?? ($r['internal_item_id'] ? 'Item #' . $r['internal_item_id'] : '⚠ Belum di-mapping'),
                fn ($r) => ['item_code' => $r['item_code'], 'internal_item_id' => $r['internal_item_id']]),
            'group' => $this->aggregateAdRows($campaignRows, 'ad_group_id',
                fn ($r) => $r['group_name'] ?? '— Tanpa Grup —',
                fn ($r) => ['ad_group_id' => $r['ad_group_id'], 'group_color' => $r['group_color']]),
            default => $campaignRows->sortByDesc('spend')->values(),
        };

        // ── 5. Overall KPI (selalu level campaign; broad = attributed utama) ──
        $totSpend    = $campaignRows->sum('spend');
        $totGmv      = $campaignRows->sum('gmv');       // broad — JANGAN jumlahkan dgn direct
        $totDirect   = $campaignRows->sum('direct_gmv');
        // Last sync = setting/perf tersinkron terbaru dari campaign yang tampil
        $lastSync = $masters->flatMap(fn ($c) => [$c->setting_synced_at, $c->synced_at])
            ->filter()->max();
        $kpi = [
            'spend'            => $totSpend,
            'gmv'              => $totGmv,
            'direct_gmv'       => $totDirect,
            'roas'             => GmvMaxAnalytics::roas($totGmv, $totSpend),
            'direct_roas'      => GmvMaxAnalytics::roas($totDirect, $totSpend),
            'weighted_target_roas' => GmvMaxAnalytics::weightedTargetRoas($campaignRows),
            'acos'             => $totGmv  > 0 ? round($totSpend / $totGmv * 100, 1) : null,
            'orders'           => $campaignRows->sum('orders'),
            'clicks'           => $campaignRows->sum('clicks'),
            'active_campaigns' => $campaignRows->where('campaign_status', 'ongoing')->count(),
            'below_target'     => $campaignRows->where('target_status', 'below')->count(),
            'profit_after_ads' => $campaignRows->filter(fn ($r) => $r['profit_after_ads'] !== null)->sum('profit_after_ads') ?: null,
            'unmapped'         => $campaignRows->where('internal_item_id', null)->count(),
            'last_sync'        => $lastSync ? $lastSync->toDateTimeString() : null,
        ];

        return response()->json([
            'rows'   => $rows->values(),
            'groups' => $this->adGroupsPayload(),
            'view'   => $groupBy,
            'kpi'    => $kpi,
        ]);
    }

    /**
     * Break-even ACOS (0..1) diturunkan dari harga jual teramati (gmv/unit)
     * vs HPP item internal. Null jika data tak cukup.
     */
    private function deriveBreakEvenAcos(?Item $item, float $gmv, int $units): ?float
    {
        if (! $item || $units <= 0 || $gmv <= 0) return null;
        $hpp = (float) ($item->hpp ?? $item->base_unit_cost ?? 0);
        if ($hpp <= 0) return null;
        $avgPrice = $gmv / $units;
        if ($hpp >= $avgPrice) return null;
        return round(($avgPrice - $hpp) / $avgPrice, 6);
    }

    /**
     * Agregasi baris campaign menjadi baris grup (per item / per grup).
     * Rasio dihitung ulang; profit dijumlahkan; break-even = rata-rata tertimbang gmv.
     */
    private function aggregateAdRows($campaignRows, string $keyField, callable $labelFn, callable $metaFn)
    {
        return $campaignRows
            ->groupBy(fn ($r) => $r[$keyField] ?? '∅')
            ->map(function ($group) use ($keyField, $labelFn, $metaFn) {
                $first  = $group->first();
                $spend  = $group->sum('spend');
                $gmv    = $group->sum('gmv');
                $orders = $group->sum('orders');
                $clicks = $group->sum('clicks');

                $acos = $gmv > 0 && $spend > 0 ? round($spend / $gmv, 4) : null;
                $roas = $spend > 0 ? round($gmv / $spend, 2) : null;

                $beWeighted = $group->filter(fn ($r) => $r['break_even_acos'] !== null && $r['gmv'] > 0);
                $beAcos = $beWeighted->isNotEmpty() && $beWeighted->sum('gmv') > 0
                    ? round($beWeighted->sum(fn ($r) => $r['break_even_acos'] * $r['gmv']) / $beWeighted->sum('gmv'), 4)
                    : null;
                $bePct  = $beAcos !== null ? round($beAcos * 100, 1) : null;

                $profit = $group->filter(fn ($r) => $r['profit_after_ads'] !== null)->sum('profit_after_ads') ?: null;
                $reco   = $this->adsRecommendation($spend, $acos, $beAcos, $orders);

                return array_merge([
                    'id'              => $keyField . ':' . ($first[$keyField] ?? 'none'),
                    'is_group'        => true,
                    'campaign_id'     => null,
                    'campaign_name'   => $labelFn($first),
                    'campaign_type'   => $group->count() . ' campaign',
                    'status'          => null,
                    'members'         => $group->count(),
                    'spend'           => $spend,
                    'gmv'             => $gmv,
                    'direct_gmv'      => $group->sum('direct_gmv'),
                    'impressions'     => $group->sum('impressions'),
                    'clicks'          => $clicks,
                    'orders'          => $orders,
                    'items_sold'      => $group->sum('items_sold'),
                    'roas'            => $roas,
                    'cpc'             => $clicks > 0 ? round($spend / $clicks, 2) : null,
                    'acos'            => $acos,
                    'acos_pct'        => $acos !== null ? round($acos * 100, 1) : null,
                    'break_even_acos'     => $beAcos,
                    'break_even_acos_pct' => $bePct,
                    'profit_after_ads'    => $profit,
                    'reco'            => $reco,
                ], $metaFn($first));
            })
            ->sortByDesc('spend')
            ->values();
    }

    /** Daftar semua grup iklan untuk UI (dengan jumlah campaign). */
    private function adGroupsPayload()
    {
        return MarketplaceAdGroup::withCount('campaigns')
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'notes'])
            ->map(fn ($g) => [
                'id' => $g->id, 'name' => $g->name, 'color' => $g->color,
                'notes' => $g->notes, 'campaigns_count' => $g->campaigns_count,
            ]);
    }

    public function updateCampaignBreakEven(Request $request, MarketplaceAdCampaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'break_even_acos' => ['required', 'numeric', 'min:0', 'max:1'],
        ]);

        $campaign->update(['break_even_acos' => $data['break_even_acos']]);

        return response()->json([
            'message'         => 'Break-even ACOS diperbarui.',
            'break_even_acos' => (float) $campaign->break_even_acos,
        ]);
    }

    /**
     * Mapping manual campaign iklan → item internal.
     * Menyimpan override di marketplace_ad_item_maps (agar bertahan saat re-sync)
     * dan langsung meng-update campaign.
     */
    public function mapCampaignItem(Request $request, MarketplaceAdCampaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'internal_item_id' => ['nullable', 'integer', 'exists:items,id'],
        ]);

        if (empty($data['internal_item_id'])) {
            // Batalkan override → kembalikan ke resolusi otomatis
            MarketplaceAdItemMap::query()
                ->where('channel_code', 'shopee')
                ->where(function ($q) use ($campaign) {
                    if ($campaign->channel_item_id) $q->orWhere('channel_item_id', $campaign->channel_item_id);
                    $q->orWhere('channel_campaign_id', $campaign->channel_campaign_id);
                })->delete();

            app(\App\Services\AdItemMapper::class)->applyTo($campaign);
            $campaign->save();

            return response()->json([
                'message'          => 'Override mapping dihapus, kembali ke otomatis.',
                'internal_item_id' => $campaign->internal_item_id,
                'mapping_status'   => $campaign->mapping_status,
            ]);
        }

        MarketplaceAdItemMap::updateOrCreate(
            $campaign->channel_item_id
                ? ['channel_code' => 'shopee', 'channel_item_id' => $campaign->channel_item_id]
                : ['channel_code' => 'shopee', 'channel_campaign_id' => $campaign->channel_campaign_id],
            [
                'store_id'            => $campaign->store_id,
                'channel_campaign_id' => $campaign->channel_campaign_id,
                'internal_item_id'    => $data['internal_item_id'],
                'created_by'          => $request->user()?->id,
            ]
        );

        $campaign->update([
            'internal_item_id' => $data['internal_item_id'],
            'mapping_status'   => 'manual',
            'mapping_source'   => 'manual',
        ]);

        return response()->json([
            'message'          => 'Item internal berhasil di-mapping.',
            'internal_item_id' => $campaign->internal_item_id,
            'item'             => optional(Item::find($data['internal_item_id']))->only(['id', 'code', 'name']),
            'mapping_status'   => 'manual',
        ]);
    }

    /** Buat grup iklan. */
    public function storeAdGroup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['created_by'] = $request->user()?->id;

        $group = MarketplaceAdGroup::create($data);

        return response()->json(['message' => 'Grup dibuat.', 'group' => $group], 201);
    }

    /** Update grup iklan. */
    public function updateAdGroup(Request $request, MarketplaceAdGroup $group): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $group->update($data);

        return response()->json(['message' => 'Grup diperbarui.', 'group' => $group]);
    }

    /** Assign / lepas campaign dari grup. */
    public function assignCampaignGroup(Request $request, MarketplaceAdCampaign $campaign): JsonResponse
    {
        $data = $request->validate([
            'ad_group_id' => ['nullable', 'integer', 'exists:marketplace_ad_groups,id'],
        ]);

        $campaign->update(['ad_group_id' => $data['ad_group_id'] ?? null]);

        return response()->json([
            'message'     => $data['ad_group_id'] ? 'Campaign dimasukkan ke grup.' : 'Campaign dikeluarkan dari grup.',
            'ad_group_id' => $campaign->ad_group_id,
        ]);
    }

    /**
     * Detail campaign GMV Max (READ-ONLY): setting + performa agregat periode +
     * daily trend. Raw setting payload hanya untuk owner (sudah tersanitasi).
     * Broad = attributed utama; direct = pembanding (tidak dijumlahkan).
     */
    public function campaignDetail(Request $request, MarketplaceAdCampaign $campaign): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $campaign->loadMissing('internalItem:id,code,name', 'store:id,name');

        $daily = \DB::table('marketplace_ad_campaign_dailies')
            ->where('store_id', $campaign->store_id)
            ->where('channel_campaign_id', $campaign->channel_campaign_id)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get(['date', 'expense', 'impressions', 'clicks', 'broad_order', 'direct_order', 'broad_gmv', 'direct_gmv']);

        $spend  = (float) $daily->sum('expense');
        $gmv    = (float) $daily->sum('broad_gmv');
        $dGmv   = (float) $daily->sum('direct_gmv');
        $impr   = (int) $daily->sum('impressions');
        $clicks = (int) $daily->sum('clicks');
        $bOrd   = (int) $daily->sum('broad_order');
        $dOrd   = (int) $daily->sum('direct_order');

        $performance = [
            'spend'       => $spend,
            'broad_gmv'   => $gmv,
            'direct_gmv'  => $dGmv,
            'impressions' => $impr,
            'clicks'      => $clicks,
            'broad_order' => $bOrd,
            'direct_order'=> $dOrd,
            'ctr'         => GmvMaxAnalytics::safeDiv($clicks * 100, $impr, 2),
            'cpc'         => GmvMaxAnalytics::safeDiv($spend, $clicks, 2),
            'broad_cvr'   => GmvMaxAnalytics::safeDiv($bOrd * 100, $clicks, 2),
            'direct_cvr'  => GmvMaxAnalytics::safeDiv($dOrd * 100, $clicks, 2),
            'broad_roas'  => GmvMaxAnalytics::roas($gmv, $spend),
            'direct_roas' => GmvMaxAnalytics::roas($dGmv, $spend),
            'broad_cpa'   => GmvMaxAnalytics::safeDiv($spend, $bOrd, 2),
            'direct_cpa'  => GmvMaxAnalytics::safeDiv($spend, $dOrd, 2),
        ];

        $setting = [
            'campaign_id'        => $campaign->channel_campaign_id,
            'item_id'            => $campaign->channel_item_id,
            'item_code'          => $campaign->internalItem?->code,
            'item_name'          => $campaign->internalItem?->name,
            'ad_type'            => $campaign->ad_type,
            'bidding_method'     => $campaign->bidding_method,
            'target_roas'        => $campaign->target_roas !== null ? (float) $campaign->target_roas : null,
            'campaign_budget'    => $campaign->campaign_budget !== null ? (float) $campaign->campaign_budget : null,
            'campaign_placement' => $campaign->campaign_placement,
            'campaign_status'    => $campaign->campaign_status,
            'started_at'         => optional($campaign->started_at)->toDateTimeString(),
            'ended_at'           => optional($campaign->ended_at)->toDateTimeString(),
            'setting_synced_at'  => optional($campaign->setting_synced_at)->toDateTimeString(),
        ];

        // Raw payload: owner-only, sanitasi ganda (defensif) walau sudah bersih saat simpan.
        $raw = null;
        if ($request->user()?->isOwner()) {
            $raw = \App\Services\MarketplaceSyncService::stripSensitive($campaign->raw_setting_payload ?? []);
        }

        return response()->json([
            'setting'             => $setting,
            'performance'         => $performance,
            'daily'               => $daily,
            'raw_setting_payload' => $raw,
        ]);
    }

    private function adsRecommendation(float $spend, ?float $acos, ?float $breakEvenAcos, int $orders): array
    {
        if ($spend === 0.0) {
            return ['label' => 'Tidak Aktif', 'color' => '#94a3b8', 'icon' => '⚪'];
        }
        if ($orders === 0) {
            return ['label' => 'Stop — 0 Konversi', 'color' => '#b91c1c', 'icon' => '🔴'];
        }
        if ($breakEvenAcos === null) {
            return ['label' => 'Set Break-Even', 'color' => '#b45309', 'icon' => '⚠️'];
        }
        if ($acos === null) {
            return ['label' => 'Data Tidak Lengkap', 'color' => '#94a3b8', 'icon' => '⚪'];
        }

        $ratio = $acos / $breakEvenAcos;

        if ($ratio <= 0.60) {
            return ['label' => 'Scale — Naikkan Budget', 'color' => '#16a34a', 'icon' => '🚀'];
        }
        if ($ratio <= 0.85) {
            return ['label' => 'Pertahankan', 'color' => '#2563eb', 'icon' => '✅'];
        }
        if ($ratio <= 1.00) {
            return ['label' => 'Perhatikan — Margin Tipis', 'color' => '#d97706', 'icon' => '⚡'];
        }

        return ['label' => 'Stop / Kurangi Bid', 'color' => '#b91c1c', 'icon' => '🔴'];
    }

    public function syncLogs(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'action' => ['nullable', 'string', 'max:80'],
        ]);

        $query = MarketplaceSyncLog::with('store:id,name');

        if (! empty($data['store_id'])) {
            $query->where('store_id', $data['store_id']);
        }
        if (! empty($data['action'])) {
            $query->where('action', $data['action']);
        }

        // Settlement history is store-specific. Do not fall back to exposing
        // every store's settlement log when the page has no selected store.
        if (($data['action'] ?? null) === 'sync_settlements' && empty($data['store_id'])) {
            return response()->json([]);
        }

        $logs = $query
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($log) => [
                'id'         => $log->id,
                'store_name' => $log->store?->name,
                'action'     => $log->action,
                'status'     => $log->status,
                'message'    => $log->message,
                'payload'    => $log->payload ?? [],
                'created_at' => $log->created_at?->toISOString(),
            ]);

        return response()->json($logs);
    }

    public function warehouses(): JsonResponse
    {
        return response()->json(
            Warehouse::orderBy('name')->get(['id', 'code', 'name'])
        );
    }

    public function updateStore(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'name'                        => ['sometimes', 'string', 'max:120'],
            'region'                      => ['sometimes', 'nullable', 'string', 'max:32'],
            'default_warehouse_id'        => ['nullable', 'integer', 'exists:warehouses,id'],
            'meta_shipping_document_type' => ['sometimes', 'string', 'in:THERMAL_AIR_WAYBILL,NORMAL_AIR_WAYBILL'],
        ]);

        $updateData = [];
        if (array_key_exists('name', $data)) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('region', $data)) {
            $updateData['region'] = $data['region'];
        }
        if (array_key_exists('default_warehouse_id', $data)) {
            $updateData['default_warehouse_id'] = $data['default_warehouse_id'];
        }

        if (array_key_exists('meta_shipping_document_type', $data)) {
            $meta = $store->meta ?? [];
            $meta['shipping_document_type'] = $data['meta_shipping_document_type'];
            $updateData['meta'] = $meta;
        }

        if (!empty($updateData)) {
            $store->update($updateData);
        }

        if (!empty($data['default_warehouse_id'])) {
            $warehouseId  = $data['default_warehouse_id'];
            $fulfillments = OrderFulfillment::whereHas('order', fn ($q) => $q->where('store_id', $store->id))
                ->whereIn('status', [OrderFulfillment::STATUS_DRAFT, OrderFulfillment::STATUS_PENDING_REVIEW])
                ->whereNull('warehouse_id')
                ->get();

            foreach ($fulfillments as $f) {
                $f->update(['warehouse_id' => $warehouseId]);
                $this->fulfillment->refreshStock($f->load('lines'));
            }
        }

        $store->load('channel');
        return response()->json([
            'message'              => 'Toko diperbarui.',
            'default_warehouse_id' => $store->default_warehouse_id,
            'fulfillments_updated' => isset($fulfillments) ? $fulfillments->count() : 0,
        ]);
    }

    public function disconnectStore(Store $store): JsonResponse
    {
        try {
            $store->update([
                'credentials' => null,
                'token_expires_at' => null,
            ]);

            return response()->json([
                'message' => 'Toko berhasil diputuskan koneksinya.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat memutuskan koneksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aktif/Nonaktifkan toko. Toko nonaktif tidak dimunculkan di peringatan
     * koneksi (dianggap sengaja tidak dipakai) dan dilewati sync chat.
     */
    public function toggleActive(Store $store): JsonResponse
    {
        $store->update(['is_active' => ! $store->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => (bool) $store->is_active,
            'message'   => $store->is_active ? 'Toko diaktifkan.' : 'Toko dinonaktifkan (disembunyikan dari peringatan).',
        ]);
    }

    public function deleteStore(Store $store): JsonResponse
    {
        // Jangan menghapus toko yang sudah memiliki data historis. Gunakan
        // nonaktifkan toko bila hanya ingin menghentikan pemakaiannya.
        $protectedTables = [
            'marketplace_orders' => 'pesanan marketplace',
            'shipments' => 'shipment operasional',
            'mp_shipments' => 'shipment import/API',
            'mp_incomes' => 'data income',
            'marketplace_order_settlements' => 'settlement',
            'marketplace_import_batches' => 'riwayat import',
            'marketplace_ad_campaigns' => 'campaign iklan',
            'marketplace_products' => 'produk marketplace',
            'marketplace_promotions' => 'promosi',
            'marketplace_returns' => 'retur marketplace',
            'marketplace_bookings' => 'booking marketplace',
        ];

        $historyLabel = null;
        foreach ($protectedTables as $table => $label) {
            if (Schema::hasTable($table)
                && Schema::hasColumn($table, 'store_id')
                && DB::table($table)->where('store_id', $store->id)->exists()) {
                $historyLabel = $label;
                break;
            }
        }

        if ($historyLabel) {
            return response()->json([
                'message' => "Toko ini tidak dapat dihapus karena sudah memiliki {$historyLabel}. Nonaktifkan toko jika tidak ingin dipakai lagi."
            ], 422);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($store) {
                // Hanya membersihkan data log yang tidak berdampak pada transaksi utama
                DB::table('marketplace_sync_logs')->where('store_id', $store->id)->delete();
                $store->delete();
            });

            return response()->json([
                'message' => 'Toko berhasil dihapus karena belum memiliki riwayat pesanan.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus toko: ' . $e->getMessage()
            ], 500);
        }
    }

    // ─── Issue Center API ─────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────────────
    // Store stats summary — untuk toko page cards
    // ─────────────────────────────────────────────────────────────────────────

    public function storesSummary(): JsonResponse
    {
        $today = now()->startOfDay();

        $storeIds = \App\Models\Store::pluck('id')->toArray();

        // Orders hari ini per toko
        $ordersToday = MarketplaceOrder::where('ordered_at', '>=', $today)
            ->whereIn('store_id', $storeIds)
            ->selectRaw('store_id, count(*) as cnt')
            ->groupBy('store_id')
            ->pluck('cnt', 'store_id');

        // Order READY_TO_SHIP belum punya fulfillment draft
        $unfulfilled = MarketplaceOrder::whereIn('order_status', ['READY_TO_SHIP', 'PROCESSED'])
            ->whereIn('store_id', $storeIds)
            ->whereDoesntHave('fulfillment')
            ->selectRaw('store_id, count(*) as cnt')
            ->groupBy('store_id')
            ->pluck('cnt', 'store_id');

        // Item bermasalah (data_status = incomplete) per toko
        $issueItems = MarketplaceOrderItem::whereHas(
                'order', fn ($q) => $q->whereIn('store_id', $storeIds)
            )
            ->where('data_status', 'incomplete')
            ->join('marketplace_orders', 'marketplace_order_items.marketplace_order_id', '=', 'marketplace_orders.id')
            ->selectRaw('marketplace_orders.store_id, count(marketplace_order_items.id) as cnt')
            ->groupBy('marketplace_orders.store_id')
            ->pluck('cnt', 'marketplace_orders.store_id');

        $result = [];
        foreach ($storeIds as $id) {
            $result[(string)$id] = [
                'orders_today' => (int) ($ordersToday[$id] ?? 0),
                'unfulfilled'  => (int) ($unfulfilled[$id] ?? 0),
                'issues'       => (int) ($issueItems[$id] ?? 0),
            ];
        }

        return response()->json($result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Data Perlu Diperbaiki — list items dengan search + filter + tabs
    // ─────────────────────────────────────────────────────────────────────────

    public function issueItems(Request $request): JsonResponse
    {
        $tab      = $request->input('tab', 'all');   // all|sku_empty|mapping_not_found|missing_hpp|profit_incomplete|selesai
        $storeId  = $request->input('store_id');
        $q        = $request->input('q');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $page     = max(1, (int) $request->input('page', 1));
        $perPage  = 20;

        $query = MarketplaceOrderItem::with([
            'order:id,store_id,channel_order_id,external_order_id,ordered_at,order_date',
            'order.store:id,name',
            'order.store.channel:id,code,name',
            'internalItem:id,name,code,base_unit_cost,hpp',
        ]);

        // Filter toko & tanggal via whereHas
        if ($storeId || $dateFrom || $dateTo) {
            $query->whereHas('order', function ($oq) use ($storeId, $dateFrom, $dateTo) {
                if ($storeId)  $oq->where('store_id', $storeId);
                if ($dateFrom) $oq->where('ordered_at', '>=', $dateFrom . ' 00:00:00');
                if ($dateTo)   $oq->where('ordered_at', '<=', $dateTo   . ' 23:59:59');
            });
        }

        // Search
        if ($q) {
            $query->where(function ($qr) use ($q) {
                $qr->where('item_name',      'like', "%{$q}%")
                   ->orWhere('variant_name',  'like', "%{$q}%")
                   ->orWhere('model_sku',     'like', "%{$q}%")
                   ->orWhere('item_sku',      'like', "%{$q}%")
                   ->orWhere('marketplace_sku','like', "%{$q}%")
                   ->orWhereHas('order', fn ($oq) => $oq
                       ->where('channel_order_id', 'like', "%{$q}%")
                       ->orWhere('external_order_id', 'like', "%{$q}%"));
            });
        }

        // Tab filter
        match ($tab) {
            'sku_empty'         => $query->skuEmpty(),
            'mapping_not_found' => $query->mappingNotFound(),
            'missing_hpp'       => $query->missingHpp(),
            'profit_incomplete' => $query->profitIncomplete(),
            'selesai'           => $query->where('data_status', 'valid'),
            default             => $query->hasIssues(),
        };

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->through(function ($i) {
            // Recommendation: fuzzy-match by item_name for mapping_not_found items
            $rec = null;
            if ($i->issue_reason === 'mapping_not_found' && $i->item_name) {
                $found = Item::where('name', 'like', '%' . addcslashes($i->item_name, '%_\\') . '%')
                             ->select('id', 'name', 'code', 'base_unit_cost', 'hpp')
                             ->first();
                if ($found) {
                    $rec = [
                        'id'   => $found->id,
                        'name' => $found->name,
                        'code' => $found->code ?? '',
                        'hpp'  => (float) ($found->base_unit_cost ?: $found->hpp ?: 0),
                    ];
                }
            }

            $sku = $i->marketplace_sku ?? $i->model_sku ?? $i->item_sku ?? $i->external_sku;
            $mappingStatus = $i->mapping_status;
            $issueReason = $i->issue_reason;

            if ($mappingStatus === 'marketplace_sku_empty' && !empty($sku)) {
                $mappingStatus = 'mapping_not_found';
                $issueReason = 'mapping_not_found';
            }

            return [
                'id'                  => $i->id,
                'order_id'            => $i->order?->id,
                'order_number'        => $i->order?->channel_order_id ?? $i->order?->external_order_id,
                'ordered_at'          => $i->order?->ordered_at?->toISOString() ?? $i->order?->order_date,
                'store_name'          => $i->order?->store?->name,
                'channel_code'        => $i->order?->store?->channel?->code,
                'item_name'           => $i->item_name ?? $i->item_name_snapshot,
                'variant_name'        => $i->variant_name ?? $i->variant_snapshot,
                'marketplace_sku'     => $sku,
                'qty'                 => $i->qty,
                'mapping_status'      => $mappingStatus,
                'cost_status'         => $i->cost_status,
                'profit_status'       => $i->profit_status,
                'data_status'         => $i->data_status,
                'issue_reason'        => $issueReason,
                'internal_item_id'    => $i->internal_item_id,
                'internal_item_name'  => $i->internalItem?->name,
                'internal_item_code'  => $i->internalItem?->code,
                'hpp_current'         => $i->internalItem ? (float) ($i->internalItem->base_unit_cost ?: $i->internalItem->hpp ?: 0) : 0,
                'hpp_snapshot'        => $i->hpp_snapshot,
                'recommended_item'    => $rec,
            ];
        });

        // ── Pesanan Kilat (booking tanpa order lokal) — sisipkan di halaman 1 ──
        $bookingRows = [];
        if ($page === 1 && in_array($tab, ['all', 'sku_empty', 'mapping_not_found', 'missing_hpp'], true)) {
            $bookingRows = $this->issueService->bookingIssueRows(
                $storeId ? (int) $storeId : null, $q ?: null, $tab
            );

            // Filter tanggal (booking pakai create_time → ordered_at ISO string)
            if ($dateFrom || $dateTo) {
                $bookingRows = array_values(array_filter($bookingRows, function ($r) use ($dateFrom, $dateTo) {
                    if (! $r['ordered_at']) return false;
                    $t = \Carbon\Carbon::parse($r['ordered_at']);
                    if ($dateFrom && $t->lt(\Carbon\Carbon::parse($dateFrom)->startOfDay())) return false;
                    if ($dateTo   && $t->gt(\Carbon\Carbon::parse($dateTo)->endOfDay()))   return false;
                    return true;
                }));
            }
        }

        return response()->json([
            'data'         => array_merge($bookingRows, $items->items()),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total() + count($bookingRows),
            'per_page'     => $perPage,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Quick-action endpoints
    // ─────────────────────────────────────────────────────────────────────────

    public function fillItemSku(Request $request, MarketplaceOrderItem $item): JsonResponse
    {
        $data = $request->validate([
            'sku'              => 'required|string|max:100',
            'apply_to_similar' => 'boolean',
        ]);

        try {
            $result = $this->issueService->fillSku(
                $item, $data['sku'], (bool) ($data['apply_to_similar'] ?? false)
            );
            return response()->json([
                'message'  => "SKU berhasil diisi. {$result['affected']} item diperbarui.",
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function mapItemSku(Request $request, MarketplaceOrderItem $item): JsonResponse
    {
        $data = $request->validate([
            'internal_item_id' => 'required|integer|exists:items,id',
            'apply_to_all'     => 'boolean',
        ]);

        try {
            $result = $this->issueService->mapSku(
                $item, (int) $data['internal_item_id'], (bool) ($data['apply_to_all'] ?? false)
            );
            return response()->json([
                'message'  => "SKU berhasil dihubungkan. {$result['affected']} item diperbarui.",
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function fillItemHpp(Request $request, MarketplaceOrderItem $item): JsonResponse
    {
        $data = $request->validate([
            'hpp'             => 'required|numeric|min:1',
            'update_affected' => 'boolean',
        ]);

        try {
            $result = $this->issueService->fillHpp(
                $item, (float) $data['hpp'], (bool) ($data['update_affected'] ?? true)
            );
            return response()->json([
                'message'  => "HPP berhasil disimpan. {$result['affected']} item diperbarui.",
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function recalcItemProfit(Request $request, MarketplaceOrderItem $item): JsonResponse
    {
        try {
            $result = $this->issueService->recalcProfit($item);
            return response()->json([
                'message'  => "Profit berhasil dihitung ulang.",
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function bulkMapSku(Request $request): JsonResponse
    {
        $request->validate([
            'item_ids'         => 'required|array|min:1',
            'item_ids.*'       => 'integer',
            'internal_item_id' => 'required|integer|exists:items,id',
            'apply_to_all'     => 'boolean',
        ]);

        $items    = MarketplaceOrderItem::whereIn('id', $request->item_ids)->get();
        $updated  = 0;
        $errors   = [];

        foreach ($items as $item) {
            try {
                $this->issueService->mapSku($item, (int) $request->internal_item_id, $request->boolean('apply_to_all', false));
                $updated++;
            } catch (\Throwable $e) {
                $errors[] = 'Item #' . $item->id . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'message' => "{$updated} item berhasil di-mapping." . (count($errors) ? ' ' . count($errors) . ' gagal.' : ''),
            'updated' => $updated,
            'errors'  => $errors,
        ]);
    }

    public function searchInternalItems(Request $request): JsonResponse
    {
        $q     = trim($request->input('q', ''));
        $limit = min(20, (int) $request->input('limit', 15));

        // Ads mapping memilih level produk utama. HPP yang dikirim ke UI
        // sudah dirata-ratakan dari seluruh item variant yang terkait.
        if ($request->boolean('group_products')) {
            $resolver = app(ItemHppResolver::class);
            $suggestions = $resolver->suggestionsForMarketplace(
                $request->integer('store_id') ?: null,
                $request->input('channel_item_id'),
                $q,
                $limit
            );
            $products = $resolver->searchProducts($q, $limit);

            return response()->json(
                $suggestions->merge($products)->unique(fn (array $row) => (string) $row['id'])->take($limit)->values()
            );
        }

        $items = Item::query()
            ->when($q, fn ($query) => $query->where(function ($qr) use ($q) {
                $qr->where('name', 'like', "%{$q}%")
                   ->orWhere('code', 'like', "%{$q}%");
            }))
            ->select('id', 'name', 'code', 'base_unit_cost', 'hpp')
            ->limit($limit)
            ->get()
            ->map(fn ($i) => [
                'id'   => $i->id,
                'name' => $i->name,
                'code' => $i->code,
                'hpp'  => (float) ($i->base_unit_cost ?: $i->hpp ?: 0),
            ]);

        return response()->json($items);
    }

    /** Lookup item by exact code (untuk batch scan item di fulfillment). */
    public function itemByCode(Request $request): JsonResponse
    {
        $code = trim($request->input('code', ''));
        if (! $code) {
            return response()->json(['message' => 'Parameter code diperlukan.'], 422);
        }

        $item = Item::where('code', $code)->select('id', 'name', 'code', 'base_unit_cost', 'hpp')->first();
        if (! $item) {
            return response()->json(['message' => "Item dengan kode \"{$code}\" tidak ditemukan."], 404);
        }

        return response()->json([
            'id'   => $item->id,
            'name' => $item->name,
            'code' => $item->code,
            'hpp'  => (float) ($item->base_unit_cost ?: $item->hpp ?: 0),
        ]);
    }

        public function issueSummary(Request $request): JsonResponse
    {
        $storeId = $request->input('store_id');
        $summary = $this->issueService->summary($storeId ? (int) $storeId : null);

        // Gabungkan issue dari Pesanan Kilat (booking tanpa order lokal)
        $booking = $this->issueService->bookingIssueSummary($storeId ? (int) $storeId : null);
        $summary['sku_empty']         += $booking['sku_empty'];
        $summary['mapping_not_found'] += $booking['mapping_not_found'];
        $summary['missing_hpp']       += $booking['missing_hpp'];
        $summary['total_issues']      += $booking['total_issues'];
        $summary['data_incomplete']   += $booking['total_issues'];
        $summary['booking_issues']     = $booking['total_issues'];

        return response()->json($summary);
    }

    // ── Quick actions untuk item Pesanan Kilat (booking) ─────────────────────

    public function fillBookingItemSku(Request $request, \App\Models\MarketplaceBooking $booking): JsonResponse
    {
        $data = $request->validate([
            'index' => 'required|integer|min:0',
            'sku'   => 'required|string|max:100',
        ]);

        try {
            $result = $this->issueService->fillBookingItemSku($booking, (int) $data['index'], $data['sku']);
            return response()->json([
                'message'  => 'SKU berhasil diisi. Mapping nama produk tersimpan — order berikutnya otomatis terisi.',
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function mapBookingItemSku(Request $request, \App\Models\MarketplaceBooking $booking): JsonResponse
    {
        $data = $request->validate([
            'index'            => 'required|integer|min:0',
            'internal_item_id' => 'required|integer|exists:items,id',
        ]);

        try {
            $result = $this->issueService->mapBookingItemSku($booking, (int) $data['index'], (int) $data['internal_item_id']);
            return response()->json([
                'message'  => 'SKU berhasil dihubungkan ke item internal.',
                'affected' => $result['affected'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function remapOrderItems(Request $request): JsonResponse
    {
        set_time_limit(300);
        $storeId = $request->input('store_id');
        $result  = $this->issueService->remapItems($storeId ? (int) $storeId : null);
        return response()->json([
            'message' => 'Remap selesai.',
            'updated' => $result['updated'],
            'errors'  => $result['errors'],
        ]);
    }

    public function syncHpp(Request $request): JsonResponse
    {
        set_time_limit(300);
        $storeId = $request->input('store_id');
        $result  = $this->issueService->syncHpp($storeId ? (int) $storeId : null);
        return response()->json([
            'message' => 'HPP berhasil disinkronisasi.',
            'updated' => $result['updated'],
            'errors'  => $result['errors'],
        ]);
    }

    public function autoMapByCode(Request $request): JsonResponse
    {
        set_time_limit(300);
        $storeId = $request->input('store_id');
        $result  = $this->issueService->autoMapByCode($storeId ? (int) $storeId : null);
        return response()->json([
            'message' => 'Auto-map selesai.',
            'mapped'  => $result['mapped'],
            'skipped' => $result['skipped'],
            'errors'  => $result['errors'],
        ]);
    }

    /** [DEV ONLY] Hapus semua marketplace orders + fulfillments + mutations untuk reset testing. */
    public function devFreshOrders(): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['message' => 'Tidak diizinkan di production.'], 403);
        }

        \DB::transaction(function () {
            \DB::table('inventory_mutations')
                ->whereIn('source_type', ['order_fulfillment', 'order_fulfillment_substitution'])
                ->delete();
            \DB::table('order_fulfillment_lines')->delete();
            \DB::table('order_fulfillments')->delete();
            \DB::table('marketplace_order_items')->delete();
            \DB::table('marketplace_orders')->delete();
        });

        return response()->json(['message' => '✅ Semua marketplace orders berhasil dihapus. Siap sync ulang.']);
    }

    /** [DEV ONLY] Hapus fulfillments saja, orders tetap (reset ke state "Perlu Proses"). */
    public function devResetFulfillments(): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['message' => 'Tidak diizinkan di production.'], 403);
        }

        \DB::transaction(function () {
            $mutations = \DB::table('inventory_mutations')
                ->whereIn('source_type', ['order_fulfillment', 'order_fulfillment_substitution'])
                ->delete();
            \DB::table('order_fulfillment_lines')->delete();
            $fulfillments = \DB::table('order_fulfillments')->delete();
        });

        $orders = \DB::table('marketplace_orders')->count();

        return response()->json([
            'message' => "✅ Semua fulfillments dihapus. {$orders} order kembali ke tab Perlu Proses.",
            'orders'  => $orders,
        ]);
    }

    /** [DEV ONLY] Return order READY_TO_SHIP berikutnya yang belum punya fulfillment. */
    public function devNextOrder(): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['message' => 'Tidak diizinkan di production.'], 403);
        }

        $order = MarketplaceOrder::whereIn('order_status', ['READY_TO_SHIP', 'PROCESSED'])
            ->whereDoesntHave('fulfillment')
            ->orderByDesc('ordered_at')
            ->first(['id', 'channel_order_id', 'buyer_username', 'order_status']);

        if (! $order) {
            return response()->json(['message' => 'Tidak ada order yang perlu diproses.', 'order' => null]);
        }

        return response()->json(['order' => [
            'id'               => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_username'   => $order->buyer_username,
            'order_status'     => $order->order_status,
        ]]);
    }

    /** [DEV ONLY] Stats ringkas untuk dev panel. */
    public function devStats(): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['message' => 'Tidak diizinkan di production.'], 403);
        }

        $orders       = \DB::table('marketplace_orders')->count();
        $perluProses  = \DB::table('marketplace_orders')
            ->whereIn('order_status', ['READY_TO_SHIP', 'PROCESSED'])
            ->whereNotExists(fn($q) => $q->from('order_fulfillments')->whereColumn('marketplace_order_id','marketplace_orders.id'))
            ->count();
        $sedangPacking = \DB::table('order_fulfillments')
            ->whereNotIn('status', ['confirmed', 'cancelled'])
            ->count();
        $fulfilled    = \DB::table('order_fulfillments')->where('status', 'confirmed')->count();

        return response()->json(compact('orders', 'perluProses', 'sedangPacking', 'fulfilled'));
    }

    /** [DEV ONLY] Buat dummy marketplace orders untuk testing. */
    public function devSeedOrders(Request $request): JsonResponse
    {
        if (app()->isProduction()) {
            return response()->json(['message' => 'Tidak diizinkan di production.'], 403);
        }

        $count  = max(1, min(50, (int) ($request->input('count', 5))));
        $status = strtoupper($request->input('status', 'READY_TO_SHIP'));

        if (! in_array($status, ['READY_TO_SHIP', 'PROCESSED'])) {
            return response()->json(['message' => 'Status tidak valid.'], 422);
        }

        // Auto-create dummy channel + store jika belum ada (dev convenience)
        $channel = \DB::table('marketplace_channels')->first();
        if (! $channel) {
            $channelId = \DB::table('marketplace_channels')->insertGetId([
                'code'       => 'shopee',
                'name'       => 'Shopee',
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $channelId = $channel->id;
        }

        $store = \App\Models\MarketplaceStore::first();
        if (! $store) {
            $store = \App\Models\MarketplaceStore::create([
                'channel_id'  => $channelId,
                'name'        => 'Toko Dev (Dummy)',
                'short_code'  => 'DEV',
                'is_active'   => true,
            ]);
        }

        // Ambil SKU mappings yang ada sebagai referensi item dummy
        $mappings = \App\Models\SkuMapping::with('item')->whereNotNull('item_id')->get();

        $buyers   = ['budi_santoso88','siti_rahayu22','agus_wijaya99','dewi_lestari7','rizky_pratama',
                     'nurul_hidayah','arif_firmansyah','maya_kusuma','fandi_ahmadi','linda_permata'];
        $carriers = ['JNE','J&T Express','SiCepat','AnterAja','SPX Express'];

        $created = 0;
        try {
        \DB::transaction(function () use ($count, $status, $store, $mappings, $buyers, $carriers, &$created) {
            for ($i = 0; $i < $count; $i++) {
                $channelId = now()->format('ymd') . strtoupper(\Str::random(8));

                $order = MarketplaceOrder::create([
                    'store_id'           => $store->id,
                    'channel_order_id'   => $channelId,
                    'external_order_id'  => $channelId,
                    'booking_sn'         => $channelId,
                    'order_date'         => now()->subMinutes(rand(5, 1440)),
                    'order_status'       => $status,
                    'status'             => $status,
                    'buyer_username'   => $buyers[array_rand($buyers)],
                    'payment_method'   => rand(0, 1) ? 'ShopeePay' : 'COD',
                    'shipping_carrier' => $carriers[array_rand($carriers)],
                    'currency'         => 'IDR',
                    'ordered_at'       => now()->subMinutes(rand(5, 1440)),
                    'synced_at'        => now(),
                    'total_amount'     => 0,
                ]);

                $itemCount   = rand(1, 3);
                $totalAmount = 0;

                // Pilih mapping acak (tanpa duplikat dalam 1 order)
                $pool = $mappings->isNotEmpty()
                    ? $mappings->shuffle()->take($itemCount)
                    : collect();

                // Isi sisa dengan item tanpa mapping jika kurang
                $slots = $pool->map(fn($m) => ['mapped' => true, 'mapping' => $m])
                    ->concat(collect(range(1, max(0, $itemCount - $pool->count())))
                        ->map(fn() => ['mapped' => false, 'mapping' => null]));

                foreach ($slots as $slot) {
                    $qty   = rand(1, 3);
                    $price = rand(15, 200) * 1000;

                    if ($slot['mapped'] && $slot['mapping']) {
                        // Item punya mapping → resolusi status
                        $m              = $slot['mapping'];
                        $itemName       = $m->item?->name ?? 'Produk ' . $m->marketplace_sku;
                        $sku            = $m->marketplace_sku;
                        $internalItem   = $m->item;
                        $hasHpp         = $internalItem && $internalItem->hpp > 0;
                        $mappingStatus  = 'mapped';
                        $costStatus     = $hasHpp ? 'complete'     : 'missing_hpp';
                        $profitStatus   = $hasHpp ? 'complete'     : 'incomplete';
                        $dataStatus     = $hasHpp ? 'valid'        : 'incomplete';
                        $issueReason    = $hasHpp ? null           : 'missing_hpp';
                        $internalItemId = $internalItem?->id;
                        $hppSnapshot    = $hasHpp ? $internalItem->hpp : null;
                    } else {
                        // Item tanpa mapping → muncul di /issues
                        $itemName       = 'Sample ' . strtoupper(\Str::random(5));
                        $sku            = 'SKU-' . strtoupper(\Str::random(6));
                        $mappingStatus  = 'mapping_not_found';
                        $costStatus     = 'missing_hpp';
                        $profitStatus   = 'incomplete';
                        $dataStatus     = 'incomplete';
                        $issueReason    = 'mapping_not_found';
                        $internalItemId = null;
                        $hppSnapshot    = null;
                    }

                    MarketplaceOrderItem::create([
                        'marketplace_order_id' => $order->id,
                        'order_id'             => $order->id,
                        'external_item_id'     => rand(100000, 999999),
                        'external_model_id'    => rand(1000000, 9999999),
                        'item_name'            => $itemName,
                        'item_sku'             => $sku,
                        'model_sku'            => $sku,
                        'qty'                  => $qty,
                        'price'                => $price,
                        'mapping_status'       => $mappingStatus,
                        'cost_status'          => $costStatus,
                        'profit_status'        => $profitStatus,
                        'data_status'          => $dataStatus,
                        'issue_reason'         => $issueReason,
                        'internal_item_id'     => $internalItemId,
                        'hpp_snapshot'         => $hppSnapshot,
                    ]);

                    $totalAmount += $qty * $price;
                }

                $order->update(['total_amount' => $totalAmount]);
                $created++;
            }
        });

        } catch (\Throwable $e) {
            return response()->json([
                'message' => '❌ Error: ' . $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ], 500);
        }

        return response()->json([
            'message' => "✅ {$created} dummy order ({$status}) berhasil dibuat.",
            'count'   => $created,
            'status'  => $status,
        ]);
    }
    public function saveAdsSetting(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'target_roas' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $setting = \App\Models\MarketplaceAdsSetting::updateOrCreate(
            ['store_id' => $request->store_id],
            [
                'target_roas' => $request->target_roas,
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan kampanye berhasil disimpan.',
            'data' => $setting
        ]);
    }
}
