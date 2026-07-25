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
        
        // Fetch Campaigns (Dynamic aggregation from dailies based on date filter)
        $campaigns = MarketplaceAdCampaign::where('store_id', $storeId)
            ->withSum(['dailies as sum_expense' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'expense')
            ->withSum(['dailies as sum_broad_gmv' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'broad_gmv')
            ->withSum(['dailies as sum_direct_gmv' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'direct_gmv')
            ->withSum(['dailies as sum_clicks' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'clicks')
            ->withSum(['dailies as sum_broad_orders' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'broad_order')
            ->withSum(['dailies as sum_direct_orders' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'direct_order')
            ->withSum(['dailies as sum_prev_expense' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'expense')
            ->withSum(['dailies as sum_prev_broad_gmv' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'broad_gmv')
            ->withSum(['dailies as sum_prev_direct_gmv' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'direct_gmv')
            ->withSum(['dailies as sum_prev_clicks' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'clicks')
            ->withSum(['dailies as sum_prev_broad_orders' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'broad_order')
            ->withSum(['dailies as sum_prev_direct_orders' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'direct_order')
            ->get()
            ->map(function ($camp) {
                $camp->sum_gmv = ($camp->sum_broad_gmv ?? 0) + ($camp->sum_direct_gmv ?? 0);
                $camp->sum_orders = ($camp->sum_broad_orders ?? 0) + ($camp->sum_direct_orders ?? 0);
                $camp->sum_prev_gmv = ($camp->sum_prev_broad_gmv ?? 0) + ($camp->sum_prev_direct_gmv ?? 0);
                $camp->sum_prev_orders = ($camp->sum_prev_broad_orders ?? 0) + ($camp->sum_prev_direct_orders ?? 0);
                
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
                $camp->prev_spend = $camp->sum_prev_expense ?? 0;
                $camp->prev_gmv = $camp->sum_prev_gmv ?? 0;
                $camp->prev_clicks = $camp->sum_prev_clicks ?? 0;
                $camp->prev_orders = $camp->sum_prev_orders ?? 0;
                return $camp;
            })
            ->filter(fn($camp) => $camp->spend > 0 || $camp->prev_spend > 0)
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

        // Fetch GMS Item Performance
        $itemPerformance = \Illuminate\Support\Facades\DB::table('marketplace_ads_item_dailies')
            ->leftJoin('marketplace_products', function ($join) {
                $join->on('marketplace_ads_item_dailies.channel_item_id', '=', 'marketplace_products.item_id')
                     ->on('marketplace_ads_item_dailies.store_id', '=', 'marketplace_products.store_id');
            })
            ->where('marketplace_ads_item_dailies.store_id', $storeId)
            ->whereBetween('marketplace_ads_item_dailies.date', [$dateFrom, $dateTo])
            ->selectRaw('
                marketplace_ads_item_dailies.channel_item_id,
                MAX(marketplace_ads_item_dailies.channel_campaign_id) as any_campaign_id,
                MAX(CASE WHEN marketplace_ads_item_dailies.broad_gmv > 0 OR marketplace_ads_item_dailies.broad_order > 0 THEN marketplace_ads_item_dailies.channel_campaign_id ELSE NULL END) as gms_campaign_id,
                MAX(marketplace_products.item_sku) as item_sku,
                MAX(marketplace_products.item_name) as item_name,
                SUM(marketplace_ads_item_dailies.impressions) as impressions,
                SUM(marketplace_ads_item_dailies.clicks) as clicks,
                SUM(marketplace_ads_item_dailies.expense) as spend,
                SUM(marketplace_ads_item_dailies.broad_order + marketplace_ads_item_dailies.direct_order) as orders,
                SUM(marketplace_ads_item_dailies.broad_gmv + marketplace_ads_item_dailies.direct_gmv) as gmv,
                SUM(marketplace_ads_item_dailies.broad_gmv) as broad_gmv_sum,
                SUM(marketplace_ads_item_dailies.direct_gmv) as direct_gmv_sum
            ')
            ->groupBy('marketplace_ads_item_dailies.channel_item_id')
            ->orderByDesc('spend')
            ->limit(30)
            ->get();

        return view('marketplace.ads_dashboard', compact(
            'stores', 'storeId', 'dateFrom', 'dateTo', 'compareMode', 'kpi', 'dailyChartData', 'campaigns', 'syncRuns', 'heatmapData', 'historicalData', 'itemPerformance', 'lastSuccessRun'
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

    public function actionGmsCampaign(Request $request, \App\Services\Channels\Shopee\ShopeeChannel $shopeeChannel)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'campaign_id' => 'nullable|numeric',
            'roas_target' => 'nullable|numeric|min:0',
            'daily_budget' => 'nullable|numeric|min:0'
        ]);

        $store = Store::find($request->input('store_id'));
        if (!$store) {
            return response()->json(['status' => 'error', 'message' => 'Store not found.'], 404);
        }

        $campaignId = $request->input('campaign_id');
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
}
