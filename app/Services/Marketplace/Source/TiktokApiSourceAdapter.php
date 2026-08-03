<?php

namespace App\Services\Marketplace\Source;

use Carbon\Carbon;

class TiktokApiSourceAdapter implements MarketplaceSourceAdapterInterface
{
    public function channel(): string
    {
        return 'tiktok';
    }

    public function normalize(array $payload, int $storeId, string $sourceType = 'api'): array
    {
        $orders = data_get($payload, 'data.orders')
            ?? data_get($payload, 'data.order_list')
            ?? data_get($payload, 'response.order_list')
            ?? data_get($payload, 'order_list')
            ?? (isset($payload['order_id']) || isset($payload['order_sn']) ? [$payload] : []);

        $out = [];

        foreach ($this->listify($orders) as $order) {
            if (! is_array($order)) {
                continue;
            }

            $orderId = $this->string($order['order_id'] ?? $order['order_sn'] ?? $order['id'] ?? null);
            if ($orderId === '') {
                continue;
            }

            $packages = $order['packages'] ?? $order['package_list'] ?? $order['shipments'] ?? [null];
            foreach ($this->listify($packages) as $package) {
                $package = is_array($package) ? $package : [];
                $items = $package['items'] ?? $package['sku_list'] ?? $order['items'] ?? $order['sku_list'] ?? [];
                $tracking = $this->string($package['tracking_number'] ?? $package['tracking_no'] ?? $order['tracking_number'] ?? null);
                $packageId = $this->string($package['package_id'] ?? $package['shipment_id'] ?? $package['id'] ?? null);

                $normalizedItems = [];
                foreach ($this->listify($items) as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $qty = (int) ($item['quantity'] ?? $item['qty'] ?? $item['model_quantity_purchased'] ?? 0);
                    $unitPrice = (float) ($item['unit_price'] ?? $item['price'] ?? $item['sale_price'] ?? 0);
                    $subtotal = (float) ($item['subtotal'] ?? $item['line_total'] ?? ($qty * $unitPrice));

                    $normalizedItems[] = [
                        'sku_code' => $this->string($item['seller_sku'] ?? $item['sku'] ?? $item['sku_code'] ?? $item['model_sku'] ?? null) ?: null,
                        'sku_parent' => $this->string($item['sku_parent'] ?? null) ?: null,
                        'product_name' => $this->string($item['product_name'] ?? $item['item_name'] ?? null) ?: null,
                        'variant_name' => $this->string($item['variation'] ?? $item['variant_name'] ?? $item['model_name'] ?? null) ?: null,
                        'qty' => $qty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'raw_line' => $item,
                    ];
                }

                $out[] = [
                    'store_id' => $storeId,
                    'channel' => $this->channel(),
                    'platform_order_id' => $orderId,
                    'platform_shipment_id' => $packageId ?: null,
                    'tracking_no' => $tracking ?: null,
                    'marketplace_status' => $this->string($order['status'] ?? $order['order_status'] ?? null) ?: null,
                    'status_norm' => $this->normStatus($order['status'] ?? $order['order_status'] ?? null),
                    'order_created_at' => $this->timestamp($order['create_time'] ?? $order['created_at'] ?? $order['create_time_unix'] ?? null),
                    'paid_at' => $this->timestamp($order['paid_time'] ?? $order['paid_at'] ?? null),
                    'shipped_at' => $this->timestamp($package['shipped_at'] ?? $order['shipped_at'] ?? null),
                    'delivered_at' => $this->timestamp($package['delivered_at'] ?? $order['delivered_at'] ?? null),
                    'completed_at' => $this->timestamp($order['completed_at'] ?? $order['finished_at'] ?? null),
                    'source_updated_at' => $this->timestamp($order['update_time'] ?? $order['updated_at'] ?? null),
                    'total_qty' => array_sum(array_map(fn ($item) => (int) ($item['qty'] ?? 0), $normalizedItems)),
                    'order_subtotal' => (float) ($order['subtotal'] ?? $order['item_subtotal'] ?? array_sum(array_map(fn ($item) => (float) ($item['subtotal'] ?? 0), $normalizedItems))),
                    'discount_total' => (float) ($order['discount_total'] ?? $order['discount'] ?? 0),
                    'shipping_fee' => (float) ($order['shipping_fee'] ?? $order['shipping_cost'] ?? 0),
                    'grand_total' => (float) ($order['total_amount'] ?? $order['grand_total'] ?? 0),
                    'currency' => $this->string($order['currency'] ?? 'IDR') ?: 'IDR',
                    'source_file' => null,
                    'raw_payload' => [
                        'source' => $sourceType,
                        'order' => $order,
                        'package' => $package,
                    ],
                    'items' => $normalizedItems,
                ];
            }
        }

        return $out;
    }

    private function string(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function listify(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return $value === null ? [] : [$value];
        }

        return array_is_list($value) ? $value : [$value];
    }

    private function timestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value, 'Asia/Jakarta')->format('Y-m-d H:i:s');
        }

        try {
            return Carbon::parse((string) $value, 'Asia/Jakarta')->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normStatus(mixed $status): string
    {
        $status = strtolower($this->string($status));

        return match (true) {
            str_contains($status, 'deliver'), str_contains($status, 'complete'), str_contains($status, 'terkirim') => 'delivered',
            str_contains($status, 'cancel'), str_contains($status, 'return') => 'canceled',
            str_contains($status, 'ship'), str_contains($status, 'transit'), str_contains($status, 'dikirim') => 'in_transit',
            default => 'unknown',
        };
    }
}
