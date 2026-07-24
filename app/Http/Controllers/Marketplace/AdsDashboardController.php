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

class AdsDashboardController extends Controller
{
    public function index(Request $request, AdsAnalyticsService $analytics)
    {
        // 1. Dapatkan daftar store milik user (atau semua jika role mencukupi)
        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')
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
            ->withSum(['dailies as sum_gmv' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'broad_gmv')
            ->withSum(['dailies as sum_clicks' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'clicks')
            ->withSum(['dailies as sum_orders' => fn($q) => $q->whereBetween('date', [$dateFrom, $dateTo])], 'broad_order')
            ->withSum(['dailies as sum_prev_expense' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'expense')
            ->withSum(['dailies as sum_prev_gmv' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'broad_gmv')
            ->withSum(['dailies as sum_prev_clicks' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'clicks')
            ->withSum(['dailies as sum_prev_orders' => fn($q) => $q->whereBetween('date', [$prevDateFrom, $prevDateTo])], 'broad_order')
            ->get()
            ->map(function ($camp) {
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

        $heatmapData = $analytics->getHourlyHeatmap($storeId, $dateFrom, $dateTo);
        $historicalData = $analytics->getHistoricalComparison($storeId, $dateFrom, $dateTo, 3);

        return view('marketplace.ads_dashboard', compact(
            'stores', 'storeId', 'dateFrom', 'dateTo', 'compareMode', 'kpi', 'dailyChartData', 'campaigns', 'syncRuns', 'heatmapData', 'historicalData'
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
        
        // We use queue to avoid blocking
        Artisan::queue($cmd, $params);

        return back()->with('success', 'Proses sinkronisasi telah dijadwalkan di background.');
    }
}
