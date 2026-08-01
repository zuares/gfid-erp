<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\Ads\AdsActionService;
use App\Services\Marketplace\Ads\AdsDashboardService;
use App\Services\Marketplace\Ads\AdsAnalyticsService;
use App\Services\Marketplace\Ads\ItemHppResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Services\Marketplace\Ads\ShopeeAdsApiService;

class AdsDashboardController extends Controller
{
    public function index(Request $request, AdsAnalyticsService $analytics, AdsDashboardService $dashboardService)
    {
        $stores = Store::select('id', 'name')
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')
            ->where('is_active', true)
            ->where('token_expires_at', '>', now())
            ->get();

        $storeId = $request->input('store_id', 'all');
        if ($stores->isEmpty()) {
            $dateFrom = now()->subDays(6)->toDateString();
            $dateTo = now()->toDateString();
            $compareMode = in_array($request->input('compare_mode'), ['prev_period', 'prev_month', 'prev_year'], true)
                ? $request->input('compare_mode')
                : 'prev_period';

            return view('marketplace.ads_dashboard', compact('stores', 'storeId', 'dateFrom', 'dateTo', 'compareMode'))
                ->with('error', 'Tidak ada toko Shopee aktif dengan token valid.');
        }

        // Auto-sync mengikuti range yang sedang dipilih. Hanya range recent
        // maksimal 7 hari yang dijalankan otomatis agar membuka range historis
        // panjang tidak memicu backfill/API request besar.
        $today = now()->startOfDay();
        $selectedFrom = $request->input('date_from');
        $selectedTo = $request->input('date_to');
        $autoSyncRange = null;

        try {
            $selectedFrom = $selectedFrom
                ? Carbon::parse($selectedFrom)->startOfDay()
                : $today->copy()->subDays(6);
            $selectedTo = $selectedTo
                ? Carbon::parse($selectedTo)->startOfDay()
                : $today->copy();

            $days = $selectedFrom->lte($selectedTo)
                ? $selectedFrom->diffInDays($selectedTo) + 1
                : 0;

            if (
                $days >= 1
                && $days <= 7
                && $selectedFrom->gte($today->copy()->subDays(6))
                && $selectedTo->lte($today)
            ) {
                $autoSyncRange = [
                    'from' => $selectedFrom->toDateString(),
                    'to' => $selectedTo->toDateString(),
                ];
            }
        } catch (\Throwable) {
            // Range invalid: biarkan dashboard menampilkan data sesuai fallback,
            // tetapi jangan membuat job auto-sync dengan tanggal yang tidak valid.
        }

        if ($autoSyncRange) {
            $autoSyncKey = 'ads_dashboard_autosync:' . $storeId . ':' . $autoSyncRange['from'] . ':' . $autoSyncRange['to'];
            if (! \Illuminate\Support\Facades\Cache::has($autoSyncKey)) {
                $syncParams = [
                    '--from' => $autoSyncRange['from'],
                    '--to'   => $autoSyncRange['to'],
                ];
                if ($storeId !== 'all') {
                    $syncParams['--store'] = $storeId;
                }

                // Kirim command wrapper ke queue ads; job sync-nya juga memakai queue ads.
                $queued = \Illuminate\Support\Facades\Artisan::queue('marketplace:sync-ads', $syncParams);
                $queued->onQueue('ads');

                \Illuminate\Support\Facades\Cache::put($autoSyncKey, true, now()->addMinutes(15));
            }
        }

        $compareMode = in_array($request->input('compare_mode'), ['prev_period', 'prev_month', 'prev_year'], true)
            ? $request->input('compare_mode')
            : 'prev_period';

        $dashboard = $dashboardService->buildDashboardData(
            $stores,
            $storeId,
            $request->input('date_from'),
            $request->input('date_to'),
            $compareMode,
            $analytics
        );
        
        $lastSync = \App\Models\MarketplaceAdsSyncRun::where('status', 'success')
            ->when($storeId !== 'all', fn($q) => $q->where('store_id', $storeId))
            ->latest('finished_at')
            ->first();
            
        $dashboard['lastSyncAt'] = $lastSync && $lastSync->finished_at ? $lastSync->finished_at->diffForHumans() : 'Belum pernah';
        $dashboard['lastSyncTime'] = $lastSync && $lastSync->finished_at ? $lastSync->finished_at->format('d M Y H:i') : '';

        return view('marketplace.ads_dashboard', $dashboard);
    }

    /** Simpan pengaturan admin fee (otomatis dari settlement / manual %). */
    public function saveFeeSetting(Request $request)
    {
        $data = $request->validate([
            'store_id'       => 'required|exists:stores,id',
            'admin_fee_mode' => 'required|in:auto,manual',
            'admin_fee_pct'  => 'required_if:admin_fee_mode,manual|nullable|numeric|min:0|max:99',
        ]);

        \App\Models\MarketplaceAdsSetting::updateOrCreate(
            ['store_id' => $data['store_id']],
            [
                'admin_fee_mode' => $data['admin_fee_mode'],
                'admin_fee_pct'  => $data['admin_fee_mode'] === 'manual' ? $data['admin_fee_pct'] : null,
            ]
        );

        return back()->with('success', 'Pengaturan admin fee disimpan.');
    }

