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
        return view('marketplace.promotions', [
            'selectedStoreId' => $request->integer('store_id') ?: null,
            'selectedStatus'   => $request->query('status', 'ongoing'),
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
}
