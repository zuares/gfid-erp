<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSyncLog;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OmnichannelController extends Controller
{
    public function index()
    {
        return view('owner.omnichannel');
    }

    public function bootstrap(): JsonResponse
    {
        foreach ([
            ['code' => 'shopee', 'name' => 'Shopee'],
            ['code' => 'tiktok', 'name' => 'TikTok Shop'],
            ['code' => 'tokopedia', 'name' => 'Tokopedia'],
            ['code' => 'lazada', 'name' => 'Lazada'],
            ['code' => 'offline', 'name' => 'Offline'],
        ] as $channel) {
            Channel::firstOrCreate(['code' => $channel['code']], [
                'name' => $channel['name'],
                'status' => 'active',
            ]);
        }

        return response()->json([
            'message' => 'Channel default berhasil dibuat.',
            'channels' => Channel::orderBy('name')->get(),
        ]);
    }

    public function channels(): JsonResponse
    {
        return response()->json(Channel::withCount('stores')->orderBy('name')->get());
    }

    public function stores(): JsonResponse
    {
        $stores = Store::with('channel')->latest()->get()->map(function ($store) {
            return [
                'id' => $store->id,
                'channel_id' => $store->channel_id,
                'name' => $store->name,
                'external_shop_id' => $store->external_shop_id,
                'region' => $store->region,
                'status' => $store->status,
                'token_expires_at' => optional($store->token_expires_at)->toISOString(),
                'last_synced_at' => optional($store->last_synced_at)->toISOString(),
                'channel' => $store->channel ? [
                    'id' => $store->channel->id,
                    'code' => $store->channel->code,
                    'name' => $store->channel->name,
                    'status' => $store->channel->status,
                ] : null,
            ];
        });

        return response()->json($stores);
    }

    public function shopInfo(Store $store, ChannelManager $manager): JsonResponse
    {
        return response()->json($manager->driver($store)->getShopInfo($store));
    }

    public function syncOrders(Request $request, Store $store, ChannelManager $manager): JsonResponse
    {
        $data = $request->validate([
            'time_from' => ['required', 'integer'],
            'time_to' => ['required', 'integer'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $driver = $manager->driver($store);

        $listResponse = $driver->getOrders(
            $store,
            (int) $data['time_from'],
            (int) $data['time_to'],
            (int) ($data['page_size'] ?? 50)
        );

        if (!empty($listResponse['error'])) {
            MarketplaceSyncLog::create([
                'store_id' => $store->id,
                'action' => 'sync_orders',
                'status' => 'failed',
                'message' => $listResponse['message'] ?? $listResponse['error'],
                'payload' => $listResponse,
            ]);

            return response()->json([
                'message' => 'Gagal ambil daftar order dari marketplace.',
                'raw' => $listResponse,
            ], 422);
        }

        $orderSnList = collect(data_get($listResponse, 'response.order_list', []))
            ->pluck('order_sn')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($orderSnList)) {
            MarketplaceSyncLog::create([
                'store_id' => $store->id,
                'action' => 'sync_orders',
                'status' => 'success',
                'message' => 'Tidak ada order ditemukan.',
                'payload' => $listResponse,
            ]);

            return response()->json([
                'message' => 'Tidak ada order ditemukan di rentang tanggal ini.',
                'found' => 0,
                'synced' => 0,
                'order_sn_list' => [],
            ]);
        }

        $details = [];

        foreach (array_chunk($orderSnList, 50) as $chunk) {
            $detailResponse = $driver->getOrderDetail($store, $chunk);

            if (!empty($detailResponse['error'])) {
                MarketplaceSyncLog::create([
                    'store_id' => $store->id,
                    'action' => 'sync_orders_detail',
                    'status' => 'failed',
                    'message' => $detailResponse['message'] ?? $detailResponse['error'],
                    'payload' => $detailResponse,
                ]);

                return response()->json([
                    'message' => 'Order ditemukan, tapi gagal ambil detail order.',
                    'found' => count($orderSnList),
                    'synced' => 0,
                    'order_sn_list' => $orderSnList,
                    'raw' => $detailResponse,
                ], 422);
            }

            $details = array_merge($details, data_get($detailResponse, 'response.order_list', []));
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        }

        $synced = DB::transaction(function () use ($details, $store) {
            $count = 0;

            foreach ($details as $detail) {
                if (empty($detail['order_sn'])) {
                    continue;
                }

                $order = MarketplaceOrder::updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'channel_order_id' => $detail['order_sn'],
                    ],
                    [
                        // Kolom lama project
                        'external_order_id' => $detail['order_sn'],
                        'external_invoice_no' => $detail['booking_sn'] ?? null,
                        'order_date' => !empty($detail['create_time']) ? now()->setTimestamp((int) $detail['create_time']) : now(),
                        'status' => $detail['order_status'] ?? 'new',
                        'buyer_name' => $detail['buyer_username'] ?? null,
                        'buyer_phone' => data_get($detail, 'recipient_address.phone'),
                        'shipping_address' => data_get($detail, 'recipient_address.full_address'),
                        'shipping_city' => data_get($detail, 'recipient_address.city'),
                        'shipping_province' => data_get($detail, 'recipient_address.state'),
                        'shipping_postal_code' => data_get($detail, 'recipient_address.zipcode'),
                        'shipping_courier_code' => $detail['shipping_carrier'] ?? $detail['checkout_shipping_carrier'] ?? null,
                        'shipping_awb_no' => data_get($detail, 'package_list.0.package_number'),
                        'subtotal_items' => $detail['total_amount'] ?? 0,
                        'total_paid_customer' => $detail['total_amount'] ?? 0,
                        'payment_status' => !empty($detail['pay_time']) ? 'paid' : 'unpaid',
                        'payment_date' => !empty($detail['pay_time']) ? now()->setTimestamp((int) $detail['pay_time']) : null,
                        'raw_payload_json' => json_encode($detail),

                        // Kolom omnichannel baru
                        'booking_sn' => $detail['booking_sn'] ?? null,
                        'order_status' => $detail['order_status'] ?? null,
                        'buyer_username' => $detail['buyer_username'] ?? null,
                        'payment_method' => $detail['payment_method'] ?? null,
                        'shipping_carrier' => $detail['shipping_carrier'] ?? $detail['checkout_shipping_carrier'] ?? null,
                        'total_amount' => $detail['total_amount'] ?? 0,
                        'currency' => $detail['currency'] ?? 'IDR',
                        'ordered_at' => !empty($detail['create_time']) ? now()->setTimestamp((int) $detail['create_time']) : null,
                        'synced_at' => now(),
                        'raw_json' => $detail,
                    ]
                );

                $order->items()->delete();

                foreach (($detail['item_list'] ?? []) as $item) {
                    MarketplaceOrderItem::create([
                        // Kolom lama project
                        'order_id' => $order->id,
                        'line_no' => $item['order_item_id'] ?? 1,
                        'external_sku' => $item['model_sku'] ?? $item['item_sku'] ?? null,
                        'item_id' => isset($item['item_id']) ? (int) $item['item_id'] : null,
                        'item_code_snapshot' => $item['model_sku'] ?? $item['item_sku'] ?? null,
                        'item_name_snapshot' => $item['item_name'] ?? '-',
                        'variant_snapshot' => $item['model_name'] ?? null,
                        'price_original' => $item['model_original_price'] ?? 0,
                        'price_after_discount' => $item['model_discounted_price'] ?? $item['model_original_price'] ?? 0,
                        'line_gross_amount' => ($item['model_original_price'] ?? 0) * (int) ($item['model_quantity_purchased'] ?? $item['active_qty'] ?? 0),
                        'line_net_amount' => ($item['model_discounted_price'] ?? $item['model_original_price'] ?? 0) * (int) ($item['model_quantity_purchased'] ?? $item['active_qty'] ?? 0),

                        // Kolom omnichannel baru
                        'marketplace_order_id' => $order->id,
                        'external_item_id' => isset($item['item_id']) ? (string) $item['item_id'] : null,
                        'external_model_id' => isset($item['model_id']) ? (string) $item['model_id'] : null,
                        'item_name' => $item['item_name'] ?? '-',
                        'item_sku' => $item['item_sku'] ?? null,
                        'model_sku' => $item['model_sku'] ?? null,
                        'variant_name' => $item['model_name'] ?? null,
                        'qty' => (int) ($item['model_quantity_purchased'] ?? $item['active_qty'] ?? 0),
                        'price' => $item['model_original_price'] ?? $item['model_discounted_price'] ?? 0,
                        'image_url' => data_get($item, 'image_info.image_url'),
                        'raw_json' => $item,
                    ]);
                }

                $count++;
            }

            $store->update(['last_synced_at' => now()]);

            return $count;
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON');
        }

        MarketplaceSyncLog::create([
            'store_id' => $store->id,
            'action' => 'sync_orders',
            'status' => 'success',
            'message' => "Found " . count($orderSnList) . " orders, synced {$synced} orders.",
            'payload' => [
                'time_from' => $data['time_from'],
                'time_to' => $data['time_to'],
                'order_sn_list' => $orderSnList,
            ],
        ]);

        return response()->json([
            'message' => "Berhasil sync {$synced} dari " . count($orderSnList) . " order.",
            'found' => count($orderSnList),
            'synced' => $synced,
            'order_sn_list' => $orderSnList,
        ]);
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
}
