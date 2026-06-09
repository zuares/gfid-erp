<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncOrdersRequest;
use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\OrderFulfillment;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Channels\ChannelManager;
use App\Services\OrderFulfillmentService;
use App\Services\OmnichannelSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OmnichannelController extends Controller
{
    public function __construct(
        protected OmnichannelSyncService $sync,
        protected ChannelManager $manager,
        protected OrderFulfillmentService $fulfillment,
    ) {}

    public function index(\Illuminate\Http\Request $request)
    {
        $validTabs   = ['toko', 'orders', 'fulfillment', 'sku-mapping'];
        $initialTab  = in_array($request->query('tab'), $validTabs) ? $request->query('tab') : 'toko';

        $today      = now()->toDateString();
        $weekAgo    = now()->subDays(6)->toDateString();

        $filters = [
            'date_from' => $request->query('date_from', $weekAgo),
            'date_to'   => $request->query('date_to', $today),
        ];

        return view('owner.omnichannel', compact('initialTab', 'filters'));
    }

    public function tokoPage(): \Illuminate\View\View
    {
        return view('owner.marketplace.toko');
    }

    public function ordersPage(Request $request): \Illuminate\View\View
    {
        $today   = now()->toDateString();
        $weekAgo = now()->subDays(6)->toDateString();
        $filters = [
            'date_from' => $request->query('date_from', $weekAgo),
            'date_to'   => $request->query('date_to', $today),
        ];
        return view('owner.marketplace.orders', compact('filters'));
    }

    public function fulfillmentPage(): \Illuminate\View\View
    {
        return view('owner.marketplace.fulfillment');
    }

    public function skuMappingPage(): \Illuminate\View\View
    {
        return view('owner.marketplace.sku-mapping');
    }

    public function bootstrap(): JsonResponse
    {
        $channels = $this->sync->bootstrapChannels();

        return response()->json([
            'message'  => 'Channel default berhasil dibuat.',
            'channels' => $channels,
        ]);
    }

    public function channels(): JsonResponse
    {
        return response()->json(
            Channel::withCount('stores')->orderBy('name')->get()
        );
    }

    public function stores(): JsonResponse
    {
        $stores = Store::with(['channel', 'defaultWarehouse'])->latest()->get()->map(fn (Store $store) => [
            'id'                   => $store->id,
            'channel_id'           => $store->channel_id,
            'name'                 => $store->name,
            'external_shop_id'     => $store->external_shop_id,
            'region'               => $store->region,
            'status'               => $store->status,
            'token_expires_at'     => $store->token_expires_at?->toISOString(),
            'last_synced_at'       => $store->last_synced_at?->toISOString(),
            'default_warehouse_id' => $store->default_warehouse_id,
            'default_warehouse'    => $store->defaultWarehouse ? [
                'id'   => $store->defaultWarehouse->id,
                'code' => $store->defaultWarehouse->code,
                'name' => $store->defaultWarehouse->name,
            ] : null,
            'channel'          => $store->channel ? [
                'id'     => $store->channel->id,
                'code'   => $store->channel->code,
                'name'   => $store->channel->name,
                'status' => $store->channel->status,
            ] : null,
        ]);

        return response()->json($stores);
    }

    public function shopInfo(Store $store): JsonResponse
    {
        return response()->json(
            $this->manager->driver($store)->getShopInfo($store)
        );
    }

    public function syncOrders(SyncOrdersRequest $request, Store $store): JsonResponse
    {
        try {
            $result = $this->sync->syncOrders(
                $store,
                (int) $request->time_from,
                (int) $request->time_to,
                (int) ($request->page_size ?? 50),
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function localOrders(): JsonResponse
    {
        return response()->json(
            MarketplaceOrder::with(['store.channel', 'items'])
                ->latest('ordered_at')
                ->limit(100)
                ->get()
        );
    }

    public function warehouses(): JsonResponse
    {
        return response()->json(
            Warehouse::orderBy('name')->get(['id', 'code', 'name'])
        );
    }

    public function updateStore(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'default_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ]);

        $store->update($data);

        // Update warehouse_id di semua fulfillment pending milik store ini yang warehouse-nya masih null
        if ($data['default_warehouse_id']) {
            $warehouseId = $data['default_warehouse_id'];
            $fulfillments = OrderFulfillment::whereHas('order', fn ($q) => $q->where('store_id', $store->id))
                ->whereIn('status', [OrderFulfillment::STATUS_DRAFT, OrderFulfillment::STATUS_PENDING_REVIEW])
                ->whereNull('warehouse_id')
                ->get();

            foreach ($fulfillments as $f) {
                $f->update(['warehouse_id' => $warehouseId]);
                $this->fulfillment->refreshStock($f->load('lines'));
            }
        }

        $store->load('channel');
        return response()->json([
            'message'             => 'Toko diperbarui.',
            'default_warehouse_id'=> $store->default_warehouse_id,
            'fulfillments_updated'=> isset($fulfillments) ? $fulfillments->count() : 0,
        ]);
    }
}
