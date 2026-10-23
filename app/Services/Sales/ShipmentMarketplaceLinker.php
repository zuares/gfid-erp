<?php

namespace App\Services\Sales;

use App\Models\MarketplaceOrder;
use App\Models\OrderFulfillment;
use App\Models\ShipmentOrderScan;
use App\Models\ShipmentOrderScanMarketplaceOrder;
use App\Services\OrderFulfillmentService;
use Illuminate\Support\Facades\DB;

/**
 * Persists the relationship between an operational shipment scan and its
 * marketplace order. Lookup stays in ShipmentController because it also
 * owns sales settings and booking promotion; this service owns the write so
 * every entry point uses the same idempotent link behavior.
 */
class ShipmentMarketplaceLinker
{
    public function __construct(
        protected OrderFulfillmentService $fulfillmentService,
    ) {}

    public function link(
        ShipmentOrderScan $scan,
        MarketplaceOrder $order,
        ?string $matchMethod = null,
        ?string $inputCode = null,
    ): OrderFulfillment {
        return $this->linkMany($scan, [$order], $matchMethod, $inputCode)->firstOrFail();
    }

    /**
     * Link one operational scan to all marketplace orders in a combined
     * parcel. The legacy fulfillment_id remains the primary link.
     */
    public function linkMany(
        ShipmentOrderScan $scan,
        iterable $orders,
        ?string $matchMethod = null,
        ?string $inputCode = null,
    ) {
        return DB::transaction(function () use ($scan, $orders, $matchMethod, $inputCode) {
            $orders = collect($orders)
                ->filter(fn ($order) => $order instanceof MarketplaceOrder)
                ->unique('id')
                ->values();

            if ($orders->isEmpty()) {
                return collect();
            }

            $fulfillments = collect();
            $linkedOrders = [];
            $primaryFulfillment = null;

            foreach ($orders as $order) {
                $fulfillment = OrderFulfillment::query()
                    ->where('marketplace_order_id', $order->id)
                    ->first();

                if (!$fulfillment) {
                    $fulfillment = $this->fulfillmentService->createDraft($order);
                }

                if ($fulfillment->status === OrderFulfillment::STATUS_CANCELLED) {
                    throw new \RuntimeException('Order marketplace sudah dibatalkan.');
                }

                ShipmentOrderScanMarketplaceOrder::updateOrCreate(
                    [
                        'shipment_order_scan_id' => $scan->id,
                        'marketplace_order_id' => $order->id,
                    ],
                    ['fulfillment_id' => $fulfillment->id]
                );

                $canonicalOrderNo = $order->channel_order_id
                    ?: $order->external_order_id
                    ?: $order->booking_sn
                    ?: $inputCode
                    ?: $scan->order_no;

                $linkedOrders[] = [
                    'invoice_id' => $order->id,
                    'order_no' => $canonicalOrderNo,
                    'shipping_awb_no' => $order->shipping_awb_no,
                    'booking_sn' => $order->booking_sn,
                    'store_id' => $order->store_id,
                    'store_name' => $order->store?->name,
                    'source' => 'marketplace',
                    'status' => $order->order_status,
                ];
                $primaryFulfillment ??= $fulfillment;
                $fulfillments->push($fulfillment);
            }

            $payload = is_array($scan->raw_payload) ? $scan->raw_payload : [];
            $payload['found'] = true;
            $payload['mode'] = 'auto_link';
            $payload['no'] = $scan->order_no;
            $payload['linked_source'] = 'marketplace_order';
            $payload['match_method'] = $matchMethod ?: ($payload['match_method'] ?? 'order_no');
            $payload['lookup_status'] = 'matched';
            $payload['label'] = 'Tertaut otomatis';
            $payload['order'] = $linkedOrders[0];
            if (count($linkedOrders) > 1) {
                $payload['combined_order_count'] = count($linkedOrders);
                $payload['combined_orders'] = $linkedOrders;
            }

            $scan->update([
                'fulfillment_id' => $primaryFulfillment?->id,
                'source' => 'marketplace',
                'match_method' => $payload['match_method'],
                'match_reason' => null,
                'matched_at' => now(),
                'raw_payload' => $payload,
            ]);

            return $fulfillments->map(fn ($fulfillment) => $fulfillment->fresh(['marketplaceOrder', 'lines']));
        });
    }

    public function markUnmatched(ShipmentOrderScan $scan, string $reason): void
    {
        $payload = is_array($scan->raw_payload) ? $scan->raw_payload : [];
        $payload['found'] = false;
        $payload['lookup_status'] = $reason;
        $payload['match_reason'] = $reason;
        $payload['label'] = 'Belum tertaut';

        $scan->update([
            'match_method' => null,
            'match_reason' => $reason,
            'matched_at' => null,
            'raw_payload' => $payload,
        ]);
    }
}
