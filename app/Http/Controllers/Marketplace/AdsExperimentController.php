<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdExperiment;
use App\Models\Store;
use App\Services\Marketplace\Ads\AdsExperimentService;
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

        $payload = $rows->map(fn (MarketplaceAdExperiment $experiment) => [
                'experiment' => $experiment,
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

        return response()->json([
            'data' => $experiments->details($experiment),
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
