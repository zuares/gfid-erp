<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdsDaily;
use App\Models\MarketplaceAdsHourlyPerformance;
use App\Models\MarketplaceAdsSyncRun;
use App\Models\Store;
use App\Services\Marketplace\Ads\AdsAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Services\Marketplace\Ads\ShopeeAdsApiService;

class AdsDashboardController extends Controller
{
    public function index(Request $request, AdsAnalyticsService $analytics)
    {
        // 1. Dapatkan daftar store milik user (atau semua jika role mencukupi)
        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')
            ->where('is_active', true)
            ->get();
            
        $storeId = $request->input('store_id', $stores->first()?->id);
        
        $dateFrom = $request->input('date_from', now()->subDays(6)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $compareMode = $request->input('compare_mode', 'prev_period');

        if (!$storeId) {
            return view('marketplace.ads_dashboard', compact('stores', 'storeId', 'dateFrom', 'dateTo', 'compareMode'))
                ->with('error', 'Tidak ada toko Shopee aktif.');
        }

        // Validate ownership
        // In real app, make sure user can access this storeId
        
        // Fetch KPIs
        $kpi = $analytics->getKpiSummary($storeId, $dateFrom, $dateTo);
        
        // Fetch Daily Chart Data
        $dailyChartData = MarketplaceAdsDaily::where('store_id', $storeId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get();
            
        $dStart = \Carbon\Carbon::parse($dateFrom);
        $dEnd = \Carbon\Carbon::parse($dateTo);
        
        if ($compareMode === 'prev_month') {
            $prevDateFrom = $dStart->copy()->subMonth()->toDateString();
            $prevDateTo = $dEnd->copy()->subMonth()->toDateString();
        } elseif ($compareMode === 'prev_year') {
            $prevDateFrom = $dStart->copy()->subYear()->toDateString();
            $prevDateTo = $dEnd->copy()->subYear()->toDateString();
        } else {
            $diffDays = $dStart->diffInDays($dEnd);
            $prevDateFrom = $dStart->copy()->subDays($diffDays + 1)->toDateString();
            $prevDateTo = $dStart->copy()->subDay()->toDateString();
        }
        
        // Fetch Campaigns — hanya ongoing & paused agar tab Kampanye tidak menampilkan yang sudah mati
        $campaigns = MarketplaceAdCampaign::with('internalItem')->where('store_id', $storeId)
            ->withSum(['dailies as sum_expense' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'expense')
            ->withSum(['dailies as sum_broad_gmv' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'broad_gmv')
            ->withSum(['dailies as sum_direct_gmv' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'direct_gmv')
            ->withSum(['dailies as sum_clicks' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'clicks')
            ->withSum(['dailies as sum_impressions' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'impressions')
            ->withSum(['dailies as sum_broad_orders' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'broad_order')
            ->withSum(['dailies as sum_broad_order_amount' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'broad_order_amount')
            ->withSum(['dailies as sum_direct_orders' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'direct_order')
            ->withSum(['dailies as sum_direct_order_amount' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'direct_order_amount')
            ->withSum(['dailies as sum_prev_expense' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'expense')
            ->withSum(['dailies as sum_prev_broad_gmv' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'broad_gmv')
            ->withSum(['dailies as sum_prev_direct_gmv' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'direct_gmv')
            ->withSum(['dailies as sum_prev_clicks' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'clicks')
            ->withSum(['dailies as sum_prev_impressions' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'impressions')
            ->withSum(['dailies as sum_prev_broad_orders' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'broad_order')
            ->withSum(['dailies as sum_prev_direct_orders' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'direct_order')
            ->get()
            ->map(function ($camp) {
                \Log::info('Camp Attributes: ', $camp->getAttributes());
                // Shopee API: broad_gmv dan broad_orders merupakan total keseluruhan (termasuk direct)
                $camp->sum_gmv = $camp->sum_broad_gmv ?? 0;
                $camp->sum_orders = $camp->sum_broad_orders ?? 0;
                $camp->sum_prev_gmv = $camp->sum_prev_broad_gmv ?? 0;
                $camp->sum_prev_orders = $camp->sum_prev_broad_orders ?? 0;
                
                $roas = $camp->sum_expense > 0 ? $camp->sum_gmv / $camp->sum_expense : 0;
                $prevRoas = $camp->sum_prev_expense > 0 ? $camp->sum_prev_gmv / $camp->sum_prev_expense : 0;
                
                $camp->roas = $roas;
                $camp->prev_roas = $prevRoas;
                
                $camp->roas_growth = $prevRoas > 0 ? (($roas - $prevRoas) / $prevRoas) * 100 : 0;
                $camp->spend_growth = $camp->sum_prev_expense > 0 ? (($camp->sum_expense - $camp->sum_prev_expense) / $camp->sum_prev_expense) * 100 : 0;
                $camp->gmv_growth = $camp->sum_prev_gmv > 0 ? (($camp->sum_gmv - $camp->sum_prev_gmv) / $camp->sum_prev_gmv) * 100 : 0;

                $camp->spend = $camp->sum_expense ?? 0;
                $camp->gmv = $camp->sum_gmv ?? 0;
                $camp->clicks = $camp->sum_clicks ?? 0;
                $camp->orders = $camp->sum_orders ?? 0;
                $camp->items_sold = $camp->sum_broad_order_amount ?? 0;
                $camp->prev_spend = $camp->sum_prev_expense ?? 0;
                $camp->prev_gmv = $camp->sum_prev_gmv ?? 0;
                $camp->prev_clicks = $camp->sum_prev_clicks ?? 0;
                $camp->prev_orders = $camp->sum_prev_orders ?? 0;
                
                // Hitung ACOS
                $acos = $camp->gmv > 0 && $camp->spend > 0 ? round($camp->spend / $camp->gmv, 4) : null;
                $camp->acos_pct = $acos !== null ? round($acos * 100, 1) : null;
                
                $camp->unit_cogs = $this->deriveAverageHpp($camp->channel_item_id, $camp->store?->channel?->code, $camp->store_id) 
                                 ?? (float) ($camp->internalItem->hpp ?? $camp->internalItem->base_unit_cost ?? 0);
                
                $trueAvgPrice = $this->deriveAveragePrice($camp->channel_item_id, $camp->store_id);
                if (!$trueAvgPrice || $trueAvgPrice <= 0) {
                    $trueAvgPrice = ($camp->orders > 0 && $camp->gmv > 0) ? ($camp->gmv / $camp->orders) : 0;
                }
                $camp->cogs_ratio = $trueAvgPrice > 0 ? ($camp->unit_cogs / $trueAvgPrice) : 0;
                                 
                $camp->net_revenue_ratio = $this->deriveNetRevenueRatio($camp->channel_item_id);
                                 
                // Break-even ACOS
                $netRevRatio = $camp->net_revenue_ratio ?? 0.89;
                $avgBeAcos = $this->deriveAverageBreakEvenAcos($camp->channel_item_id, (float)$camp->gmv, (int)$camp->orders, $netRevRatio, $camp->store?->channel?->code, $camp->store_id);
                $beAcos = $camp->break_even_acos !== null 
                    ? (float) $camp->break_even_acos 
                    : ($avgBeAcos ?? $this->deriveBreakEvenAcos($camp->internalItem, (float)$camp->gmv, (int)$camp->orders, $netRevRatio));
                $camp->break_even_acos_pct = $beAcos !== null ? round($beAcos * 100, 1) : null;
                
                // Profit after ads
                if ($beAcos !== null) {
                    $netRevenue = $camp->gmv * $netRevRatio;
                    $totalCogs = $camp->gmv * ($camp->cogs_ratio ?? 0);
                    $spendAfterTax = $camp->spend * 1.11;
                    $camp->profit_after_ads = round($netRevenue - $totalCogs - $spendAfterTax, 2);
                } else {
                    $camp->profit_after_ads = null;
                }
                
                // Rekomendasi
                $camp->reco = $this->adsRecommendation((float)$camp->spend, $acos, $beAcos, (int)$camp->orders);
                
                return $camp;
            })
            // Tampilkan kampanye yang sedang berjalan ATAU yang memiliki spend
            ->filter(fn($camp) => in_array($camp->campaign_status, ['ongoing', 'normal']) || $camp->spend > 0 || $camp->prev_spend > 0)
            ->sortByDesc('spend')
            ->values();
            
        // Fetch Sync Runs
        $syncRuns = MarketplaceAdsSyncRun::where('store_id', $storeId)
            ->orderByDesc('id')
            ->limit(10)
            ->get();
            
        $lastSuccessRun = MarketplaceAdsSyncRun::where('store_id', $storeId)
            ->where('status', 'success')
            ->latest('id')
            ->first();

        $heatmapData = $analytics->getHourlyHeatmap($storeId, $dateFrom, $dateTo);
        $historicalData = $analytics->getHistoricalComparison($storeId, $dateFrom, $dateTo, 3);

        // Fetch All Product Performance (CPC + GMS gabungan)
        // Sumber: campaign dailies di-join ke campaigns (untuk channel_item_id) lalu ke marketplace_products
        $itemPerformance = \Illuminate\Support\Facades\DB::table('marketplace_ad_campaign_dailies as cd')
            ->join('marketplace_ad_campaigns as c', function ($join) {
                $join->on('cd.channel_campaign_id', '=', 'c.channel_campaign_id')
                     ->on('cd.store_id', '=', 'c.store_id');
            })
            ->leftJoin('marketplace_products as p', function ($join) {
                $join->on('c.channel_item_id', '=', 'p.item_id')
                     ->on('c.store_id', '=', 'p.store_id');
            })
            ->where('cd.store_id', $storeId)
            ->whereNotNull('c.channel_item_id') // Exclude aggregate GMS campaigns tanpa produk spesifik
            ->whereBetween('cd.date', [$dateFrom, $dateTo])
            ->selectRaw('
                c.channel_item_id,
                c.channel_campaign_id,
                MAX(c.campaign_name) as campaign_name,
                MAX(c.campaign_status) as campaign_status,
                MAX(c.ad_type) as ad_type,
                MAX(p.item_sku) as item_sku,
                MAX(p.item_name) as item_name,
                SUM(cd.impressions) as impressions,
                SUM(cd.clicks) as clicks,
                SUM(cd.expense) as spend,
                SUM(cd.broad_gmv) as gmv,
                SUM(cd.broad_order) as orders,
                SUM(cd.direct_gmv) as direct_gmv_sum
            ')
            ->groupBy('c.channel_item_id', 'c.channel_campaign_id')
            ->orderByDesc('spend')
            ->limit(50)
            ->get();

        $gmsItems = \Illuminate\Support\Facades\DB::table('marketplace_ads_item_dailies as id')
            ->leftJoin('marketplace_products as p', function ($join) {
                $join->on('id.channel_item_id', '=', 'p.item_id')
                     ->on('id.store_id', '=', 'p.store_id');
            })
            ->leftJoin('marketplace_ad_campaigns as camp', function ($join) {
                $join->on('id.channel_campaign_id', '=', 'camp.channel_campaign_id')
                     ->on('id.store_id', '=', 'camp.store_id');
            })
            ->where('id.store_id', $storeId)
            ->where(function ($q) use ($dateFrom, $dateTo, $prevDateFrom, $prevDateTo) {
                $q->whereBetween('id.date', [$dateFrom, $dateTo])
                  ->orWhereBetween('id.date', [$prevDateFrom, $prevDateTo]);
            })
            ->selectRaw("
                id.channel_item_id,
                MAX(id.channel_campaign_id) as channel_campaign_id,
                MAX(p.item_sku) as item_sku,
                MAX(p.item_name) as item_name,
                MAX(camp.target_roas) as target_roas,
                MAX(camp.campaign_budget) as campaign_budget,
                MAX(camp.campaign_status) as campaign_status,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.impressions ELSE 0 END) as impression,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.clicks ELSE 0 END) as click,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.expense ELSE 0 END) as expense,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.broad_order ELSE 0 END) as broad_order,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.broad_gmv ELSE 0 END) as broad_gmv,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.direct_order ELSE 0 END) as direct_order,
                SUM(CASE WHEN id.date >= '{$dateFrom}' AND id.date <= '{$dateTo}' THEN id.direct_gmv ELSE 0 END) as direct_gmv,
                
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.impressions ELSE 0 END) as prev_impression,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.clicks ELSE 0 END) as prev_click,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.expense ELSE 0 END) as prev_expense,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.broad_order ELSE 0 END) as prev_broad_order,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.broad_gmv ELSE 0 END) as prev_broad_gmv,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.direct_order ELSE 0 END) as prev_direct_order,
                SUM(CASE WHEN id.date >= '{$prevDateFrom}' AND id.date <= '{$prevDateTo}' THEN id.direct_gmv ELSE 0 END) as prev_direct_gmv
            ")
            ->groupBy('id.channel_item_id')
            ->orderByDesc('expense')
            ->get()
            ->map(function ($item) {
                // Return original structure but with growth added
                return $item;
            });

        $adsSetting = \App\Models\MarketplaceAdsSetting::where('store_id', $storeId)->first();
        $autoCampaign = \App\Models\MarketplaceAdCampaign::where('store_id', $storeId)->where('ad_type', 'auto')->first();

        return view('marketplace.ads_dashboard', compact(
            'stores', 'storeId', 'dateFrom', 'dateTo', 'compareMode', 'kpi', 'dailyChartData', 'campaigns', 'syncRuns', 'heatmapData', 'historicalData', 'itemPerformance', 'lastSuccessRun', 'adsSetting', 'gmsItems', 'autoCampaign'
        ));
    }

    public function sync(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'sync_type' => 'required|in:incremental,hourly,backfill',
        ]);
        
        $storeId = $request->input('store_id');
        $type = $request->input('sync_type');
        
        // Trigger Artisan command in background or synchronously based on type
        // In real app, use queued jobs. We dispatch the job here or run command.
        $cmd = 'marketplace:sync-ads';
        $params = ['--store' => $storeId];
        
        if ($type === 'hourly') {
            $params['--hourly'] = true;
        } elseif ($type === 'backfill') {
            if (!auth()->user()->hasRole(['owner', 'admin'])) {
                return back()->with('error', 'Hanya Admin/Owner yang dapat menjalankan backfill.');
            }
            $params['--backfill'] = true;
            $params['--from'] = now()->subMonths(6)->toDateString();
            $params['--to'] = now()->toDateString();
        }
        
        // We use call instead of queue for Live Mode to ensure data is updated immediately
        Artisan::call($cmd, $params);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => 'Data berhasil disinkronisasi.']);
        }

        return back()->with('success', 'Data berhasil disinkronisasi.');
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
            'mp_ads_rows',
            'store_ad_spend_dailies'
        ];

        foreach ($tables as $table) {
            \Illuminate\Support\Facades\DB::table($table)->truncate();
        }

        return response()->json(['status' => 'success', 'message' => 'Semua data iklan berhasil dihapus.']);
    }

    public function realtimeStatus(Request $request, ShopeeAdsApiService $adsApi)
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
            $balance = $adsApi->getAdsTotalBalance($store);
            $toggleInfo = $adsApi->getAdsShopToggleInfo($store);
            $facilRate = $adsApi->getAdsFacilShopRate($store);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'balance' => $balance['response'] ?? [],
                    'toggle_info' => $toggleInfo['response'] ?? [],
                    'facil_rate' => $facilRate['response'] ?? [],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function actionGmsItem(Request $request, \App\Services\Channels\Shopee\ShopeeChannel $shopeeChannel)
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
            $res = $shopeeChannel->editGmsItemProductCampaign(
                $store,
                $request->input('action'),
                [$request->input('item_id')]
            );

            if (!empty($res['error'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => $res['message'] ?? $res['error']
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil ' . ($request->input('action') === 'add' ? 'menambahkan' : 'mengeluarkan') . ' produk dari GMV Max.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function actionCpcCampaign(Request $request, \App\Services\Channels\Shopee\ShopeeChannel $shopeeChannel)
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
        
        $results = [];

        try {
            // Ubah ROAS
            if ($roasTarget !== null && $roasTarget !== '') {
                $params = ['roas_target' => (float)$roasTarget];
                $resRoas = $shopeeChannel->editManualProductAds($store, $campaignId, 'change_roas_target', $params);
                if (!empty($resRoas['error'])) {
                    return response()->json(['status' => 'error', 'message' => 'Gagal ubah ROAS: ' . ($resRoas['message'] ?? $resRoas['error'])], 400);
                }
                $results[] = 'Target ROAS (' . ($roasTarget == 0 ? 'Auto' : $roasTarget) . ')';
                
                // Jeda sebentar agar tidak melanggar rate limit Shopee
                usleep(300000); 
            }

            // Ubah Budget
            if ($dailyBudget !== null && $dailyBudget !== '') {
                $params = ['budget' => (float)$dailyBudget];
                $resBudget = $shopeeChannel->editManualProductAds($store, $campaignId, 'change_budget', $params);
                if (!empty($resBudget['error'])) {
                    return response()->json(['status' => 'error', 'message' => 'Gagal ubah Budget: ' . ($resBudget['message'] ?? $resBudget['error'])], 400);
                }
                $results[] = 'Batas Modal Harian (' . ($dailyBudget == 0 ? 'Tidak Terbatas' : number_format($dailyBudget, 0, ',', '.')) . ')';
            }

            // Ubah Status (Pause / Resume)
            if ($statusAction === 'pause' || $statusAction === 'resume') {
                $resStatus = $shopeeChannel->editManualProductAds($store, $campaignId, $statusAction, []);
                if (!empty($resStatus['error'])) {
                    return response()->json(['status' => 'error', 'message' => 'Gagal ubah status: ' . ($resStatus['message'] ?? $resStatus['error'])], 400);
                }
                $results[] = 'Status (' . ucfirst($statusAction) . ')';
                usleep(300000);
            }

            if (empty($results)) {
                return response()->json(['status' => 'error', 'message' => 'Tidak ada pengaturan yang diubah.'], 400);
            }
            
            // Update local DB
            $localCampaign = \App\Models\MarketplaceAdCampaign::where('store_id', $store->id)
                ->where('channel_campaign_id', $campaignId)->first();
            if ($localCampaign) {
                if ($roasTarget !== null && $roasTarget !== '') {
                    $localCampaign->target_roas = (float)$roasTarget;
                }
                if ($dailyBudget !== null && $dailyBudget !== '') {
                    $localCampaign->campaign_budget = (float)$dailyBudget;
                }
                if ($statusAction === 'pause') {
                    $localCampaign->campaign_status = 'paused';
                } elseif ($statusAction === 'resume') {
                    $localCampaign->campaign_status = 'ongoing';
                }
                $localCampaign->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pengaturan Kampanye CPC berhasil disimpan: ' . implode(' dan ', $results)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    public function actionGmsCampaign(Request $request, \App\Services\Channels\Shopee\ShopeeChannel $shopeeChannel)
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
        
        $results = [];

        try {
            // Ubah ROAS
            if ($roasTarget !== null && $roasTarget !== '') {
                $params = ['roas_target' => (float)$roasTarget];
                if ($campaignId) $params['campaign_id'] = (int)$campaignId;
                
                $resRoas = $shopeeChannel->editGmsProductCampaign($store, 'change_roas_target', $params);
                if (!empty($resRoas['error'])) {
                    return response()->json(['status' => 'error', 'message' => 'Gagal ubah ROAS: ' . ($resRoas['message'] ?? $resRoas['error'])], 400);
                }
                $results[] = 'Target ROAS (' . ($roasTarget == 0 ? 'Auto' : $roasTarget) . ')';
            }

            // Ubah Budget
            if ($dailyBudget !== null && $dailyBudget !== '') {
                $params = ['daily_budget' => (float)$dailyBudget];
                if ($campaignId) $params['campaign_id'] = (int)$campaignId;
                
                $resBudget = $shopeeChannel->editGmsProductCampaign($store, 'change_budget', $params);
                if (!empty($resBudget['error'])) {
                    return response()->json(['status' => 'error', 'message' => 'Gagal ubah budget: ' . ($resBudget['message'] ?? $resBudget['error'])], 400);
                }
                $results[] = 'Batas Modal Harian';
            }

            if (empty($results)) {
                return response()->json(['status' => 'error', 'message' => 'Tidak ada data yang diubah.'], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil memperbarui ' . implode(' dan ', $results) . ' pada kampanye GMV Max.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function campaignHourly(Request $request, ShopeeAdsApiService $adsApi)
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
            $res = $adsApi->getCampaignHourlyPerformance($store, [$campaignId], $date);
            
            return response()->json([
                'status' => 'success',
                'data' => $res['response'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
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
    
    private function deriveNetRevenueRatio(?string $channelItemId): float
    {
        if (!$channelItemId) return 0.89; // Default 11% fee

        $orderIds = \App\Models\MarketplaceOrderItem::where('external_item_id', $channelItemId)->pluck('order_id');
        if ($orderIds->isEmpty()) return 0.89;

        $settlements = \App\Models\MarketplaceOrderSettlement::whereIn('order_id', $orderIds)->get(['final_income', 'buyer_payment_amount']);
        if ($settlements->isEmpty()) return 0.89;

        $totalFinalIncome = $settlements->sum('final_income');
        $totalBuyerPayment = $settlements->sum('buyer_payment_amount');

        if ($totalBuyerPayment <= 0) return 0.89;

        return round($totalFinalIncome / $totalBuyerPayment, 4);
    }

    private function deriveAveragePrice(?string $channelItemId, ?int $storeId = null): ?float
    {
        if ($channelItemId) {
            $models = \App\Models\MarketplaceProductModel::whereHas('product', function($q) use ($channelItemId) {
                $q->where('item_id', $channelItemId);
            })->get();
        } else if ($storeId) {
            $models = \App\Models\MarketplaceProductModel::whereHas('product', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })->get();
        } else {
            return null;
        }
        
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

    private function deriveAverageHpp(?string $channelItemId, ?string $channelCode = null, ?int $storeId = null): ?float
    {
        if ($channelItemId) {
            $models = \App\Models\MarketplaceProductModel::whereHas('product', function($q) use ($channelItemId) {
                $q->where('item_id', $channelItemId);
            })->get();
        } else if ($storeId) {
            $models = \App\Models\MarketplaceProductModel::whereHas('product', function($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })->get();
        } else {
            return null;
        }
        
        $totalHpp = 0;
        $count = 0;
        
        foreach ($models as $model) {
            if ($model->model_sku) {
                $query = \App\Models\SkuMapping::where('marketplace_sku', $model->model_sku);
                if ($channelCode) {
                    $query->where('channel_code', $channelCode);
                }
                $mapping = $query->first();
                
                if ($mapping && $mapping->item) {
                    $hpp = (float) ($mapping->item->hpp ?? $mapping->item->base_unit_cost ?? 0);
                    if ($hpp > 0) {
                        $totalHpp += $hpp;
                        $count++;
                    }
                }
            }
        }
        
        if ($count === 0) return null;
        return $totalHpp / $count;
    }

    private function deriveAverageBreakEvenAcos(?string $channelItemId, float $gmv, int $units, ?string $channelCode = null): ?float
    {
        if (! $channelItemId || $units <= 0 || $gmv <= 0) return null;
        
        $avgHpp = $this->deriveAverageHpp($channelItemId, $channelCode);
        if ($avgHpp === null) return null;
        
        $avgPrice = $gmv / $units;
        if ($avgHpp >= $avgPrice) return null;
        
        return round(($avgPrice - $avgHpp) / $avgPrice, 6);
    }
    
    private function deriveBreakEvenAcos(?\App\Models\Item $item, float $gmv, int $units): ?float
    {
        if (! $item || $units <= 0 || $gmv <= 0) return null;
        $hpp = (float) ($item->hpp ?? $item->base_unit_cost ?? 0);
        if ($hpp <= 0) return null;
        $avgPrice = $gmv / $units;
        if ($hpp >= $avgPrice) return null;
        return round(($avgPrice - $hpp) / $avgPrice, 6);
    }
    
    private function adsRecommendation(float $spend, ?float $acos, ?float $breakEvenAcos, int $orders): array
    {
        if ($spend === 0.0) {
            return ['label' => 'Tidak Aktif', 'color' => '#94a3b8', 'icon' => '⚪', 'class' => 'reco-nodata'];
        }
        if ($orders === 0) {
            return ['label' => 'Stop — 0 Konversi', 'color' => '#b91c1c', 'icon' => '🔴', 'class' => 'reco-stop'];
        }
        if ($breakEvenAcos === null) {
            return ['label' => 'Set Break-Even', 'color' => '#b45309', 'icon' => '⚠️', 'class' => 'reco-warn'];
        }
        if ($acos === null) {
            return ['label' => 'Data Tidak Lengkap', 'color' => '#94a3b8', 'icon' => '⚪', 'class' => 'reco-nodata'];
        }

        $ratio = $acos / $breakEvenAcos;

        if ($ratio <= 0.60) {
            return ['label' => 'Scale — Naikkan Budget', 'color' => '#16a34a', 'icon' => '🚀', 'class' => 'reco-scale'];
        }
        if ($ratio <= 0.85) {
            return ['label' => 'Pertahankan', 'color' => '#2563eb', 'icon' => '✅', 'class' => 'reco-ok'];
        }
        if ($ratio <= 1.00) {
            return ['label' => 'Perhatikan — Margin Tipis', 'color' => '#d97706', 'icon' => '⚡', 'class' => 'reco-warn'];
        }

        return ['label' => 'Stop / Kurangi Bid', 'color' => '#b91c1c', 'icon' => '🔴', 'class' => 'reco-stop'];
    }
}
