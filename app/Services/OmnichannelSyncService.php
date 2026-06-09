<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceSyncLog;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OmnichannelSyncService
{
    public function __construct(
        protected ChannelManager $manager,
        protected OrderFulfillmentService $fulfillment,
    ) {}

    /**
     * Seed channel default (Shopee, TikTok, dst).
     */
    public function bootstrapChannels(): array
    {
        foreach ([
            ['code' => 'shopee',    'name' => 'Shopee'],
            ['code' => 'tiktok',    'name' => 'TikTok Shop'],
            ['code' => 'tokopedia', 'name' => 'Tokopedia'],
            ['code' => 'lazada',    'name' => 'Lazada'],
            ['code' => 'offline',   'name' => 'Offline'],
        ] as $channel) {
            Channel::firstOrCreate(
                ['code' => $channel['code']],
                ['name' => $channel['name'], 'status' => 'active']
            );
        }

        return Channel::orderBy('name')->get()->toArray();
    }

    /**
     * Sync orders dari marketplace ke DB lokal.
     * Mengembalikan array: found, synced, order_sn_list, message.
     * Melempar \RuntimeException jika API error.
     */
    public function syncOrders(Store $store, int $timeFrom, int $timeTo, int $pageSize = 50): array
    {
        $driver = $this->manager->driver($store);

        // 1. Ambil daftar order
        $listResponse = $driver->getOrders($store, $timeFrom, $timeTo, $pageSize);

        if (! empty($listResponse['error'])) {
            $this->log($store, 'sync_orders', 'failed', $listResponse['message'] ?? $listResponse['error'], $listResponse);
            throw new \RuntimeException($listResponse['message'] ?? 'Gagal ambil daftar order dari marketplace.');
        }

        $orderSnList = collect(data_get($listResponse, 'response.order_list', []))
            ->pluck('order_sn')->filter()->unique()->values()->all();

        if (empty($orderSnList)) {
            $this->log($store, 'sync_orders', 'success', 'Tidak ada order ditemukan.', $listResponse);
            return ['found' => 0, 'synced' => 0, 'order_sn_list' => [], 'message' => 'Tidak ada order ditemukan di rentang tanggal ini.'];
        }

        // 2. Ambil detail order per chunk
        $details = [];
        foreach (array_chunk($orderSnList, 50) as $chunk) {
            $detailResponse = $driver->getOrderDetail($store, $chunk);
            if (! empty($detailResponse['error'])) {
                $this->log($store, 'sync_orders_detail', 'failed', $detailResponse['message'] ?? $detailResponse['error'], $detailResponse);
                throw new \RuntimeException('Order ditemukan, tapi gagal ambil detail order.');
            }
            $details = array_merge($details, data_get($detailResponse, 'response.order_list', []));
        }

        // 3. Simpan ke DB
        $synced = $this->upsertOrders($store, $details);

        // 4. Auto-buat fulfillment draft untuk READY_TO_SHIP yang belum punya fulfillment
        $this->autoCreateFulfillments($store);

        $this->log($store, 'sync_orders', 'success', "Found " . count($orderSnList) . " orders, synced {$synced}.", [
            'time_from'     => $timeFrom,
            'time_to'       => $timeTo,
            'order_sn_list' => $orderSnList,
        ]);

        return [
            'found'         => count($orderSnList),
            'synced'        => $synced,
            'order_sn_list' => $orderSnList,
            'message'       => "Berhasil sync {$synced} dari " . count($orderSnList) . " order.",
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function upsertOrders(Store $store, array $details): int
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
        }

        $count = DB::transaction(function () use ($store, $details) {
            $n = 0;

            foreach ($details as $detail) {
                if (empty($detail['order_sn'])) continue;

                $order = MarketplaceOrder::updateOrCreate(
                    ['store_id' => $store->id, 'channel_order_id' => $detail['order_sn']],
                    [
                        'external_order_id' => $detail['order_sn'],
                        'order_date'       => ! empty($detail['create_time']) ? now()->setTimestamp((int) $detail['create_time'])->toDateTimeString() : now()->toDateTimeString(),
                        'booking_sn'       => $detail['booking_sn'] ?? null,
                        'order_status'     => $detail['order_status'] ?? null,
                        'buyer_username'   => $detail['buyer_username'] ?? null,
                        'payment_method'   => $detail['payment_method'] ?? null,
                        'shipping_carrier' => $detail['shipping_carrier'] ?? $detail['checkout_shipping_carrier'] ?? null,
                        'total_amount'     => $detail['total_amount'] ?? 0,
                        'currency'         => $detail['currency'] ?? 'IDR',
                        'ordered_at'       => ! empty($detail['create_time']) ? now()->setTimestamp((int) $detail['create_time']) : null,
                        'synced_at'        => now(),
                        'raw_json'         => $detail,
                    ]
                );

                $order->items()->delete();

                foreach (($detail['item_list'] ?? []) as $item) {
                    MarketplaceOrderItem::create([
                        'order_id'             => $order->id,
                        'marketplace_order_id' => $order->id,
                        'external_item_id'     => isset($item['item_id'])  ? (string) $item['item_id']  : null,
                        'external_model_id'    => isset($item['model_id']) ? (string) $item['model_id'] : null,
                        'item_name'            => $item['item_name'] ?? '-',
                        'item_sku'             => $item['item_sku']  ?? null,
                        'model_sku'            => $item['model_sku'] ?? null,
                        'variant_name'         => $item['model_name'] ?? null,
                        'qty'                  => (int) ($item['model_quantity_purchased'] ?? $item['active_qty'] ?? 0),
                        'price'                => $item['model_original_price'] ?? $item['model_discounted_price'] ?? 0,
                        'image_url'            => data_get($item, 'image_info.image_url'),
                        'raw_json'             => $item,
                    ]);
                }

                $n++;
            }

            $store->update(['last_synced_at' => now()]);

            return $n;
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON');
        }

        return $count;
    }

    private function autoCreateFulfillments(Store $store): void
    {
        $orders = MarketplaceOrder::where('store_id', $store->id)
            ->where('order_status', 'READY_TO_SHIP')
            ->whereDoesntHave('fulfillment')
            ->get();

        foreach ($orders as $order) {
            try {
                $this->fulfillment->createDraft($order);
            } catch (\Throwable $e) {
                Log::warning("Gagal buat fulfillment draft order #{$order->id}: " . $e->getMessage());
            }
        }
    }

    private function log(Store $store, string $action, string $status, string $message, array $payload = []): void
    {
        MarketplaceSyncLog::create([
            'store_id' => $store->id,
            'action'   => $action,
            'status'   => $status,
            'message'  => $message,
            'payload'  => $payload,
        ]);
    }
}
