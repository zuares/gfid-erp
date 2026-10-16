<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdExperiment;
use App\Models\MarketplaceProduct;
use App\Models\Store;
use App\Services\Marketplace\Ads\AdsExperimentService;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdsExperimentController extends Controller
{
    public function store(Request $request, AdsExperimentService $experiments): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'channel_campaign_id' => ['nullable', 'string', 'max:80'],
            'channel_item_id' => ['nullable', 'string', 'max:80'],
            'change_type' => ['required', 'in:creative,target_roas,price,budget,title,other'],
            'before_value' => ['required', 'string', 'max:1000'],
            'after_value' => ['required', 'string', 'max:1000'],
            'changed_at' => ['nullable', 'date'],
            'objective' => ['required', 'string', 'max:255'],
            'primary_kpi' => ['required', 'in:roas,acos,orders,cvr,profit,gmv,ctr'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'confounders' => ['nullable', 'array', 'max:6'],
            'confounders.*' => ['string', 'max:40'],
        ]);

        $store = Store::query()
            ->where('is_active', true)
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->findOrFail($data['store_id']);

        $campaignId = $data['channel_campaign_id'] ?? null;
        $campaign = $campaignId
            ? MarketplaceAdCampaign::query()
                ->where('store_id', $store->id)
                ->where('channel_campaign_id', $campaignId)
                ->first()
            : null;

        // Scope campaign harus berasal dari toko yang dipilih. GMS virtual
        // campaign tidak selalu punya row lokal, sehingga tetap diizinkan.
        if ($campaignId && ! $campaign && ! str_starts_with((string) $campaignId, 'GMS-')) {
            return response()->json([
                'message' => 'Campaign tidak ditemukan pada toko yang dipilih.',
            ], 422);
        }

        if ($campaign) {
            $data['channel_item_id'] = $campaign->channel_item_id;
        }

        $experiment = $experiments->recordManualChange(
            store: $store,
            change: $data,
            createdBy: $request->user()?->getAuthIdentifier(),
        );

        return response()->json([
            'message' => 'Perubahan berhasil dicatat dan masuk ke fase Learning.',
            'data' => [
                'experiment_id' => $experiment->id,
                'change_type' => $experiment->change_type,
                'lifecycle_status' => $experiment->lifecycle_status,
            ],
        ], 201);
    }

    public function index(Request $request, AdsExperimentService $experiments): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'channel_campaign_id' => ['nullable', 'string', 'max:80'],
            'change_type' => ['nullable', 'in:creative,target_roas,price,budget,title,other,price_and_target_roas'],
            'lifecycle_status' => ['nullable', 'string', 'max:32'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $rows = MarketplaceAdExperiment::query()
            ->with(['store:id,name', 'internalItem:id,code,name'])
            ->when(isset($data['store_id']), fn ($q) => $q->where('store_id', $data['store_id']))
            ->when(isset($data['channel_campaign_id']), fn ($q) => $q->where('channel_campaign_id', $data['channel_campaign_id']))
            ->when(isset($data['change_type']), fn ($q) => $q->where('change_type', $data['change_type']))
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->limit($data['limit'] ?? 50)
            ->get();

        // Historical clients may have written several target-ROAS rows on
        // one day before the active-experiment guard existed. Present that
        // group as one latest row without deleting or mutating history.
        $rows = $rows
            ->groupBy(function (MarketplaceAdExperiment $experiment): string {
                if ($experiment->change_type !== MarketplaceAdExperiment::CHANGE_TARGET_ROAS) {
                    return 'experiment:' . $experiment->id;
                }

                return implode(':', [
                    'target_roas',
                    $experiment->store_id,
                    $experiment->channel_campaign_id ?? '-',
                    $experiment->channel_item_id ?? '-',
                    $experiment->changed_at?->toDateString() ?? '-',
                ]);
            })
            ->map(fn ($group) => $group->sortByDesc('changed_at')->sortByDesc('id')->first())
            ->values();

        $productContexts = $this->productContexts($rows);

        $payload = $rows->map(fn (MarketplaceAdExperiment $experiment) => [
                'experiment' => $experiment,
                'product' => $productContexts->get($experiment->id),
                'details' => $experiments->details($experiment),
            ]);
        if (isset($data['lifecycle_status'])) {
            $payload = $payload->filter(
                fn (array $row): bool => ($row['details']['lifecycle_status'] ?? null) === $data['lifecycle_status']
            );
        }

        return response()->json(['data' => $payload->values()]);
    }

    public function show(MarketplaceAdExperiment $experiment, AdsExperimentService $experiments): JsonResponse
    {
        $experiment->load(['store:id,name', 'internalItem:id,code,name']);

        $details = $experiments->details($experiment);
        $details['product'] = $this->productContexts(collect([$experiment]))->get($experiment->id);

        return response()->json([
            'data' => $details,
        ]);
    }

    /**
     * Resolve product-facing labels once for the evaluation list/detail UI.
     * This is display context only and does not affect experiment calculations.
     */
    private function productContexts(Collection $experiments): Collection
    {
        $storeIds = $experiments->pluck('store_id')->filter()->unique()->values();
        $itemIds = $experiments->pluck('channel_item_id')->filter()->map(fn ($id) => (string) $id)->unique()->values();
        $campaignIds = $experiments->pluck('channel_campaign_id')->filter()->map(fn ($id) => (string) $id)->unique()->values();

        $products = $storeIds->isNotEmpty() && $itemIds->isNotEmpty()
            ? MarketplaceProduct::query()
                ->whereIn('store_id', $storeIds->all())
                ->whereIn('item_id', $itemIds->all())
                ->get(['store_id', 'item_id', 'item_name', 'item_sku', 'image_url'])
                ->keyBy(fn ($product) => $product->store_id . '|' . (string) $product->item_id)
            : collect();

        $campaigns = $storeIds->isNotEmpty() && $campaignIds->isNotEmpty()
            ? MarketplaceAdCampaign::query()
                ->whereIn('store_id', $storeIds->all())
                ->whereIn('channel_campaign_id', $campaignIds->all())
                ->get(['store_id', 'channel_campaign_id', 'campaign_name'])
                ->keyBy(fn ($campaign) => $campaign->store_id . '|' . (string) $campaign->channel_campaign_id)
            : collect();

        return $experiments->mapWithKeys(function (MarketplaceAdExperiment $experiment) use ($products, $campaigns): array {
            $product = $experiment->channel_item_id
                ? $products->get($experiment->store_id . '|' . (string) $experiment->channel_item_id)
                : null;
            $campaign = $experiment->channel_campaign_id
                ? $campaigns->get($experiment->store_id . '|' . (string) $experiment->channel_campaign_id)
                : null;

            return [$experiment->id => [
                'name' => $product?->item_name ?: $experiment->internalItem?->name,
                'sku' => $product?->item_sku,
                'image_url' => $product?->image_url,
                'campaign_name' => $campaign?->campaign_name,
            ]];
        });
    }

    public function invalidate(Request $request, MarketplaceAdExperiment $experiment, AdsExperimentService $experiments): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $experiment = $experiments->invalidate(
            experiment: $experiment,
            reason: $data['reason'],
            invalidatedBy: $request->user()?->getAuthIdentifier(),
        );

        return response()->json([
            'message' => 'Record experiment di-invalidasi dan tidak lagi dihitung sebagai experiment aktif.',
            'data' => [
                'experiment_id' => $experiment->id,
                'lifecycle_status' => $experiment->lifecycle_status,
                'invalidated_at' => $experiment->invalidated_at?->toIso8601String(),
                'reason' => $experiment->invalidation_reason,
            ],
        ]);
    }

    public function simulate(Request $request, AdsExperimentService $experiments): JsonResponse
    {
        $data = $request->validate([
            'experiment_id' => ['required', 'integer', 'exists:marketplace_ad_experiments,id'],
            'period' => ['nullable', 'in:baseline,observation'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'target_roas' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'spend' => ['nullable', 'numeric', 'min:0'],
            'clicks' => ['nullable', 'integer', 'min:0'],
            'qty' => ['nullable', 'numeric', 'min:0'],
        ]);

        $experiment = MarketplaceAdExperiment::query()->findOrFail($data['experiment_id']);

        return response()->json([
            'data' => $experiments->simulate($experiment, $data),
        ]);
    }
}
