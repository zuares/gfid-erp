<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAdExperiment;
use App\Services\Marketplace\Ads\AdsExperimentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdsExperimentController extends Controller
{
    public function index(Request $request, AdsExperimentService $experiments): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'change_type' => ['nullable', 'in:price,target_roas,price_and_target_roas'],
            'lifecycle_status' => ['nullable', 'string', 'max:32'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $rows = MarketplaceAdExperiment::query()
            ->with(['store:id,name', 'internalItem:id,code,name'])
            ->when(isset($data['store_id']), fn ($q) => $q->where('store_id', $data['store_id']))
            ->when(isset($data['change_type']), fn ($q) => $q->where('change_type', $data['change_type']))
            ->when(isset($data['lifecycle_status']), fn ($q) => $q->where('lifecycle_status', $data['lifecycle_status']))
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->limit($data['limit'] ?? 50)
            ->get();

        return response()->json([
            'data' => $rows->map(fn (MarketplaceAdExperiment $experiment) => [
                'experiment' => $experiment,
                'details' => $experiments->details($experiment),
            ])->values(),
        ]);
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