    public function sync(Request $request)
    {
        $request->validate([
            'sync_type' => 'required|in:today,yesterday,last_7_days,custom',
        ]);

        $storeId = $request->input('store_id');
        $isAllStores = $storeId === null || $storeId === '' || $storeId === 'all';
        if (! $isAllStores) {
            $request->validate([
                'store_id' => 'required|exists:stores,id',
            ]);
        }

        $type = $request->input('sync_type');
        $dateFrom = match($type) {
            'yesterday' => now()->subDay()->toDateString(),
            'last_7_days' => now()->subDays(7)->toDateString(),
            'custom' => $request->input('date_from_custom') ?? now()->subMonths(1)->toDateString(),
            default => now()->toDateString(),
        };
        $dateTo = match($type) {
            'yesterday' => now()->subDay()->toDateString(),
            'custom' => $request->input('date_to_custom') ?? now()->toDateString(),
            default => now()->toDateString(),
        };

        $store = $isAllStores ? null : Store::findOrFail($storeId);
        $storeLabel = $isAllStores ? 'Semua toko' : $store->name;
        $progressKey = 'marketplace:ads_sync_progress:' . ($isAllStores ? 'all' : $storeId);
        $dayLabel = match($type) {
            'yesterday' => 'kemarin',
            'last_7_days' => '1 minggu terakhir',
            'custom' => "rentang {$dateFrom} s/d {$dateTo}",
            default => 'hari ini',
        };
        $label = "Sync {$dayLabel} untuk {$storeLabel} sedang antre…";

        \Illuminate\Support\Facades\Cache::put($progressKey, [
            'status'     => 'queued',
            'phase'      => 'queued',
            'percent'    => 3,
            'label'      => $label,
            'store_id'   => $isAllStores ? null : $store->id,
            'store_name' => $storeLabel,
            'mode'       => $type,
            'updated_at' => now()->toISOString(),
        ], 1800);

        $params = [
            '--from' => $dateFrom,
            '--to'   => $dateTo,
        ];
        if (! $isAllStores) {
            $params['--store'] = $storeId;
        }
        if ($type === 'custom') {
            $params['--backfill'] = true;
        }

        $queued = Artisan::queue('marketplace:sync-ads', $params);
        $queued->onQueue('ads');

        $message = "Sync {$dayLabel} untuk {$storeLabel} telah dikirim ke latar belakang. Pantau progress di tab Sinkronisasi.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'queued',
                'message' => $message,
                'progress_key' => $progressKey,
            ]);
        }

        return back()->with('success', $message);
    }

    public function syncProgress(Request $request)
    {
        $storeId = $request->input('store_id', 'all');
        $progressKey = 'marketplace:ads_sync_progress:' . $storeId;
        
        $data = \Illuminate\Support\Facades\Cache::get($progressKey);
        
        if (!$data) {
            return response()->json([
                'status' => 'idle',
                'phase' => 'idle',
                'percent' => 0,
                'label' => 'Tidak ada proses berjalan',
            ]);
        }
        
        return response()->json($data);
    }

    public function clear(Request $request)
    {
        if (auth()->user()->role !== 'owner') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tables = [
            'marketplace_ad_campaign_dailies',
            'marketplace_ad_campaigns',
            'marketplace_ad_groups',
            'marketplace_ad_item_maps',
            'marketplace_ads_balance_logs',
            'marketplace_ads_campaign_items',
            'marketplace_ads_dailies',
            'marketplace_ads_hourly_performances',
            'marketplace_ads_item_dailies',
            'marketplace_ads_sync_runs',
            'mp_ads_imports',
            'mp_ads_rows'
        ];

        foreach ($tables as $table) {
            \Illuminate\Support\Facades\DB::table($table)->truncate();
        }

        return response()->json(['status' => 'success', 'message' => 'Semua data iklan berhasil dihapus.']);
    }

    public function realtimeStatus(Request $request, AdsActionService $actions, ShopeeAdsApiService $adsApi)
    {
        $storeId = $request->input('store_id');
        if (!$storeId) {
            return response()->json(['error' => 'store_id is required'], 400);
        }

        $store = Store::find($storeId);
        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        try {
            return response()->json($actions->realtimeStatus($store, $adsApi));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function actionGmsItem(Request $request, AdsActionService $actions, \App\Services\Channels\Shopee\ShopeeChannel $shopeeChannel)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'item_id' => 'required|numeric',
            'action' => 'required|in:add,remove'
        ]);

        $store = Store::find($request->input('store_id'));
        if (!$store) {
            return response()->json(['status' => 'error', 'message' => 'Store not found.'], 404);
        }

        try {
            $result = $actions->actionGmsItem($store, (int) $request->input('item_id'), $request->input('action'), $shopeeChannel);
            return response()->json(Arr::except($result, ['http_status']), $result['http_status'] ?? 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function actionCpcCampaign(Request $request, AdsActionService $actions, \App\Services\Channels\Shopee\ShopeeChannel $shopeeChannel)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'campaign_id' => 'required|numeric',
            'roas_target' => 'nullable|numeric|min:0',
            'daily_budget' => 'nullable|numeric|min:0'
        ]);

        $store = Store::find($request->input('store_id'));
        if (!$store) {
            return response()->json(['status' => 'error', 'message' => 'Store not found.'], 404);
        }

        $campaignId = (int)$request->input('campaign_id');
        $roasTarget = $request->input('roas_target');
        $dailyBudget = $request->input('daily_budget');
        $statusAction = $request->input('status_action'); // 'pause' or 'resume'

        try {
            $result = $actions->actionCpcCampaign($store, $campaignId, $roasTarget !== null && $roasTarget !== '' ? (float) $roasTarget : null, $dailyBudget !== null && $dailyBudget !== '' ? (float) $dailyBudget : null, $statusAction, $shopeeChannel);
            return response()->json(Arr::except($result, ['http_status']), $result['http_status'] ?? 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function actionGmsCampaign(Request $request, AdsActionService $actions, \App\Services\Channels\Shopee\ShopeeChannel $shopeeChannel)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'campaign_id' => 'nullable|string',
            'roas_target' => 'nullable|numeric|min:0',
            'daily_budget' => 'nullable|numeric|min:0'
        ]);

        $store = Store::find($request->input('store_id'));
        if (!$store) {
            return response()->json(['status' => 'error', 'message' => 'Store not found.'], 404);
        }

        $campaignId = $request->input('campaign_id');
        // Jika ID adalah pseudo-ID global seperti "GMS-5", kita kosongkan agar API mengedit kampanye GMS tingkat toko
        if (is_string($campaignId) && str_starts_with($campaignId, 'GMS-')) {
            $campaignId = null;
        }

        $roasTarget = $request->input('roas_target');
        $dailyBudget = $request->input('daily_budget');

        try {
            $result = $actions->actionGmsCampaign($store, $campaignId, $roasTarget !== null && $roasTarget !== '' ? (float) $roasTarget : null, $dailyBudget !== null && $dailyBudget !== '' ? (float) $dailyBudget : null, $shopeeChannel);
            return response()->json(Arr::except($result, ['http_status']), $result['http_status'] ?? 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function campaignHourly(Request $request, AdsActionService $actions, ShopeeAdsApiService $adsApi)
    {
        $storeId = $request->input('store_id');
        $campaignId = $request->input('campaign_id');
        $date = $request->input('date', now()->format('d-m-Y'));

        if (!$storeId || !$campaignId) {
            return response()->json(['error' => 'store_id dan campaign_id diperlukan'], 400);
        }

        $store = Store::find($storeId);
        if (!$store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        try {
            $result = $actions->campaignHourly($store, $campaignId, $date, $adsApi);
            return response()->json(Arr::except($result, ['http_status']), $result['http_status'] ?? 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detail harian untuk drilldown campaign GMV Max ROAS atau item GMV Max Auto.
     * Sumbernya adalah fakta harian hasil sync agar grafik mengikuti filter dashboard.
     */
    public function drilldown(Request $request, string $store, ItemHppResolver $hppResolver, AdsDashboardService $dashboardService): JsonResponse
    {
        $kind = $request->input('kind', 'campaign');
        $entityId = trim((string) $request->input('id', ''));
        $from = $request->input('date_from', now()->subDays(6)->toDateString());
        $to = $request->input('date_to', now()->toDateString());
        $storeId = strtolower($store) === 'all' ? null : (ctype_digit($store) ? (int) $store : null);

        if (!in_array($kind, ['campaign', 'gms_item', 'category'], true) || $entityId === '') {
            return response()->json(['message' => 'Target drilldown tidak valid.'], 422);
        }
        if (in_array($kind, ['campaign', 'gms_item'], true) && $storeId === null) {
            return response()->json(['message' => 'Toko drilldown tidak valid.'], 422);
        }

        try {
            $fromDate = Carbon::parse($from)->toDateString();
            $toDate = Carbon::parse($to)->toDateString();
        } catch (\Throwable) {
            return response()->json(['message' => 'Format tanggal tidak valid.'], 422);
        }

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $unitCogs = 0.0;
        $netRevenueRatio = AdsDashboardService::DEFAULT_NET_REVENUE_RATIO;
        $label = match ($kind) {
            'gms_item' => 'Item GMV Max Auto',
            'category' => $entityId,
            default => 'Kampanye GMV Max ROAS',
        };
        if ($kind === 'campaign') {
            $campaign = \App\Models\MarketplaceAdCampaign::query()
                ->with('internalItem:id,name,code,hpp,base_unit_cost')
                ->where('store_id', $storeId)
                ->where('channel_campaign_id', $entityId)
                ->first();
            if (!$campaign) {
                return response()->json(['message' => 'Kampanye tidak ditemukan.'], 404);
            }
            $unitCogs = $campaign->internalItem ? $hppResolver->resolve($campaign->internalItem) : 0.0;
            [$netRevenueRatio] = $dashboardService->resolveConfiguredNetRevenueRatio($entityId, $storeId);
            $label = $campaign->campaign_name ?: $label;
            $rows = \App\Models\MarketplaceAdCampaignDaily::query()
                ->where('store_id', $storeId)
                ->where('channel_campaign_id', $entityId)
                ->whereBetween('date', [$fromDate, $toDate])
                ->orderBy('date')
                ->get();
        } elseif ($kind === 'gms_item') {
            if (!ctype_digit($entityId)) {
                return response()->json(['message' => 'ID item GMV Max tidak valid.'], 422);
            }

            $manualMap = \App\Models\MarketplaceAdItemMap::query()
                ->with('item:id,name,code,hpp,base_unit_cost')
                ->where('store_id', $storeId)
                ->where('channel_code', 'shopee')
                ->where('channel_item_id', (int) $entityId)
                ->first();
            if ($manualMap?->item) {
                $unitCogs = $hppResolver->resolve($manualMap->item);
            }

            $product = \App\Models\MarketplaceProduct::query()
                ->where('store_id', $storeId)
                ->where('item_id', $entityId)
                ->with('models:id,marketplace_product_id,model_sku,model_name,price,raw_json')
                ->first();
            if ($product) {
                $label = $product->item_name ?: $label;
            }

            if ($unitCogs <= 0 && $product) {
                $skus = $product->models->pluck('model_sku')->push($product->item_sku)
                    ->filter()->map(fn ($sku) => trim((string) $sku))->unique()->values();
                $mappings = \App\Models\SkuMapping::query()
                    ->with('item:id,name,code,hpp,base_unit_cost')
                    ->whereIn('marketplace_sku', $skus->all())
                    ->where(function ($query) {
                        $query->whereNull('channel_code')->orWhereRaw('LOWER(channel_code) = ?', ['shopee']);
                    })
                    ->get();
                $hppValues = $mappings->map(fn ($mapping) => $mapping->item ? $hppResolver->resolve($mapping->item) : 0.0)
                    ->filter(fn ($hpp) => $hpp > 0);
                $unitCogs = $hppValues->isNotEmpty() ? (float) $hppValues->avg() : 0.0;
            }
            [$netRevenueRatio] = $dashboardService->resolveConfiguredNetRevenueRatio($entityId, $storeId);

            $rows = \App\Models\MarketplaceAdsItemDaily::query()
                ->where('store_id', $storeId)
                ->where('channel_campaign_id', 'GMS-' . $storeId)
                ->where('channel_item_id', $entityId)
                ->whereBetween('date', [$fromDate, $toDate])
                ->orderBy('date')
                ->get();
        } else {
            $categoryCampaigns = \App\Models\MarketplaceAdCampaign::query()
                ->with('internalItem:id,item_category_id,hpp,base_unit_cost', 'internalItem.category:id,name')
                ->when($storeId !== null, fn ($query) => $query->where('store_id', $storeId))
                ->where(function ($query) {
                    $query->whereNull('channel_campaign_id')
                        ->orWhere('channel_campaign_id', 'not like', 'GMS-%');
                })
                ->get()
                ->filter(function ($campaign) use ($entityId) {
                    $categoryName = $campaign->internalItem?->category?->name ?: 'Belum termapping';
                    return $categoryName === $entityId;
                });

            $categoryCampaignIds = $categoryCampaigns->pluck('channel_campaign_id')->filter()->values();
            $categoryUnitCogs = $categoryCampaigns->mapWithKeys(function ($campaign) use ($hppResolver) {
                return [
                    (string) $campaign->channel_campaign_id => $campaign->internalItem
                        ? $hppResolver->resolve($campaign->internalItem)
                        : 0.0,
                ];
            });
            $rows = \App\Models\MarketplaceAdCampaignDaily::query()
                ->when($storeId !== null, fn ($query) => $query->where('store_id', $storeId))
                ->whereIn('channel_campaign_id', $categoryCampaignIds->all())
                ->whereBetween('date', [$fromDate, $toDate])
                ->orderBy('date')
                ->get();
        }

        // GMV Max Auto menyimpan biaya pada dua level: item detail dan campaign
        // parent. Tabel sebelum drilldown sudah menormalkan item ke total parent;
        // gunakan skala yang sama agar angka iklan, estimasi cair, dan laba tetap
        // konsisten ketika detail harian dibuka.
        $gmsItemSpendScale = 1.0;
        if ($kind === 'gms_item' && $storeId !== null) {
            $parentSpend = (float) DB::table('marketplace_ad_campaign_dailies')
                ->where('store_id', $storeId)
                ->where('channel_campaign_id', 'GMS-' . $storeId)
                ->whereBetween('date', [$fromDate, $toDate])
                ->sum('expense');
            $itemSpendTotal = (float) DB::table('marketplace_ads_item_dailies')
                ->where('store_id', $storeId)
                ->where('channel_campaign_id', 'GMS-' . $storeId)
                ->whereBetween('date', [$fromDate, $toDate])
                ->sum('expense');
            if ($parentSpend >= 0 && $itemSpendTotal > 0 && abs($parentSpend - $itemSpendTotal) > 0.001) {
                $gmsItemSpendScale = $parentSpend / $itemSpendTotal;
            }
        }

        $rowsByDate = $kind === 'category'
            ? $rows->groupBy(fn ($row) => Carbon::parse($row->date)->toDateString())
            : $rows->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());
        $daily = [];
        $cursor = Carbon::parse($fromDate);
        $end = Carbon::parse($toDate);
        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $dayRows = $kind === 'category' ? $rowsByDate->get($date, collect()) : collect([$rowsByDate->get($date)])->filter();
            $spend = (float) $dayRows->sum('expense');
            if ($kind === 'gms_item') {
                $spend *= $gmsItemSpendScale;
            }
            $gmv = (float) $dayRows->sum('broad_gmv');
            $orders = (int) $dayRows->sum('broad_order');
            $pcs = (float) $dayRows->sum(fn ($row) => (($rawPcs = (float) data_get($row->raw_json, 'broad_order_amount', 0)) > 0)
                ? $rawPcs
                : (float) ($row->broad_order_amount ?? $row->broad_order ?? 0));
            $spendAfterTax = $spend * 1.11;
            $netRevenue = $gmv * $netRevenueRatio;
            $hasSales = $gmv > 0 || $orders > 0 || $pcs > 0;
            $hpp = $kind === 'category'
                ? null
                : (!$hasSales ? 0.0 : ($unitCogs > 0 && $pcs > 0 ? $unitCogs * $pcs : null));
            $profit = $kind === 'category'
                ? $dayRows->map(function ($row) use ($categoryUnitCogs, $netRevenueRatio) {
                    $rowSpend = (float) $row->expense * 1.11;
                    $rowGmv = (float) $row->broad_gmv;
                    $rowOrders = (int) $row->broad_order;
                    $rowRawPcs = (float) data_get($row->raw_json, 'broad_order_amount', 0);
                    $rowPcs = $rowRawPcs > 0 ? $rowRawPcs : (float) ($row->broad_order_amount ?? $rowOrders);
                    $rowHasSales = $rowGmv > 0 || $rowOrders > 0 || $rowPcs > 0;
                    $rowHpp = !$rowHasSales ? 0.0 : (($categoryUnitCogs[(string) $row->channel_campaign_id] ?? 0) > 0 && $rowPcs > 0
                        ? $categoryUnitCogs[(string) $row->channel_campaign_id] * $rowPcs
                        : null);
                    return !$rowHasSales
                        ? round(-$rowSpend, 2)
                        : ($rowHpp !== null ? round(($rowGmv * $netRevenueRatio) - $rowHpp - $rowSpend, 2) : null);
                })->filter(fn ($value) => $value !== null)->sum()
                : (!$hasSales
                    ? round(-$spendAfterTax, 2)
                    : ($hpp !== null ? round($netRevenue - $hpp - $spendAfterTax, 2) : round($netRevenue - $spendAfterTax, 2)));

            $daily[] = [
                'date' => $date,
                'impressions' => (int) $dayRows->sum('impressions'),
                'clicks' => (int) $dayRows->sum('clicks'),
                'spend' => round($spend, 2),
                'spend_after_tax' => round($spendAfterTax, 2),
                'gmv' => round($gmv, 2),
                'orders' => $orders,
                'pcs' => round($pcs, 2),
                'net_revenue' => round($netRevenue, 2),
                'hpp' => $hpp !== null ? round($hpp, 2) : null,
                'profit_after_ads' => $profit,
                'roas' => $spend > 0 ? round($gmv / $spend, 4) : null,
                'aov' => $orders > 0 ? round($gmv / $orders, 2) : null,
            ];
            $cursor->addDay();
        }

        $totalGmv = collect($daily)->sum('gmv');
        $totalSpendAfterTax = collect($daily)->sum('spend_after_tax');
        $knownProfit = collect($daily)->filter(fn ($row) => $row['profit_after_ads'] !== null);

        return response()->json([
            'status' => 'success',
            'kind' => $kind,
            'id' => $entityId,
            'label' => $label,
            'date_from' => $fromDate,
            'date_to' => $toDate,
            'unit_cogs' => round($unitCogs, 2),
            'data' => $daily,
            'totals' => [
                'gmv' => round($totalGmv, 2),
                'spend_after_tax' => round($totalSpendAfterTax, 2),
                'orders' => collect($daily)->sum('orders'),
                'pcs' => round(collect($daily)->sum('pcs'), 2),
                'profit_after_ads' => $knownProfit->isNotEmpty() ? round($knownProfit->sum('profit_after_ads'), 2) : null,
                'roas' => $totalSpendAfterTax > 0 ? round($totalGmv / ($totalSpendAfterTax / 1.11), 4) : null,
            ],
        ]);
    }

    /**
     * Daftar produk yang benar-benar muncul di GMV Max pada periode terpilih.
     * Sumber utama adalah data item performance hasil sync, bukan campaign
     * parent "GMV Max (Semua Produk)" yang hanya menyimpan total agregat.
     */
    public function gmsItems(Request $request, Store $store, ItemHppResolver $hppResolver, AdsDashboardService $dashboardService): JsonResponse
    {
        $from = $request->input('date_from', now()->subDays(6)->toDateString());
        $to = $request->input('date_to', now()->toDateString());

        try {
            $fromDate = Carbon::parse($from)->toDateString();
            $toDate = Carbon::parse($to)->toDateString();
        } catch (\Throwable) {
            return response()->json(['message' => 'Format tanggal tidak valid.'], 422);
        }

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $compareMode = in_array($request->input('compare_mode'), ['prev_period', 'prev_month', 'prev_year'], true)
            ? $request->input('compare_mode')
            : 'prev_period';
        $currentFrom = Carbon::parse($fromDate);
        $currentTo = Carbon::parse($toDate);
        if ($compareMode === 'prev_month') {
            $previousFromDate = $currentFrom->copy()->subMonthNoOverflow()->toDateString();
            $previousToDate = $currentTo->copy()->subMonthNoOverflow()->toDateString();
        } elseif ($compareMode === 'prev_year') {
            $previousFromDate = $currentFrom->copy()->subYear()->toDateString();
            $previousToDate = $currentTo->copy()->subYear()->toDateString();
        } else {
            $periodDays = $currentFrom->diffInDays($currentTo) + 1;
            $previousTo = $currentFrom->copy()->subDay();
            $previousFromDate = $previousTo->copy()->subDays($periodDays - 1)->toDateString();
            $previousToDate = $previousTo->toDateString();
        }

        $rows = \App\Models\MarketplaceAdsItemDaily::query()
            ->where('store_id', $store->id)
            ->where('channel_campaign_id', 'GMS-' . $store->id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date')
            ->get();
        $gmsTargetRoas = \App\Models\MarketplaceAdCampaign::query()
            ->where('store_id', $store->id)
            ->where('channel_campaign_id', 'GMS-' . $store->id)
            ->value('target_roas');

        $grouped = $rows->groupBy(fn ($row) => (string) $row->channel_item_id);
        $itemIds = $grouped->keys()->filter()->values();

        $previousRows = \App\Models\MarketplaceAdsItemDaily::query()
            ->where('store_id', $store->id)
            ->where('channel_campaign_id', 'GMS-' . $store->id)
            ->whereBetween('date', [$previousFromDate, $previousToDate])
            ->orderBy('date')
            ->get();
        $previousGrouped = $previousRows->groupBy(fn ($row) => (string) $row->channel_item_id);

        $profitForMetrics = static function (float $spend, float $netRevenue, ?float $hppTotal, bool $hasSales): ?float {
            return !$hasSales
                ? round(-($spend * 1.11), 2)
                : ($hppTotal !== null ? round($netRevenue - $hppTotal - ($spend * 1.11), 2) : null);
        };

        $products = \App\Models\MarketplaceProduct::query()
            ->where('store_id', $store->id)
            ->whereIn('item_id', $itemIds->all())
            ->with('models:id,marketplace_product_id,model_sku,model_name,price,raw_json')
            ->get()
            ->keyBy(fn ($product) => (string) $product->item_id);

        $productSkus = $products->flatMap(fn ($product) => $product->models->pluck('model_sku')->push($product->item_sku))
            ->filter()->map(fn ($sku) => trim((string) $sku))->unique()->values();

        $skuMappings = \App\Models\SkuMapping::query()
            ->with('item:id,code,name,hpp,base_unit_cost')
            ->whereIn('marketplace_sku', $productSkus->all())
            ->where(function ($query) {
                $query->whereNull('channel_code')->orWhereRaw('LOWER(channel_code) = ?', ['shopee']);
            })
            ->get()
            ->groupBy(fn ($mapping) => mb_strtolower(trim((string) $mapping->marketplace_sku)));

        $manualMaps = \App\Models\MarketplaceAdItemMap::query()
            ->with('item:id,code,name,hpp,base_unit_cost')
            ->where('store_id', $store->id)
            ->where('channel_code', 'shopee')
            ->whereIn('channel_item_id', $itemIds->map(fn ($id) => (int) $id)->all())
            ->get()
            ->keyBy(fn ($mapping) => (string) $mapping->channel_item_id);

        $items = $grouped->map(function ($dailyRows, string $channelItemId) use ($products, $skuMappings, $manualMaps, $hppResolver, $previousGrouped, $profitForMetrics, $gmsTargetRoas, $dashboardService, $store) {
            $product = $products->get($channelItemId);
            $manual = $manualMaps->get($channelItemId);
            $modelMappings = $product
                ? $product->models->flatMap(fn ($model) => $skuMappings->get(mb_strtolower(trim((string) $model->model_sku)), collect()))
                    ->filter(fn ($mapping) => $mapping->item)
                : collect();
            $productMappings = $modelMappings;
            if ($productMappings->isEmpty() && $product?->item_sku) {
                $productMappings = $skuMappings
                    ->get(mb_strtolower(trim((string) $product->item_sku)), collect())
                    ->filter(fn ($mapping) => $mapping->item);
            }
            $mappedItem = $manual?->item ?? $productMappings->first()?->item;
            $manualHpp = $manual?->item ? $hppResolver->resolve($manual->item) : 0.0;
            $hppCandidates = $productMappings->map(fn ($mapping) => $hppResolver->resolve($mapping->item))->filter(fn ($hpp) => $hpp > 0);
            $unitCogs = $manual?->item ? $manualHpp : ($hppCandidates->isNotEmpty() ? (float) $hppCandidates->avg() : 0.0);

            $impressions = (int) $dailyRows->sum('impressions');
            $clicks = (int) $dailyRows->sum('clicks');
            $spend = (float) $dailyRows->sum('expense');
            $orders = (int) $dailyRows->sum('broad_order');
            $gmv = (float) $dailyRows->sum('broad_gmv');
            $directOrders = (int) $dailyRows->sum('direct_order');
            $directGmv = (float) $dailyRows->sum('direct_gmv');
            $apiPcs = (float) $dailyRows->sum(fn ($row) => (float) data_get($row->raw_json, 'broad_order_amount', 0));
            $pcs = $apiPcs > 0 ? $apiPcs : (float) $orders;
            try {
                [$netRevenueRatio] = $dashboardService->resolveConfiguredNetRevenueRatio($channelItemId, $store->id);
            } catch (\Throwable $e) {
                // Satu item dengan data settlement rusak tidak boleh membuat
                // seluruh tab Produk Belum Mapping gagal. Gunakan rasio default
                // dan biarkan endpoint tetap mengembalikan daftar item.
                report($e);
                $netRevenueRatio = AdsDashboardService::DEFAULT_NET_REVENUE_RATIO;
            }
            $netRevenue = $gmv * $netRevenueRatio;
            $hasSales = $gmv > 0 || $orders > 0 || $pcs > 0;
            $hppTotal = !$hasSales ? 0.0 : ($unitCogs > 0 && $pcs > 0 ? $unitCogs * $pcs : null);
            $profit = $profitForMetrics($spend, $netRevenue, $hppTotal, $hasSales);

            $previousDailyRows = $previousGrouped->get($channelItemId, collect());
            $previousSpend = (float) $previousDailyRows->sum('expense');
            $previousOrders = (int) $previousDailyRows->sum('broad_order');
            $previousGmv = (float) $previousDailyRows->sum('broad_gmv');
            $previousApiPcs = (float) $previousDailyRows->sum(fn ($row) => (float) data_get($row->raw_json, 'broad_order_amount', 0));
            $previousPcs = $previousApiPcs > 0 ? $previousApiPcs : (float) $previousOrders;
            $previousNetRevenue = $previousGmv * $netRevenueRatio;
            $previousHasSales = $previousGmv > 0 || $previousOrders > 0 || $previousPcs > 0;
            $previousHppTotal = !$previousHasSales ? 0.0 : ($unitCogs > 0 && $previousPcs > 0 ? $unitCogs * $previousPcs : null);
            $previousProfit = $profitForMetrics($previousSpend, $previousNetRevenue, $previousHppTotal, $previousHasSales);

            return [
                'channel_item_id' => $channelItemId,
                'item_name' => $product?->item_name ?: ($mappedItem?->name ?: 'Produk GMV Max'),
                'item_sku' => $product?->item_sku,
                'target_roas' => $gmsTargetRoas !== null ? round((float) $gmsTargetRoas, 2) : null,
                'image_url' => $product?->image_url,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'spend' => round($spend, 2),
                'orders' => $orders,
                'pcs' => round($pcs, 2),
                'pcs_source' => $apiPcs > 0 ? 'api' : 'order_fallback',
                'gmv' => round($gmv, 2),
                'direct_orders' => $directOrders,
                'direct_gmv' => round($directGmv, 2),
                'roas' => $spend > 0 ? round($gmv / $spend, 4) : null,
                'unit_cogs' => round($unitCogs, 2),
                'hpp_total' => $hppTotal !== null ? round($hppTotal, 2) : null,
                'net_revenue' => round($netRevenue, 2),
                'net_revenue_ratio' => round($netRevenueRatio, 6),
                'profit_after_ads' => $profit,
                'previous_spend' => round($previousSpend, 2),
                'previous_orders' => $previousOrders,
                'previous_pcs' => round($previousPcs, 2),
                'previous_gmv' => round($previousGmv, 2),
                'previous_hpp_total' => $previousHppTotal !== null ? round($previousHppTotal, 2) : null,
                'previous_profit_after_ads' => $previousProfit,
                'mapped' => $unitCogs > 0,
                'mapping_source' => $manual?->item ? 'manual_item' : ($productMappings->isNotEmpty() ? 'sku_mapping' : null),
                'internal_item_id' => $mappedItem?->id,
                'internal_item_code' => $mappedItem?->code,
                'internal_item_name' => $mappedItem?->name,
            ];
        })->sortByDesc('spend')->values();

        // Detail item kadang berbeda 1–2 rupiah dari campaign parent karena
        // pembulatan API. Gunakan total parent sebagai sumber biaya iklan agar
        // tabel GMV Max Auto selalu sama dengan KPI dashboard.
        $parentSpend = (float) DB::table('marketplace_ad_campaign_dailies')
            ->where('store_id', $store->id)
            ->where('channel_campaign_id', 'GMS-' . $store->id)
            ->whereBetween('date', [$fromDate, $toDate])
            ->sum('expense');
        $previousParentSpend = (float) DB::table('marketplace_ad_campaign_dailies')
            ->where('store_id', $store->id)
            ->where('channel_campaign_id', 'GMS-' . $store->id)
            ->whereBetween('date', [$previousFromDate, $previousToDate])
            ->sum('expense');
        $itemSpend = (float) $items->sum('spend');
        $previousItemSpend = (float) $items->sum('previous_spend');
        if ($parentSpend >= 0 && $itemSpend > 0 && abs($parentSpend - $itemSpend) > 0.001) {
            $scale = $parentSpend / $itemSpend;
            $items = $items->map(function (array $item) use ($scale) {
                $item['spend'] = round((float) $item['spend'] * $scale, 2);
                $hasSales = (float) ($item['gmv'] ?? 0) > 0 || (int) ($item['orders'] ?? 0) > 0 || (float) ($item['pcs'] ?? 0) > 0;
                $item['profit_after_ads'] = !$hasSales
                    ? round(-($item['spend'] * 1.11), 2)
                    : ($item['hpp_total'] !== null
                        ? round((float) $item['net_revenue'] - (float) $item['hpp_total'] - ($item['spend'] * 1.11), 2)
                        : null);
                $item['roas'] = $item['spend'] > 0 ? round((float) $item['gmv'] / $item['spend'], 4) : null;
                return $item;
            })->sortByDesc('spend')->values();
        }
        if ($previousParentSpend >= 0 && $previousItemSpend > 0 && abs($previousParentSpend - $previousItemSpend) > 0.001) {
            $previousScale = $previousParentSpend / $previousItemSpend;
            $items = $items->map(function (array $item) use ($previousScale, $profitForMetrics) {
                $item['previous_spend'] = round((float) $item['previous_spend'] * $previousScale, 2);
                $previousHasSales = (float) ($item['previous_gmv'] ?? 0) > 0
                    || (int) ($item['previous_orders'] ?? 0) > 0
                    || (float) ($item['previous_pcs'] ?? 0) > 0;
                $item['previous_profit_after_ads'] = $profitForMetrics(
                    (float) $item['previous_spend'],
                    (float) ($item['previous_gmv'] ?? 0) * (float) ($item['net_revenue_ratio'] ?? \App\Services\Marketplace\Ads\AdsDashboardService::DEFAULT_NET_REVENUE_RATIO),
                    $item['previous_hpp_total'] !== null ? (float) $item['previous_hpp_total'] : null,
                    $previousHasSales
                );
                return $item;
            })->sortByDesc('spend')->values();
        }

        return response()->json([
            'status' => 'success',
            'store_id' => $store->id,
            'campaign_id' => 'GMS-' . $store->id,
            'date_from' => $fromDate,
            'date_to' => $toDate,
            'compare_mode' => $compareMode,
            'previous_date_from' => $previousFromDate,
            'previous_date_to' => $previousToDate,
            'total_items' => $items->count(),
            'mapped_items' => $items->where('mapped', true)->count(),
            'unmapped_items' => $items->where('mapped', false)->count(),
            'data' => $items->values(),
        ]);
    }

    /** Simpan mapping item marketplace tertentu di dalam GMV Max. */
    public function mapGmsItem(Request $request, Store $store, string $channelItemId): JsonResponse
    {
        $data = $request->validate([
            'internal_item_id' => ['required', 'integer', 'exists:items,id'],
        ]);

        if (! ctype_digit($channelItemId)) {
            return response()->json(['message' => 'Item GMV Max tidak valid.'], 422);
        }

        $mapping = \App\Models\MarketplaceAdItemMap::updateOrCreate(
            [
                'store_id' => $store->id,
                'channel_code' => 'shopee',
                'channel_item_id' => (int) $channelItemId,
            ],
            [
                'channel_campaign_id' => 'GMS-' . $store->id,
                'internal_item_id' => $data['internal_item_id'],
                'created_by' => $request->user()?->id,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Item GMV Max berhasil di-mapping.',
            'mapping' => $mapping->load('item:id,code,name,hpp,base_unit_cost'),
        ]);
    }

    // Example transformation block for Campaign objects (assuming inside a controller method)
    // $campaigns->with('internalItem')->get()->map(function ($camp) {
    //     $acos = $camp->sum_gmv > 0 && $camp->sum_expense > 0 ? round($camp->sum_expense / $camp->sum_gmv, 4) : null;
    //     $camp->acos_pct = $acos !== null ? round($acos * 100, 1) : null;
    //     $beAcos = $camp->break_even_acos !== null ? (float) $camp->break_even_acos : $this->deriveBreakEvenAcos($camp->internalItem, $camp->sum_gmv, $camp->sum_orders);
    //     $camp->break_even_acos_pct = $beAcos !== null ? round($beAcos * 100, 1) : null;
    //     $camp->profit_after_ads = ($beAcos !== null) ? round($camp->sum_gmv * $beAcos - $camp->sum_expense, 2) : null;
    //     $camp->reco = $this->adsRecommendation((float)$camp->sum_expense, $acos, $beAcos, (int)$camp->sum_orders);
    //     return $camp;
    // });
    
    // ------------------------------------------------------------------------
    // Helper Methods untuk ACOS & Rekomendasi
    // ------------------------------------------------------------------------
    
    /*
    | Default rasio cair saat item belum punya data settlement.
    | Diambil dari struktur biaya order riil Shopee saat ini:
    | subtotal 99.950 → cair 78.051 = potongan ±21,9%
    | (komisi + layanan + proses pesanan + gratis ongkir xtra + voucher seller).
    */
    private const DEFAULT_NET_REVENUE_RATIO = 0.781;

    private function deriveNetRevenueRatio(?string $channelItemId, ?int $storeId = null): float
    {
        return $this->deriveNetRevenueRatioWithSource($channelItemId, $storeId)[0];
    }

    /**
     * Rasio dana cair terhadap nilai barang (harga jual), plus asal datanya.
     *
     * Basis rasio = buyer_payment_amount settlement. Di sync Shopee kolom ini
     * dipetakan dari cost_of_goods_sold / order_selling_price — NILAI BARANG
     * harga jual (basis yang sama dengan GMV iklan), bukan total transfer buyer.
     *
     * JANGAN pakai marketplace_orders.subtotal_items sebagai penyebut:
     * kolom itu diisi model_original_price (harga coret SEBELUM diskon),
     * jadi rasio ketarik ke bawah dan potongan platform tampak jauh
     * lebih besar dari kenyataan.
     *
     * Urutan: settlement item itu sendiri → DEFAULT_NET_REVENUE_RATIO.
     * (Rata-rata toko sengaja TIDAK dipakai sebagai fallback — campuran item
     * lama bertarif rendah membuat estimasinya terlalu optimis.)
     * Settlement final_income <= 0 (batal / refund penuh) selalu dikecualikan.
     *
     * @return array{0: float, 1: string} [ratio, 'item'|'default']
     */
    private function deriveNetRevenueRatioWithSource(?string $channelItemId, ?int $storeId = null): array
    {
        if ($channelItemId) {
            return \Illuminate\Support\Facades\Cache::remember(
                'ads-nrr:' . $channelItemId,
                1800,
                fn () => $this->computeNetRevenueRatio($channelItemId)
            );
        }

        return [self::DEFAULT_NET_REVENUE_RATIO, 'default'];
    }

    private function computeNetRevenueRatio(string $channelItemId): array
    {
        if ($channelItemId) {
            // SATSET: subquery, bukan pluck — id order tidak perlu ditarik ke PHP
            // (bisa ribuan) lalu dikirim balik sebagai whereIn raksasa.
            $settlements = \App\Models\MarketplaceOrderSettlement::whereIn(
                    'order_id',
                    \App\Models\MarketplaceOrderItem::where('external_item_id', $channelItemId)
                        ->whereNotNull('order_id')
                        ->select('order_id')
                )
                ->where('final_income', '>', 0)
                ->get(['final_income', 'buyer_payment_amount']);

            $totalFinalIncome = (float) $settlements->sum('final_income');
            $totalItemValue   = (float) $settlements->sum('buyer_payment_amount');

            if ($totalItemValue > 0) {
                // Cair tidak mungkin melebihi nilai barang untuk keperluan estimasi
                return [round(min(1, $totalFinalIncome / $totalItemValue), 4), 'item'];
            }
        }

        return [self::DEFAULT_NET_REVENUE_RATIO, 'default']; // potongan ±21,9%
    }

    /**
     * SATSET: fetch product-models sekali per (item|store) — memo per-request
     * + cache 30 menit. Sebelumnya tiap kampanye menembak 6-8 query derivasi
     * sendiri-sendiri (ratusan query per load filter).
     */
    private array $productModelsMemo = [];

    private function productModelsFor(?string $channelItemId, ?int $storeId)
    {
        $key = ($channelItemId ?? '-') . '|' . ($storeId ?? '-');
        if (! array_key_exists($key, $this->productModelsMemo)) {
            $this->productModelsMemo[$key] = \Illuminate\Support\Facades\Cache::remember(
                'ads-pmodels:' . $key,
                1800,
                function () use ($channelItemId, $storeId) {
                    if ($channelItemId) {
                        return \App\Models\MarketplaceProductModel::whereHas('product', fn ($q) => $q->where('item_id', $channelItemId))->get();
                    }
                    if ($storeId) {
                        return \App\Models\MarketplaceProductModel::whereHas('product', fn ($q) => $q->where('store_id', $storeId))->get();
                    }
                    return collect();
                }
            );
        }
        return $this->productModelsMemo[$key];
    }

    private function deriveAveragePrice(?string $channelItemId, ?int $storeId = null): ?float
    {
        $models = $this->productModelsFor($channelItemId, $storeId);
        if ($models->isEmpty()) return null;
        
        $totalPrice = 0;
        $count = 0;
        foreach($models as $model) {
            $raw = is_string($model->raw_json) ? json_decode($model->raw_json, true) : $model->raw_json;
            $raw = $raw ?? [];
            if (isset($raw['price_info'][0]['current_price'])) {
                $totalPrice += (float)$raw['price_info'][0]['current_price'];
                $count++;
            } else if ($model->price > 0) {
                $totalPrice += (float)$model->price;
                $count++;
            }
        }
        return $count > 0 ? $totalPrice / $count : null;
    }

    /** Memo hasil rata-rata HPP per (item|channel|store) — sekali hitung per request. */
    private array $avgHppMemo = [];

    private function deriveAverageHpp(?string $channelItemId, ?string $channelCode = null, ?int $storeId = null): ?float
    {
        $memoKey = ($channelItemId ?? '-') . '|' . ($channelCode ?? '-') . '|' . ($storeId ?? '-');
        if (array_key_exists($memoKey, $this->avgHppMemo)) {
            return $this->avgHppMemo[$memoKey];
        }

        $models = $this->productModelsFor($channelItemId, $storeId);
        if ($models->isEmpty()) return $this->avgHppMemo[$memoKey] = null;

        // SATSET: dulu SETIAP model menembak 1 query SkuMapping::first() + 1 lazy
        // load ->item — dikali jumlah kampanye = ratusan/ribuan query per load.
        // Sekarang: 1 query whereIn + eager load item untuk semua model sekaligus.
        $skus = $models->pluck('model_sku')->filter()->unique()->values();
        if ($skus->isEmpty()) return $this->avgHppMemo[$memoKey] = null;

        $mappingQuery = \App\Models\SkuMapping::with('item')->whereIn('marketplace_sku', $skus);
        if ($channelCode) {
            $mappingQuery->where('channel_code', $channelCode);
        }
        $mappings = $mappingQuery->get()->keyBy('marketplace_sku');

        $totalHpp = 0;
        $count = 0;

        foreach ($models as $model) {
            if ($model->model_sku) {
                $mapping = $mappings->get($model->model_sku);

                if ($mapping && $mapping->item) {
                    $hpp = (float) ($mapping->item->hpp ?? $mapping->item->base_unit_cost ?? 0);
                    if ($hpp > 0) {
                        $totalHpp += $hpp;
                        $count++;
                    }
                }
            }
        }

        return $this->avgHppMemo[$memoKey] = ($count > 0 ? $totalHpp / $count : null);
    }

}
