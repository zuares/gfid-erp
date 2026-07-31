<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\Ads\AdsActionService;
use App\Services\Marketplace\Ads\AdsDashboardService;
use App\Services\Marketplace\Ads\AdsAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
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
            $compareMode = $request->input('compare_mode', 'prev_period');

            return view('marketplace.ads_dashboard', compact('stores', 'storeId', 'dateFrom', 'dateTo', 'compareMode'))
                ->with('error', 'Tidak ada toko Shopee aktif dengan token valid.');
        }

        // Auto-sync data hari ini TANPA ANTRIAN (Synchronous) dengan cooldown 15 menit
        $autoSyncKey = 'ads_dashboard_autosync_today_' . $storeId;
        if (! \Illuminate\Support\Facades\Cache::has($autoSyncKey)) {
            $syncParams = [
                '--from' => now()->toDateString(),
                '--to'   => now()->toDateString(),
            ];
            if ($storeId !== 'all') {
                $syncParams['--store'] = $storeId;
            }
            
            // Eksekusi artisan command secara asinkron (dikirim ke queue, page load instan)
            \Illuminate\Support\Facades\Artisan::queue('marketplace:sync-ads', $syncParams);
            
            \Illuminate\Support\Facades\Cache::put($autoSyncKey, true, now()->addMinutes(15));
        }

        $dashboard = $dashboardService->buildDashboardData(
            $stores,
            $storeId,
            $request->input('date_from'),
            $request->input('date_to'),
            $request->input('compare_mode', 'prev_period'),
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
