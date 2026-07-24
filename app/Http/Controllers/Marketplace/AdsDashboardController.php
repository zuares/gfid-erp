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

        if (!$storeId) {
            return view('marketplace.ads_dashboard', compact('stores', 'storeId', 'dateFrom', 'dateTo'))
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
            
        // Fetch Campaigns
        $campaigns = MarketplaceAdCampaign::where('store_id', $storeId)
            ->orderByDesc('spend')
            ->get();
            
        // Fetch Sync Runs
        $syncRuns = MarketplaceAdsSyncRun::where('store_id', $storeId)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $heatmapData = $analytics->getHourlyHeatmap($storeId, $dateFrom, $dateTo);

        return view('marketplace.ads_dashboard', compact(
            'stores', 'storeId', 'dateFrom', 'dateTo', 'kpi', 'dailyChartData', 'campaigns', 'syncRuns', 'heatmapData'
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
