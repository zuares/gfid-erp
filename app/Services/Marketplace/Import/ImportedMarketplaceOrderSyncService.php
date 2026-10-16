<?php

namespace App\Services\Marketplace\Import;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MpShipment;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
use App\Services\MarketplaceIssueService;

class ImportedMarketplaceOrderSyncService
{
    public function __construct(
        protected MarketplaceIssueService $issueService,
        protected MarketplaceFinancialDataQualityService $financialQuality,
    ) {}

    /**
     * Mirror a manually imported shipment into the order tables used by the
     * sales dashboard. API-owned orders are only linked; their richer payload
     * and item snapshot must not be overwritten by a file import.
     */
    public function sync(
        MpShipment $shipment,
        array $normalized,
        array $items,
        string $batchId,
        string $timestamp,
        bool $replaceSnapshot = true,
    ): array {
        $orderKey = trim((string) ($normalized['platform_order_id'] ?? ''));
        if ($orderKey === '') {
            return ['order_id' => null, 'created' => false, 'updated' => false, 'linked' => false];
        }

        $order = MarketplaceOrder::query()
            ->where('store_id', (int) $shipment->store_id)
            ->where('channel_order_id', $orderKey)
            ->first();

        if ($order && (! $this->isImportOwned($order) || ! $replaceSnapshot)) {
            if (blank($order->purchase_channel) && filled($normalized['purchase_channel'] ?? null)) {
                $order->forceFill(['purchase_channel' => $normalized['purchase_channel']])->save();
            }

            return [
                'order_id' => (int) $order->id,
                'created' => false,
                'updated' => false,
                'linked' => true,
            ];
        }

        $shipment->loadMissing('store.channel');
        $channelCode = $shipment->store?->channel?->code ?: $shipment->channel;

        // A single marketplace order may contain multiple shipments/packages.
        // Rebuild the order item snapshot from every package so importing the
        // later package cannot erase the items from the earlier one.
        $packageItems = MpShipment::query()
            ->with('items')
            ->where('store_id', (int) $shipment->store_id)
            ->where('platform_order_id', $orderKey)
            ->get()
            ->flatMap(fn (MpShipment $package) => $package->items->map(fn ($item) => [
                'sku_code' => $item->sku_code,
                'sku_parent' => $item->sku_parent,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'qty' => (int) $item->qty,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'raw_line' => $item->raw_line,
            ]))
            ->values()
            ->all();
        if ($packageItems !== []) {
            $items = $packageItems;
        }

        $payload = $this->orderPayload($shipment, $normalized, $items, $batchId, $timestamp);

        if ($order) {
            $payload['raw_json'] = array_merge((array) $order->raw_json, (array) $payload['raw_json']);
            $payload['meta'] = array_merge((array) $order->meta, (array) $payload['meta']);
            $order->fill($payload)->save();
            $updated = true;
            $created = false;
        } else {
            $order = MarketplaceOrder::create($payload);
            $updated = false;
            $created = true;
        }

        // Imported orders are snapshots. Refresh only snapshots created by
        // this import-owned path; API-owned orders returned above are intact.
        MarketplaceOrderItem::query()
            ->where(function ($query) use ($order): void {
                $query->where('marketplace_order_id', $order->id)
                    ->orWhere('order_id', $order->id);
            })
            ->delete();

        foreach ($items as $lineNo => $item) {
            $qty = max(0, (int) ($item['qty'] ?? 0));
            $originalPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $lineNet = max(0, (float) ($item['subtotal'] ?? 0));
            if ($lineNet <= 0 && $qty > 0) {
                $lineNet = $originalPrice * $qty;
            }

            $priceAfterDiscount = $qty > 0 ? $lineNet / $qty : $originalPrice;
            $lineGross = $originalPrice * $qty;
            $lineDiscount = max(0, $lineGross - $lineNet);
            $sku = trim((string) ($item['sku_code'] ?? $item['sku_parent'] ?? '')) ?: null;
            $name = trim((string) ($item['product_name'] ?? '')) ?: null;
            $variant = trim((string) ($item['variant_name'] ?? '')) ?: null;
            $mapping = $this->issueService->buildMappingAttributes(
                $sku,
                $sku,
                $sku,
                $channelCode,
                $name,
                $variant,
            );
            $hpp = isset($mapping['hpp_snapshot']) && (float) $mapping['hpp_snapshot'] > 0
                ? (float) $mapping['hpp_snapshot']
                : null;

            MarketplaceOrderItem::create(array_merge([
                'order_id' => $order->id,
                'marketplace_order_id' => $order->id,
                'line_no' => $lineNo + 1,
                'external_item_id' => sha1(json_encode([
                    $sku,
                    $name,
                    $variant,
                    $originalPrice,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                'external_sku' => $sku,
                'item_code_snapshot' => $item['sku_parent'] ?? null,
                'item_name_snapshot' => $name,
                'variant_snapshot' => $variant,
                'item_name' => $name,
                'item_sku' => $sku,
                'model_sku' => $item['sku_parent'] ?? null,
                'variant_name' => $variant,
                'marketplace_sku' => $sku,
                'qty' => $qty,
                'price' => $priceAfterDiscount,
                'price_original' => $originalPrice,
                'price_after_discount' => $priceAfterDiscount,
                'line_discount' => $lineDiscount,
                'line_gross_amount' => $lineGross,
                'line_net_amount' => $lineNet,
                // Kolom legacy ini NOT NULL. Status semantik tetap dibaca dari
                // hpp_snapshot/data_status, sehingga 0 di sini bukan dianggap HPP valid.
                'hpp_unit_snapshot' => $hpp ?? 0,
                'hpp_total_snapshot' => $hpp !== null ? $hpp * $qty : 0,
                'raw_json' => [
                    'source' => 'marketplace_shipment_import',
                    'source_file' => $normalized['source_file'] ?? null,
                    'promotion_breakdown' => $item['promotion_breakdown'] ?? null,
                    'raw_line' => $item['raw_line'] ?? null,
                ],
            ], $mapping));
        }

        $this->financialQuality->refreshOrder($order->fresh(['settlement', 'items']));

        return [
            'order_id' => (int) $order->id,
            'created' => $created,
            'updated' => $updated,
            'linked' => false,
        ];
    }

    private function orderPayload(
        MpShipment $shipment,
        array $normalized,
        array $items,
        string $batchId,
        string $timestamp,
    ): array {
        $raw = (array) ($normalized['raw_payload'] ?? []);
        $promotion = (array) ($normalized['promotion_breakdown']
            ?? ($raw['promotion_breakdown'] ?? []));
        $status = $this->canonicalStatus($normalized['status_norm'] ?? null);
        $orderDate = $normalized['order_created_at']
            ?: $normalized['shipped_at']
            ?: $normalized['delivered_at']
            ?: $timestamp;
        $subtotal = (float) ($normalized['order_subtotal'] ?? 0);
        if ($subtotal <= 0) {
            $subtotal = array_sum(array_map(
                fn (array $item): float => max(0, (float) ($item['subtotal'] ?? 0)),
                $items,
            ));
        }

        $raw['source'] = 'marketplace_shipment_import';
        $raw['channel'] = $normalized['channel'] ?? $shipment->channel;
        $raw['purchase_channel'] = $normalized['purchase_channel'] ?? null;
        $raw['source_file'] = $normalized['source_file'] ?? null;
        $raw['import_batch_id'] = $batchId;
        $raw['promotion_breakdown'] = $promotion;

        // marketplace_orders has legacy order-level columns. Keep the
        // canonical breakdown in raw_json as well, while mapping the values
        // that the sales dashboard can aggregate directly.
        $voucherStore = (float) ($promotion['voucher_store'] ?? 0);
        $voucherPlatform = (float) ($promotion['voucher_platform'] ?? 0);
        $otherDiscount = (float) ($promotion['other_discount'] ?? 0);

        return [
            'store_id' => (int) $shipment->store_id,
            'purchase_channel' => $normalized['purchase_channel'] ?? null,
            'channel_order_id' => (string) $normalized['platform_order_id'],
            'external_order_id' => (string) $normalized['platform_order_id'],
            'order_date' => $orderDate,
            'status' => strtolower($status),
            'order_status' => $status,
            'buyer_username' => $raw['buyer_username'] ?? null,
            'buyer_name' => $raw['recipient'] ?? null,
            'buyer_phone' => $raw['phone'] ?? null,
            'shipping_address' => trim(implode(' ', array_filter([
                $raw['address'] ?? null,
                $raw['additional_address'] ?? null,
            ]))) ?: null,
            'shipping_city' => $raw['city'] ?? null,
            'shipping_province' => $raw['province'] ?? null,
            'shipping_courier_code' => $raw['shipping_provider_name'] ?? null,
            'shipping_awb_no' => $normalized['tracking_no'] ?? null,
            'subtotal_items' => $subtotal,
            'shipping_fee_customer' => (float) ($normalized['shipping_fee'] ?? 0),
            'shipping_discount_platform' => (float) ($promotion['shipping_discount_platform'] ?? 0),
            'voucher_discount' => $voucherStore + $voucherPlatform,
            'other_discount' => $otherDiscount,
            'total_paid_customer' => (float) ($normalized['grand_total'] ?? 0),
            'total_amount' => (float) ($normalized['grand_total'] ?? 0),
            'currency' => $normalized['currency'] ?? 'IDR',
            'payment_method' => $raw['payment_method'] ?? null,
            'payment_status' => ! empty($normalized['paid_at']) ? 'paid' : 'unpaid',
            'payment_date' => $normalized['paid_at'] ?? null,
            'ordered_at' => $orderDate,
            'paid_at' => $normalized['paid_at'] ?? null,
            'shipped_at' => $normalized['shipped_at'] ?? null,
            'delivered_at' => $normalized['delivered_at'] ?? null,
            'completed_at' => $normalized['completed_at'] ?? null,
            'synced_at' => $timestamp,
            'raw_json' => $raw,
            'raw_payload_json' => json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'meta' => [
                'source_type' => 'marketplace_shipment_import',
                'source_file' => $normalized['source_file'] ?? null,
                'import_batch_id' => $batchId,
                'marketplace_status' => $normalized['marketplace_status'] ?? null,
            ],
        ];
    }

    private function isImportOwned(MarketplaceOrder $order): bool
    {
        $raw = (array) $order->raw_json;
        $meta = (array) $order->meta;

        return in_array((string) ($raw['source'] ?? ''), ['marketplace_import', 'marketplace_crm_import', 'marketplace_shipment_import'], true)
            || in_array((string) ($meta['source_type'] ?? ''), ['marketplace_import', 'marketplace_crm_import', 'marketplace_shipment_import'], true)
            || (string) ($meta['crm_source'] ?? '') === 'marketplace_import';
    }

    private function canonicalStatus(?string $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'delivered' => 'COMPLETED',
            'canceled', 'cancelled' => 'CANCELLED',
            'in_transit' => 'SHIPPED',
            'processing' => 'PACKED',
            default => 'NEW',
        };
    }
}
