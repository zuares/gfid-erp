<?php

namespace App\Services\Marketplace\Import\Adapters;

interface MpImportAdapterInterface
{
    public function channel(): string;

    /**
     * Parse file and return normalized shipments array:
     * [
     *  [
     *    'store_id' => 1,
     *    'channel' => 'shopee',
     *    'platform_order_id' => '...',
     *    'platform_shipment_id' => null,
     *    'tracking_no' => null,
     *    'marketplace_status' => '...',
     *    'status_norm' => 'in_transit|delivered|canceled|unknown',
     *    'order_created_at' => 'Y-m-d H:i:s'|null,
     *    'paid_at' => ...,
     *    'shipped_at' => ...,
     *    'delivered_at' => ...,
     *    'completed_at' => ...,
     *    'currency' => 'IDR',
     *    'order_subtotal' => 0,
     *    'discount_total' => 0,
     *    'shipping_fee' => 0,
     *    'grand_total' => 0,
     *    'platform_fee_total' => 0,
     *    'refund_total' => 0,
     *    'net_payout_actual' => 0,
     *    'released_at' => null,
     *    'raw_payload' => [...],
     *    'items' => [
     *      [
     *        'sku_code' => '...',
     *        'sku_parent' => null,
     *        'product_name' => '...',
     *        'variant_name' => '...',
     *        'qty' => 1,
     *        'unit_price' => 0,
     *        'subtotal' => 0,
     *        'raw_line' => [...]
     *      ],
     *    ]
     *  ]
     * ]
     */
    public function parse(string $path, int $storeId, string $sourceFile): array;
}
