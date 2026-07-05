<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncOrdersRequest;
use App\Models\Channel;
use App\Models\ItemCostSnapshot;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceOrder;
use App\Models\Item;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceSyncLog;
use App\Models\OrderFulfillment;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Channels\ChannelManager;
use App\Services\MarketplaceIssueService;
use App\Services\MarketplaceSyncService;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function __construct(
        protected MarketplaceSyncService $sync,
        protected ChannelManager $manager,
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
        return view('marketplace.orders', compact('filters'));
    }

    public function fulfillment(): \Illuminate\View\View
    {
        return view('marketplace.fulfillment');
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

    public function issueCenter(): \Illuminate\View\View
    {
        return view('marketplace.issues');
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
            'name'                 => $store->name,
            'external_shop_id'     => $store->external_shop_id,
            'region'               => $store->region,
            'status'               => $store->status,
            'token_expires_at'     => $store->token_expires_at?->toISOString(),
            'last_synced_at'       => $store->last_synced_at?->toISOString(),
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

    public function shopInfo(Store $store): JsonResponse
    {
        return response()->json(
            $this->manager->driver($store)->getShopInfo($store)
        );
    }

    public function syncOrders(SyncOrdersRequest $request, Store $store): JsonResponse
    {
        try {
            $result = $this->sync->syncOrders(
                $store,
                (int) $request->time_from,
                (int) $request->time_to,
                (int) ($request->page_size ?? 50),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function localOrders(): JsonResponse
    {
        // Sertakan scan_log jika kolom sudah ada (setelah migration)
        $hasScanLog = in_array(
            'scan_log',
            \Illuminate\Support\Facades\Schema::getColumnListing('order_fulfillments')
        );
        $fulfillmentSelect = $hasScanLog
            ? 'id,marketplace_order_id,status,scan_log'
            : 'id,marketplace_order_id,status';

        $orders = MarketplaceOrder::with([
                'store.channel',
                'items',
                'items.internalItem' => fn ($q) => $q->select('id', 'code', 'item_category_id')->with('category:id,code,name'),
                'fulfillment:' . $fulfillmentSelect,
                'fulfillment.lines',
                'fulfillment.lines.item:id,code,name',
                'fulfillment.lines.splitChildren',
                'fulfillment.lines.splitChildren.item:id,code,name',
            ])
            ->latest('ordered_at')
            ->limit(200)
            ->get();

        return response()->json($orders->map(function ($o) use ($hasScanLog) {
            $arr = $o->toArray();
            $arr['fulfillment_id']         = $o->fulfillment?->id;
            $arr['fulfillment_status']     = $o->fulfillment?->status; // null|draft|pending_review|confirmed|cancelled
            $arr['has_unresolved_lines']   = $o->fulfillment
                ? $o->fulfillment->lines->whereNull('item_id')->isNotEmpty()
                : false;
            // Issues: ada item dengan data_status != 'valid' (sama seperti halaman /marketplace/issues)
            $arr['has_data_issues'] = $o->items->contains(
                fn ($item) => ($item->data_status ?? 'incomplete') !== 'valid'
            );

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
        }));
    }

    public function syncSettlements(Request $request, Store $store): JsonResponse
    {
        try {
            $result = $this->sync->syncSettlements(
                $store,
                $request->input('time_from') ? (int) $request->input('time_from') : null,
                $request->input('time_to')   ? (int) $request->input('time_to')   : null,
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function settlements(Request $request): JsonResponse
    {
        $query = MarketplaceOrderSettlement::with(['store:id,name', 'order:id,channel_order_id,order_status,ordered_at'])
            ->latest('settlement_time');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $settlements = $query->limit(200)->get()->map(fn ($s) => [
            'id'                    => $s->id,
            'store'                 => $s->store,
            'order'                 => $s->order,
            'channel_order_id'      => $s->channel_order_id,
            'buyer_payment_amount'  => (float) $s->buyer_payment_amount,
            'commission_fee'        => (float) $s->commission_fee,
            'service_fee'           => (float) $s->service_fee,
            'transaction_fee'       => (float) $s->transaction_fee,
            'seller_voucher'        => (float) $s->seller_voucher,
            'seller_coin_cash_back' => (float) $s->seller_coin_cash_back,
            'actual_shipping_fee'   => (float) $s->actual_shipping_fee,
            'shipping_fee_subsidy'  => (float) $s->shipping_fee_subsidy,
            'reverse_shipping_fee'  => (float) $s->reverse_shipping_fee,
            'activity_fee'          => (float) $s->activity_fee,
            'drc_adjustable_refund' => (float) $s->drc_adjustable_refund,
            'escrow_tax'            => (float) $s->escrow_tax,
            'final_income'          => (float) $s->final_income,
            'settlement_time'       => $s->settlement_time?->toISOString(),
            'synced_at'             => $s->synced_at?->toISOString(),
        ]);

        return response()->json($settlements);
    }

    public function orderProfits(Request $request): JsonResponse
    {
        $query = MarketplaceOrderSettlement::with([
            'store:id,name,channel_id',
            'store.channel:id,code,name',
            'order:id,channel_order_id,order_status,ordered_at',
            'order.items:id,marketplace_order_id,model_sku,item_sku,qty,price',
        ])->latest('settlement_time');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $settlements = $query->limit(200)->get();

        // Pre-load HPP: build map sku → hpp_unit
        // Collect all unique SKUs from all items
        $allSkus = $settlements->flatMap(fn ($s) => $s->order?->items ?? collect())
            ->map(fn ($item) => $item->model_sku ?: $item->item_sku)
            ->filter()
            ->unique()
            ->values();

        $channelCode = $settlements->first()?->store?->channel?->code;

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

        $rows = $settlements->map(function ($s) use ($skuToHpp) {
            $items    = $s->order?->items ?? collect();
            $hppTotal = 0.0;
            $hppMapped = true;

            foreach ($items as $item) {
                $sku = $item->model_sku ?: $item->item_sku;
                if ($sku && isset($skuToHpp[$sku])) {
                    $hppTotal += $skuToHpp[$sku] * (int) $item->qty;
                } else {
                    $hppMapped = false;
                }
            }

            $finalIncome = (float) $s->final_income;
            $adCost      = (float) $s->ad_cost;
            $profitNet   = $finalIncome - $hppTotal - $adCost;

            return [
                'id'                    => $s->id,
                'channel_order_id'      => $s->channel_order_id,
                'store'                 => $s->store ? ['id' => $s->store->id, 'name' => $s->store->name] : null,
                'order'                 => $s->order ? [
                    'order_status' => $s->order->order_status,
                    'ordered_at'   => $s->order->ordered_at?->toISOString(),
                ] : null,
                'buyer_payment_amount'  => (float) $s->buyer_payment_amount,
                'final_income'          => $finalIncome,
                'hpp_total'             => $hppTotal,
                'hpp_mapped'            => $hppMapped,
                'ad_cost'               => $adCost,
                'profit_gross'          => $finalIncome - $hppTotal,  // before ad cost
                'profit_net'            => $profitNet,
                'margin_pct'            => $s->buyer_payment_amount > 0
                    ? round($profitNet / (float) $s->buyer_payment_amount * 100, 1)
                    : null,
                'settlement_time'       => $s->settlement_time?->toISOString(),
                // Detail potongan (untuk tooltip)
                'commission_fee'        => (float) $s->commission_fee,
                'service_fee'           => (float) $s->service_fee,
                'transaction_fee'       => (float) $s->transaction_fee,
                'activity_fee'          => (float) $s->activity_fee,
                'seller_voucher'        => (float) $s->seller_voucher,
                'seller_coin_cash_back' => (float) $s->seller_coin_cash_back,
                'shipping_fee_subsidy'  => (float) $s->shipping_fee_subsidy,
            ];
        });

        return response()->json($rows);
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

        try {
            $result = $this->sync->syncAdCampaigns($store, $dateFrom, $dateTo);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    /** Debug: lihat raw response Shopee Ads API (hapus setelah selesai debug) */
    public function debugAdApi(Request $request, Store $store): JsonResponse
    {
        $driver     = $this->manager->driver($store);
        $sampleIds  = [34741562, 34741571, 65538832]; // 3 campaign pertama
        $today      = now()->format('d-m-Y');
        $monthAgo   = now()->subDays(29)->format('d-m-Y');

        return response()->json([
            'toggle_info'    => $driver->getShopToggleInfo($store),
            'campaign_ids'   => $driver->getCampaignIdList($store, 1, 5),
            'setting_info'   => $driver->getCampaignSettingInfo($store, $sampleIds),
            'daily_perf'     => $driver->getCampaignDailyPerformance($store, $sampleIds, $monthAgo, $today),
        ]);
    }

    public function adsAnalytics(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());
        $storeId  = $request->input('store_id');

        // ── 1. Ambil campaign dari tabel API-synced ──────────────────────────
        $query = MarketplaceAdCampaign::with('store:id,name')
            ->where('report_date_from', '>=', $dateFrom)
            ->where('report_date_to',   '<=', $dateTo)
            ->orderByDesc('spend');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $campaigns = $query->limit(200)->get();

        if ($campaigns->isEmpty()) {
            return response()->json([
                'rows' => [],
                'kpi'  => ['spend' => 0, 'gmv' => 0, 'roas' => null, 'acos' => null,
                           'orders' => 0, 'clicks' => 0, 'profit_after_ads' => null],
            ]);
        }

        // ── 2. Build rows dengan break-even ACOS + rekomendasi ───────────────
        // Break-even ACOS untuk campaign-level: pakai nilai manual dari DB jika ada,
        // atau biarkan null (user bisa set via endpoint update).
        $rows = $campaigns->map(function (MarketplaceAdCampaign $c) {
            $spend     = (float) $c->spend;
            $gmv       = (float) $c->gmv;
            $orders    = (int) $c->orders;

            // ACOS (0..1) — hitung dari data mentah agar akurat
            $acos = $gmv > 0 && $spend > 0 ? round($spend / $gmv, 4) : null;

            // ROAS
            $roas = $spend > 0 ? round($gmv / $spend, 2) : null;

            // Break-even ACOS dari kolom manual (user bisa di-set)
            $beAcos  = $c->break_even_acos !== null ? (float) $c->break_even_acos : null;
            $bePct   = $beAcos !== null ? round($beAcos * 100, 1) : null;

            // Profit setelah iklan: hanya bisa dihitung jika break-even ACOS diset
            // gross_margin = beAcos × gmv → profit_after_ads = gmv×beAcos - spend
            $profitAfterAds = ($beAcos !== null) ? round($gmv * $beAcos - $spend, 2) : null;

            $reco = $this->adsRecommendation($spend, $acos, $beAcos, $orders);

            return [
                'id'              => $c->id,
                'store_name'      => $c->store?->name,
                'campaign_id'     => $c->channel_campaign_id,
                'campaign_name'   => $c->campaign_name,
                'campaign_type'   => $c->campaign_type,
                'status'          => $c->status,
                'spend'           => $spend,
                'gmv'             => $gmv,
                'direct_gmv'      => (float) $c->direct_gmv,
                'impressions'     => (int) $c->impressions,
                'clicks'          => (int) $c->clicks,
                'orders'          => $orders,
                'items_sold'      => (int) $c->items_sold,
                'roas'            => $roas,
                'direct_roas'     => $c->direct_roas !== null ? (float) $c->direct_roas : null,
                'cpc'             => $c->cpc !== null ? (float) $c->cpc : null,
                'acos'            => $acos,
                'acos_pct'        => $acos !== null ? round($acos * 100, 1) : null,
                'break_even_acos'     => $beAcos,
                'break_even_acos_pct' => $bePct,
                'profit_after_ads'    => $profitAfterAds,
                'reco'            => $reco,
            ];
        });

        // ── 3. Overall KPI ────────────────────────────────────────────────────
        $totSpend  = $rows->sum('spend');
        $totGmv    = $rows->sum('gmv');

        $kpi = [
            'spend'           => $totSpend,
            'gmv'             => $totGmv,
            'roas'            => $totSpend > 0 ? round($totGmv / $totSpend, 2) : null,
            'acos'            => $totGmv  > 0 ? round($totSpend / $totGmv * 100, 1) : null,
            'orders'          => $rows->sum('orders'),
            'clicks'          => $rows->sum('clicks'),
            'profit_after_ads' => $rows->filter(fn ($r) => $r['profit_after_ads'] !== null)->sum('profit_after_ads') ?: null,
        ];

        return response()->json(compact('rows', 'kpi'));
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

    public function syncLogs(): JsonResponse
    {
        $logs = MarketplaceSyncLog::with('store:id,name')
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
            'name'                 => ['sometimes', 'string', 'max:120'],
            'default_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);

        $store->update($data);

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

            return [
                'id'                  => $i->id,
                'order_id'            => $i->order?->id,
                'order_number'        => $i->order?->channel_order_id ?? $i->order?->external_order_id,
                'ordered_at'          => $i->order?->ordered_at?->toISOString() ?? $i->order?->order_date,
                'store_name'          => $i->order?->store?->name,
                'channel_code'        => $i->order?->store?->channel?->code,
                'item_name'           => $i->item_name ?? $i->item_name_snapshot,
                'variant_name'        => $i->variant_name ?? $i->variant_snapshot,
                'marketplace_sku'     => $i->marketplace_sku ?? $i->model_sku ?? $i->item_sku ?? $i->external_sku,
                'qty'                 => $i->qty,
                'mapping_status'      => $i->mapping_status,
                'cost_status'         => $i->cost_status,
                'profit_status'       => $i->profit_status,
                'data_status'         => $i->data_status,
                'issue_reason'        => $i->issue_reason,
                'internal_item_id'    => $i->internal_item_id,
                'internal_item_name'  => $i->internalItem?->name,
                'internal_item_code'  => $i->internalItem?->code,
                'hpp_current'         => $i->internalItem ? (float) ($i->internalItem->base_unit_cost ?: $i->internalItem->hpp ?: 0) : 0,
                'hpp_snapshot'        => $i->hpp_snapshot,
                'recommended_item'    => $rec,
            ];
        });

        return response()->json([
            'data'         => $items->items(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
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
        return response()->json($this->issueService->summary($storeId ? (int) $storeId : null));
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

}
