<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceShopPerformanceSnapshot;
use App\Models\Store;
use App\Services\Marketplace\ShopeeShopPerformanceSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ShopeeShopPerformanceController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->period($request);
        $storeId = $request->integer('store_id') ?: null;

        $stores = $this->stores();
        $snapshots = MarketplaceShopPerformanceSnapshot::query()
            ->with('store:id,name')
            ->where('granularity', 'day')
            ->whereDate('period_start', '>=', $from->toDateString())
            ->whereDate('period_start', '<=', $to->toDateString())
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->orderBy('period_start')
            ->get();

        $aggregateSnapshots = MarketplaceShopPerformanceSnapshot::query()
            ->with('store:id,name')
            ->where('granularity', 'customize')
            ->whereDate('period_start', $from->toDateString())
            ->whereDate('period_end', $to->toDateString())
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->get();

        $summary = $this->sumMetrics($aggregateSnapshots->isNotEmpty() ? $aggregateSnapshots : $snapshots);
        $daily = $snapshots
            ->groupBy(fn ($row) => $row->period_start->toDateString())
            ->map(fn ($rows, $date) => array_merge(['date' => $date], $this->sumMetrics($rows)))
            ->values();
        $byStore = ($aggregateSnapshots->isNotEmpty() ? $aggregateSnapshots : $snapshots)
            ->groupBy('store_id')
            ->map(function ($rows) {
                return array_merge([
                    'store_id' => (int) $rows->first()->store_id,
                    'store_name' => $rows->first()->store?->name ?: 'Toko Shopee',
                ], $this->sumMetrics($rows));
            })
            ->sortByDesc('sales')
            ->values();

        $lastFetchedAt = collect([$snapshots->max('fetched_at'), $aggregateSnapshots->max('fetched_at')])->filter()->max();
        $hasPrincipal = $stores->contains(function (Store $store): bool {
            return filled($store->credential('principal_id'))
                || filled(data_get($store->meta ?? [], 'principal_id'))
                || filled(config('shopee.principal_id'));
        });

        return view('marketplace.shopee-performance', [
            'stores' => $stores,
            'selectedStoreId' => $storeId,
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'daily' => $daily,
            'byStore' => $byStore,
            'lastFetchedAt' => $lastFetchedAt,
            'hasPrincipal' => $hasPrincipal,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        [$from, $to] = $this->period($request);
        $storeId = $request->integer('store_id') ?: null;

        $snapshots = MarketplaceShopPerformanceSnapshot::query()
            ->where('granularity', 'day')
            ->whereDate('period_start', '>=', $from->toDateString())
            ->whereDate('period_start', '<=', $to->toDateString())
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->orderBy('period_start')
            ->get();

        $aggregateSnapshots = MarketplaceShopPerformanceSnapshot::query()
            ->where('granularity', 'customize')
            ->whereDate('period_start', $from->toDateString())
            ->whereDate('period_end', $to->toDateString())
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->get();

        return response()->json([
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'store_id' => $storeId,
            ],
            'summary' => $this->sumMetrics($aggregateSnapshots->isNotEmpty() ? $aggregateSnapshots : $snapshots),
            'daily' => $snapshots
                ->groupBy(fn ($row) => $row->period_start->toDateString())
                ->map(fn ($rows, $date) => array_merge(['date' => $date], $this->sumMetrics($rows)))
                ->values(),
        ]);
    }

    public function sync(Request $request, ShopeeShopPerformanceSyncService $syncService): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'timezone' => ['nullable', 'in:GMT+7,GMT+8,GMT-3'],
            'currency' => ['nullable', 'in:LOCAL,USD'],
        ]);

        $today = now('Asia/Jakarta')->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $validated['date_to'] ?? $today->copy()->subDay()->toDateString())->startOfDay();
        $from = Carbon::createFromFormat('Y-m-d', $validated['date_from'] ?? $to->copy()->subDays(6)->toDateString())->startOfDay();
        if ($from->greaterThan($to) || $to->greaterThanOrEqualTo($today)) {
            return back()->withInput()->with('error', 'Periode sync harus valid dan tanggal akhir harus sebelum hari ini.');
        }
        if ($from->diffInDays($to) > 30) {
            return back()->withInput()->with('error', 'Sync dari halaman dibatasi maksimal 31 hari. Gunakan command untuk histori lebih panjang.');
        }

        $stores = $this->stores()
            ->when(! empty($validated['store_id']), fn ($collection) => $collection->where('id', (int) $validated['store_id']))
            ->values();
        if ($stores->isEmpty()) {
            return back()->withInput()->with('error', 'Toko Shopee aktif tidak ditemukan.');
        }

        $timezone = $validated['timezone'] ?? 'GMT+7';
        $currency = $validated['currency'] ?? 'LOCAL';
        $saved = 0;
        $errors = [];

        foreach ($stores as $store) {
            $lock = Cache::lock("marketplace:shopee_performance_sync:{$store->id}", 1800);
            if (! $lock->get()) {
                $errors[] = "{$store->name}: sync sedang berjalan.";
                continue;
            }

            try {
                $result = $syncService->syncDailyRange($store, $from, $to, $timezone, $currency);
                $saved += (int) ($result['saved'] ?? 0);
            } catch (\Throwable $e) {
                $errors[] = "{$store->name}: {$e->getMessage()}";
            } finally {
                $lock->release();
            }
        }

        $message = "Sync selesai: {$saved} snapshot disimpan.";
        if ($errors !== []) {
            $message .= ' ' . implode(' ', $errors);
        }

        return back()->with($errors === [] ? 'success' : 'warning', $message);
    }

    private function stores()
    {
        return Store::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['shopee', 'shp', 'SHOPEE']))
            ->with('channel:id,name,code')
            ->orderBy('name')
            ->get();
    }

    private function period(Request $request): array
    {
        $today = now('Asia/Jakarta')->startOfDay();
        $defaultTo = $today->copy()->subDay();
        $defaultFrom = $defaultTo->copy()->subDays(29);

        try {
            $from = Carbon::createFromFormat('Y-m-d', (string) ($request->query('date_from') ?: $defaultFrom->toDateString()))->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', (string) ($request->query('date_to') ?: $defaultTo->toDateString()))->startOfDay();
        } catch (\Throwable) {
            $from = $defaultFrom;
            $to = $defaultTo;
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }
        if ($to->greaterThanOrEqualTo($today)) {
            $to = $defaultTo;
        }
        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366);
        }

        return [$from, $to];
    }

    private function sumMetrics(iterable $rows): array
    {
        $rows = collect($rows);

        return [
            'sales' => (float) $rows->sum(fn ($row) => (float) $row->sales),
            'orders' => (int) $rows->sum(fn ($row) => (int) $row->orders),
            'units_sold' => (int) $rows->sum(fn ($row) => (int) $row->units_sold),
            'product_clicks' => (int) $rows->sum(fn ($row) => (int) $row->product_clicks),
            'product_views' => (int) $rows->sum(fn ($row) => (int) $row->product_views),
            'unique_visitors' => (int) $rows->sum(fn ($row) => (int) $row->unique_visitors),
            'flash_sale_sales' => (float) $rows->sum(fn ($row) => (float) $row->flash_sale_sales),
            'voucher_sales' => (float) $rows->sum(fn ($row) => (float) $row->voucher_sales),
            'voucher_cost' => (float) $rows->sum(fn ($row) => (float) $row->voucher_cost),
            'average_basket_size' => $this->ratio(
                (float) $rows->sum(fn ($row) => (float) $row->sales),
                (int) $rows->sum(fn ($row) => (int) $row->orders)
            ),
            'items_per_order' => $this->ratio(
                (int) $rows->sum(fn ($row) => (int) $row->units_sold),
                (int) $rows->sum(fn ($row) => (int) $row->orders)
            ),
            'item_conversion_rate' => $this->ratio(
                (int) $rows->sum(fn ($row) => (int) $row->units_sold),
                (int) $rows->sum(fn ($row) => (int) $row->product_views)
            ),
            'order_conversion_rate' => $this->ratio(
                (int) $rows->sum(fn ($row) => (int) $row->orders),
                (int) $rows->sum(fn ($row) => (int) $row->product_clicks)
            ),
        ];
    }

    private function ratio(float|int $numerator, float|int $denominator): float
    {
        return $denominator > 0 ? (float) $numerator / (float) $denominator : 0.0;
    }
}
