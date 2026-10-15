<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncOrdersRequest;
use App\Models\Channel;
use App\Models\ItemCostSnapshot;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdGroup;
use App\Models\MarketplaceAdItemMap;
use App\Models\MarketplaceOrder;
use App\Models\Item;
use App\Models\MarketplaceProduct;
use App\Models\MarketplacePromotion;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceSyncLog;
use App\Models\OrderFulfillment;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Marketplace\Ads\ItemHppResolver;
use App\Services\Channels\ChannelManager;
use App\Support\GmvMaxAnalytics;
use App\Services\MarketplaceIssueService;
use App\Services\MarketplaceSyncService;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MarketplacePromotionsController extends Controller
{
    public function promotions(Request $request): \Illuminate\View\View
    {
        $selectedTab = $request->query('tab', 'discount');

        if (! in_array($selectedTab, ['discount', 'voucher'], true)) {
            $selectedTab = 'discount';
        }

        return view('marketplace.promotions', [
            'selectedStoreId' => $request->integer('store_id') ?: null,
            'selectedStatus'   => $request->query('status', 'ongoing'),
            'selectedTab'     => $selectedTab,
        ]);
    }

    public function promotionCreatePage(Request $request): \Illuminate\View\View
    {
        $store = $this->resolvePromotionStore($request->integer('store_id'));

        return view('marketplace.promotion-edit', [
            'store' => $store ? $this->normalizeStorePayload($store) : null,
            'discountId' => null,
            'mode' => 'create',
            'backUrl' => route('marketplace.promotions', [
                'store_id' => $store?->id,
            ]),
        ]);
    }

    public function promotionEdit(Request $request, Store $store, int $discountId): \Illuminate\View\View
    {
        $store = $this->resolvePromotionStoreBinding($store);

        return view('marketplace.promotion-edit', [
            'store' => $this->normalizeStorePayload($store),
            'discountId' => $discountId,
            'mode' => 'edit',
            'backUrl' => route('marketplace.promotions', [
                'store_id' => $store->id,
            ]),
        ]);
    }

    public function promotionsSummary(Request $request): \Illuminate\View\View
    {
        $today = now()->toDateString();

        return view('marketplace.promotions-summary', [
            'filters' => [
                'store_id'  => $request->query('store_id', 'all'),
                'status'    => $request->query('status', 'all'),
                'date_from' => $request->query('date_from', now()->subDays(29)->toDateString()),
                'date_to'   => $request->query('date_to', $today),
            ],
        ]);
    }

    private function resolvePromotionStore(?int $storeId = null): ?Store
    {
        if ($storeId) {
            $store = Store::with('channel')->find($storeId);

            return $this->isPromotionFilterableStore($store) ? $store : null;
        }

        return Store::with('channel')
            ->where('is_active', true)
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['shopee', 'shp']))
            ->orderBy('id')
            ->first();
    }

    private function resolvePromotionStoreBinding(mixed $store): Store
    {
        $storeId = $store instanceof Store ? $store->id : $store;
        $resolved = is_numeric($storeId) ? Store::with('channel')->findOrFail((int) $storeId) : null;

        abort_unless($this->isPromotionFilterableStore($resolved), 404, 'Toko promosi tidak ditemukan.');

        return $resolved;
    }

    private function isPromotionFilterableStore(?Store $store): bool
    {
        if (! $store || $store->is_active === false) {
            return false;
        }

        return in_array(strtolower((string) ($store->channel?->code ?? '')), ['shopee', 'shp'], true);
    }

    private function normalizeStorePayload(Store $store): array
    {
        return [
            'id' => $store->id,
            'name' => $store->name,
            'code' => $store->code,
            'is_active' => $store->is_active,
            'channel' => $store->channel ? [
                'id' => $store->channel->id,
                'name' => $store->channel->name,
                'code' => $store->channel->code,
            ] : null,
        ];
    }
}
