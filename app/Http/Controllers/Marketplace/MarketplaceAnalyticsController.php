<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\Marketplace\MarketplaceAnalyticsCashService;
use App\Services\Marketplace\MarketplaceAnalyticsOverviewService;
use App\Services\Marketplace\MarketplaceAnalyticsProductService;
use App\Services\Marketplace\MarketplaceAnalyticsReturnService;
use App\Services\Marketplace\MarketplaceCohortService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MarketplaceAnalyticsController extends Controller
{
    public function index(Request $request): \Illuminate\View\View
    {
        $today = now()->toDateString();
        $monthFrom = now()->startOfMonth()->toDateString();
        $filters = [
            'date_from' => $request->query('date_from', $monthFrom),
            'date_to' => $request->query('date_to', $today),
            'store_id' => $request->query('store_id'),
            'compare_mode' => $request->query('compare_mode', 'prev_period'),
        ];

        return view('marketplace.analytics', compact('filters'));
    }

    public function summary(Request $request, MarketplaceAnalyticsOverviewService $analytics): JsonResponse
    {
        $filters = $this->summaryFilters($request);
        $cacheKey = $this->cacheKey('summary', $filters);
        $payload = Cache::remember($cacheKey, now()->addSeconds(45), fn (): array => $analytics->summary($filters));

        return $this->json($payload);
    }

    public function kpis(Request $request, MarketplaceAnalyticsOverviewService $analytics): JsonResponse
    {
        $filters = $this->summaryFilters($request);
        $cacheKey = $this->cacheKey('kpis', $filters);
        $payload = Cache::remember($cacheKey, now()->addSeconds(45), fn (): array => $analytics->kpis($filters));

        return $this->json($payload);
    }

    public function products(Request $request, MarketplaceAnalyticsProductService $products): JsonResponse
    {
        $filters = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        return $this->json(['data' => $products->products($filters)]);
    }

    public function cohort(Request $request, MarketplaceCohortService $cohort): JsonResponse
    {
        $filters = $request->validate([
            'mode' => ['nullable', 'in:customer,product'],
            'group_by' => ['nullable', 'in:product,category'],
            'metric' => ['nullable', 'in:retention_pct,active_customers,orders,qty_sold,revenue,gross_profit,gross_margin_pct,net_profit'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'marketplace' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:150'],
            'product' => ['nullable', 'string', 'max:190'],
            'sku' => ['nullable', 'string', 'max:100'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        return $this->json($cohort->cohort($filters));
    }

    public function cohortOptions(Request $request, MarketplaceCohortService $cohort): JsonResponse
    {
        $filters = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'marketplace' => ['nullable', 'string', 'max:100'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        return $this->json($cohort->options($filters));
    }

    public function cashOrders(Request $request, MarketplaceAnalyticsCashService $cash): JsonResponse
    {
        $filters = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'settlement' => ['nullable', 'in:all,settled,unsettled,shipped,warehouse,confirm,cancelled,return_refund'],
        ]);

        return $this->json($cash->orders(
            $filters,
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 50),
            (string) $request->input('settlement', 'settled'),
        ));
    }

    public function returnOrders(Request $request, MarketplaceAnalyticsReturnService $returns): JsonResponse
    {
        $filters = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'type' => ['nullable', 'in:return_refund,failed_delivery'],
        ]);

        return $this->json($returns->orders(
            $filters,
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 50),
            (string) $request->input('type', 'return_refund'),
        ));
    }

    private function summaryFilters(Request $request): array
    {
        return $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'compare_mode' => ['nullable', 'in:prev_period,prev_month,prev_quarter,prev_year'],
        ]);
    }

    private function cacheKey(string $scope, array $filters): string
    {
        return 'marketplace:analytics:'.$scope.':'.sha1(implode('|', [
            (string) ($filters['store_id'] ?? 'all'),
            Carbon::parse($filters['date_from'])->toDateString(),
            Carbon::parse($filters['date_to'])->toDateString(),
            (string) ($filters['compare_mode'] ?? 'prev_period'),
        ]));
    }

    private function json(array $payload): JsonResponse
    {
        return response()
            ->json($payload)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
