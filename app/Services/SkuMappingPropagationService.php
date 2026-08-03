<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\MarketplaceOrderItem;
use App\Models\OrderFulfillment;
use App\Models\OrderFulfillmentLine;
use App\Models\SkuMapping;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Propagate SKU Mapping changes to denormalized marketplace data.
 *
 * Mapping is the source of truth, but order items, fulfillment lines, and ad
 * campaigns also store resolved item IDs for fast operational reads. This
 * service keeps those projections in sync after a mapping mutation.
 */
class SkuMappingPropagationService
{
    public function __construct(
        private readonly MarketplaceIssueService $issueService,
        private readonly AdItemMapper $adItemMapper,
    ) {}

    /**
     * @param  array<int, array{marketplace_sku: string, channel_code: ?string}>  $keys
     * @return array{order_items: int, fulfillment_lines: int, ad_campaigns: int}
     */
    public function propagate(array $keys): array
    {
        $keys = collect($keys)
            ->map(fn (array $key) => [
                'marketplace_sku' => trim((string) ($key['marketplace_sku'] ?? '')),
                'channel_code' => $this->normalizeChannel($key['channel_code'] ?? null),
            ])
            ->filter(fn (array $key) => $key['marketplace_sku'] !== '')
            ->unique(fn (array $key) => $key['marketplace_sku'].'|'.($key['channel_code'] ?? '*'))
            ->values();

        if ($keys->isEmpty()) {
            return ['order_items' => 0, 'fulfillment_lines' => 0, 'ad_campaigns' => 0];
        }

        $skuValues = $keys->pluck('marketplace_sku')->unique()->values();
        $orderItems = $this->resolveOrderItems($keys, $skuValues);
        $fulfillmentLines = $this->syncPendingFulfillmentLines($orderItems, $skuValues);
        $adCampaigns = $this->refreshAdCampaigns($orderItems);

        return [
            'order_items' => $orderItems->count(),
            'fulfillment_lines' => $fulfillmentLines,
            'ad_campaigns' => $adCampaigns,
        ];
    }

    private function resolveOrderItems(Collection $keys, Collection $skuValues): Collection
    {
        $resolved = collect();

        try {
            MarketplaceOrderItem::query()
                ->with(['order.store.channel', 'legacyOrder.store.channel'])
                ->where(function ($query) use ($skuValues) {
                    $query->whereIn('model_sku', $skuValues)
                        ->orWhereIn('item_sku', $skuValues)
                        ->orWhereIn('marketplace_sku', $skuValues);
                })
                ->chunkById(300, function ($items) use ($keys, $resolved) {
                    foreach ($items as $item) {
                        $order = $item->order ?: $item->legacyOrder;
                        $channelCode = $this->normalizeChannel($order?->store?->channel?->code);
                        $sku = $this->sourceSku($item);

                        if (! $this->keyApplies($keys, $sku, $channelCode)) {
                            continue;
                        }

                        $item->update($this->issueService->buildMappingAttributes(
                            modelSku: $item->model_sku,
                            itemSku: $item->item_sku,
                            externalSku: $item->external_sku,
                            channelCode: $channelCode,
                            itemName: $item->item_name,
                            variantName: $item->variant_name,
                        ));
                        $resolved->push($item->fresh(['order.store.channel', 'legacyOrder.store.channel']));
                    }
                });
        } catch (\Throwable $e) {
            Log::warning('Propagasi SKU mapping ke order items gagal: '.$e->getMessage());
        }

        return $resolved;
    }

    private function syncPendingFulfillmentLines(Collection $orderItems, Collection $skuValues): int
    {
        $orderItemIds = $orderItems->pluck('id')->filter()->unique()->values();
        $updated = 0;

        try {
            OrderFulfillmentLine::query()
                ->with(['fulfillment.order.store.channel'])
                ->where('substituted', false)
                ->whereHas('fulfillment', function ($query) {
                    $query->whereIn('status', [
                        OrderFulfillment::STATUS_DRAFT,
                        OrderFulfillment::STATUS_PENDING_REVIEW,
                    ]);
                })
                ->where(function ($query) use ($orderItemIds, $skuValues) {
                    if ($orderItemIds->isNotEmpty()) {
                        $query->whereIn('marketplace_order_item_id', $orderItemIds);
                    }
                    $query->orWhereIn('marketplace_sku', $skuValues);
                })
                ->chunkById(300, function ($lines) use ($orderItems, &$updated) {
                    foreach ($lines as $line) {
                        $channelCode = $this->normalizeChannel(
                            $line->fulfillment?->order?->store?->channel?->code
                        );
                        $orderItem = $orderItems->firstWhere('id', $line->marketplace_order_item_id);
                        $itemId = $orderItem?->internal_item_id
                            ?? SkuMapping::resolve($line->marketplace_sku, $channelCode);
                        $stockRow = $this->stockFor($itemId, $line->fulfillment?->warehouse_id);

                        $line->update([
                            'item_id' => $itemId,
                            'lot_id' => $stockRow?->lot_id,
                            'stock_available' => (int) ($stockRow?->qty ?? 0),
                            'qty_fulfilled' => $itemId ? $line->qty_ordered : 0,
                        ]);
                        $updated++;
                    }
                });
        } catch (\Throwable $e) {
            Log::warning('Propagasi SKU mapping ke fulfillment lines gagal: '.$e->getMessage());
        }

        return $updated;
    }

    private function refreshAdCampaigns(Collection $orderItems): int
    {
        $storeIds = $orderItems
            ->map(fn ($item) => ($item->order ?: $item->legacyOrder)?->store_id)
            ->filter()
            ->unique()
            ->values();
        $updated = 0;

        foreach ($storeIds as $storeId) {
            try {
                $updated += $this->adItemMapper->backfill((int) $storeId);
            } catch (\Throwable $e) {
                Log::warning("Propagasi SKU mapping ke campaign iklan store {$storeId} gagal: ".$e->getMessage());
            }
        }

        return $updated;
    }

    private function stockFor(?int $itemId, ?int $warehouseId): ?InventoryStock
    {
        if (! $itemId || ! $warehouseId) {
            return null;
        }

        return InventoryStock::query()
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->orderByDesc('qty')
            ->first();
    }

    private function sourceSku(MarketplaceOrderItem $item): ?string
    {
        return $item->model_sku ?: ($item->item_sku ?: ($item->marketplace_sku ?: $item->external_sku));
    }

    private function keyApplies(Collection $keys, ?string $sku, ?string $channelCode): bool
    {
        if (! $sku) {
            return false;
        }

        return $keys->contains(function (array $key) use ($sku, $channelCode) {
            return $key['marketplace_sku'] === $sku
                && ($key['channel_code'] === null || $key['channel_code'] === $channelCode);
        });
    }

    private function normalizeChannel(?string $channelCode): ?string
    {
        $channelCode = strtolower(trim((string) $channelCode));

        return $channelCode !== '' ? $channelCode : null;
    }
}
