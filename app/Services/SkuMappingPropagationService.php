<?php

namespace App\Services;

use App\Models\InventoryStock;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\OrderFulfillment;
use App\Models\OrderFulfillmentLine;
use App\Models\ProductNameSkuMapping;
use App\Models\SkuMapping;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
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
        private readonly MarketplaceFinancialDataQualityService $qualityService,
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
        $this->refreshOrderQuality($orderItems);
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
                        ->orWhereIn('marketplace_sku', $skuValues)
                        ->orWhereIn('external_sku', $skuValues);
                })
                ->chunkById(300, function ($items) use ($keys, $resolved) {
                    foreach ($items as $item) {
                        $order = $item->order ?: $item->legacyOrder;
                        $channelCode = $this->normalizeChannel($order?->store?->channel?->code);
                        $sku = $this->sourceSku($item);

                        if (! $this->keyApplies($keys, $sku, $channelCode)) {
                            continue;
                        }

                        // Gunakan SKU yang membuat item cocok dengan mapping.
                        // Ini penting ketika marketplace_sku berbeda dari model_sku/item_sku.
                        $item->update($this->issueService->buildMappingAttributes(
                            modelSku: $sku,
                            itemSku: null,
                            externalSku: null,
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

        // Order lama yang dibuat saat marketplace tidak mengirim Seller SKU
        // tidak bisa ditemukan melalui sku_values di atas. Jika mapping sudah
        // menunjuk ke item internal yang sama, isi SKU hanya ketika kombinasi
        // item+channel memiliki satu kandidat SKU yang tidak ambigu.
        $resolved = $resolved->merge($this->resolveEmptyOrderItems($keys));

        return $resolved;
    }

    private function resolveEmptyOrderItems(Collection $keys): Collection
    {
        $skuValues = $keys->pluck('marketplace_sku')->unique()->values();
        $mappedItemIds = collect();

        foreach ($keys as $key) {
            $sku = (string) $key['marketplace_sku'];
            $itemId = SkuMapping::resolve($sku, $key['channel_code'] ?? null);

            if (! $itemId) {
                continue;
            }

            $mappedItemIds->push($itemId);
        }

        $skuCandidates = [];
        SkuMapping::query()
            ->whereIn('item_id', $mappedItemIds->unique()->all())
            ->get(['marketplace_sku', 'channel_code', 'item_id'])
            ->each(function (SkuMapping $mapping) use (&$skuCandidates): void {
                $compound = ($this->normalizeChannel($mapping->channel_code) ?? '*').'|'.$mapping->item_id;
                $skuCandidates[$compound] ??= [];
                $skuCandidates[$compound][] = (string) $mapping->marketplace_sku;
            });

        $skuCandidates = collect($skuCandidates)->map(
            fn (array $skus): array => array_values(array_unique($skus))
        );

        $itemIds = $skuCandidates->keys()
            ->map(fn (string $key): int => (int) last(explode('|', $key)))
            ->unique()
            ->values();

        $nameVariantMappings = ProductNameSkuMapping::query()
            ->whereIn('marketplace_sku', $skuValues)
            ->get();
        $byNameVariant = $nameVariantMappings
            ->groupBy(fn (ProductNameSkuMapping $mapping): string =>
                $this->nameVariantKey($mapping->item_name, $mapping->variant_name, $mapping->channel_code)
            )
            ->map(function (Collection $mappings): ?string {
                $skus = $mappings->pluck('marketplace_sku')->filter()->unique()->values();

                return $skus->count() === 1 ? (string) $skus->first() : null;
            });

        if ($itemIds->isEmpty() && $byNameVariant->isEmpty()) {
            return collect();
        }

        $resolved = collect();
        $query = MarketplaceOrderItem::query()
            ->with(['order.store.channel', 'legacyOrder.store.channel'])
            ->where(function ($query) use ($itemIds, $byNameVariant, $nameVariantMappings): void {
                if ($itemIds->isNotEmpty()) {
                    $query->whereIn('internal_item_id', $itemIds->all());
                }

                if ($byNameVariant->isNotEmpty()) {
                    $query->orWhereIn('item_name', $nameVariantMappings->pluck('item_name')->unique()->all());
                }
            })
            ->where(function ($query): void {
                foreach (['marketplace_sku', 'model_sku', 'item_sku', 'external_sku'] as $column) {
                    $query->where(function ($empty) use ($column): void {
                        $empty->whereNull($column)->orWhere($column, '');
                    });
                }
            });

        $query->chunkById(300, function ($items) use (&$resolved, $skuCandidates, $byNameVariant): void {
            foreach ($items as $item) {
                $order = $item->order ?: $item->legacyOrder;
                $channel = $this->normalizeChannel($order?->store?->channel?->code);
                $sku = null;

                if ($item->internal_item_id) {
                    $sku = $this->uniqueCandidateSku(
                        $skuCandidates->get(($channel ?: '*').'|'.$item->internal_item_id)
                            ?? $skuCandidates->get('*|'.$item->internal_item_id)
                    );
                }

                if (! $sku) {
                    $sku = $byNameVariant->get(
                        $this->nameVariantKey($item->item_name, $item->variant_name, $channel)
                    ) ?? $byNameVariant->get(
                        $this->nameVariantKey($item->item_name, $item->variant_name, null)
                    );
                }

                if (! $sku) {
                    continue;
                }

                $item->update([
                    'model_sku' => $sku,
                    'marketplace_sku' => $sku,
                ]);
                $this->issueService->resolveItem($item->fresh(), $channel);
                $resolved->push($item->fresh(['order.store.channel', 'legacyOrder.store.channel']));
            }
        });

        return $resolved;
    }

    private function uniqueCandidateSku(?array $skus): ?string
    {
        $skus = array_values(array_unique(array_filter($skus ?? [])));

        return count($skus) === 1 ? $skus[0] : null;
    }

    private function nameVariantKey(?string $itemName, ?string $variantName, ?string $channelCode): string
    {
        return ProductNameSkuMapping::makeHash($itemName, $variantName)
            .'|'.($this->normalizeChannel($channelCode) ?? '*');
    }

    /**
     * Mapping/HPP pada order item adalah sumber penilaian parent order.
     * Refresh parent setelah propagasi agar alasan issue lama tidak tertinggal
     * walaupun item sudah valid.
     */
    private function refreshOrderQuality(Collection $orderItems): void
    {
        $orderIds = $orderItems
            ->map(fn (MarketplaceOrderItem $item) => ($item->order ?: $item->legacyOrder)?->getKey())
            ->filter()
            ->unique()
            ->values();

        if ($orderIds->isEmpty()) {
            return;
        }

        try {
            MarketplaceOrder::query()
                ->whereIn('id', $orderIds)
                ->with(['settlement', 'items', 'legacyItems'])
                ->chunkById(100, function ($orders): void {
                    foreach ($orders as $order) {
                        try {
                            $this->qualityService->refreshOrder($order);
                        } catch (\Throwable $e) {
                            Log::warning("Refresh kualitas order #{$order->id} setelah propagasi mapping gagal: {$e->getMessage()}");
                        }
                    }
                });
        } catch (\Throwable $e) {
            Log::warning('Refresh kualitas parent order setelah propagasi mapping gagal: '.$e->getMessage());
        }
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
        $marketplaceSku = trim((string) $item->getRawOriginal('marketplace_sku'));

        return $marketplaceSku !== ''
            ? $marketplaceSku
            : ($item->model_sku ?: ($item->item_sku ?: $item->external_sku));
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
