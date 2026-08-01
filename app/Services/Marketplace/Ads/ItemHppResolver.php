<?php

namespace App\Services\Marketplace\Ads;

use App\Models\Item;
use App\Models\StorefrontProduct;
use Illuminate\Support\Collection;

/**
 * Resolves the HPP used by Ads from an internal product item.
 *
 * A storefront product can have multiple internal items behind its color/size
 * variants. Ads should be mapped to the product, not to one arbitrary variant,
 * so the effective HPP is the average of all related positive HPP values.
 */
class ItemHppResolver
{
    /** @var array<int, array<string, mixed>> */
    private array $cache = [];

    public function resolve(Item|int|null $item): float
    {
        return (float) $this->summary($item)['hpp'];
    }

    /**
     * @return array{
     *   item: ?Item,
     *   hpp: float,
     *   hpp_source: string,
     *   variant_count: int,
     *   product_id: ?int,
     *   product_name: ?string,
     *   representative_item_id: ?int,
     *   item_ids: array<int>
     * }
     */
    public function summary(Item|int|null $item): array
    {
        $resolvedItem = $item instanceof Item ? $item : ($item ? Item::find($item) : null);
        $empty = [
            'item' => $resolvedItem,
            'hpp' => 0.0,
            'hpp_source' => 'none',
            'variant_count' => 0,
            'product_id' => null,
            'product_name' => null,
            'representative_item_id' => $resolvedItem?->id,
            'item_ids' => [],
        ];

        if (! $resolvedItem) {
            return $empty;
        }

        $itemId = (int) $resolvedItem->id;
        if (isset($this->cache[$itemId])) {
            return $this->cache[$itemId];
        }

        $product = StorefrontProduct::query()
            ->with('variantItemMappings:id,product_id,item_id')
            ->where(function ($query) use ($itemId) {
                $query->where('item_id', $itemId)
                    ->orWhereHas('variantItemMappings', fn ($mapping) => $mapping->where('item_id', $itemId));
            })
            ->orderByRaw('CASE WHEN item_id = ? THEN 0 ELSE 1 END', [$itemId])
            ->orderBy('id')
            ->first();

        if (! $product) {
            $baseHpp = $this->baseHpp($resolvedItem);
            return $this->cache[$itemId] = array_merge($empty, [
                'hpp' => $baseHpp,
                'hpp_source' => $baseHpp > 0 ? 'item' : 'none',
            ]);
        }

        $variantIds = $product->variantItemMappings
            ->pluck('item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Jika produk belum memakai tabel mapping variant, gunakan item utama.
        $groupItemIds = $variantIds->isNotEmpty()
            ? $variantIds
            : collect([$product->item_id ?: $itemId]);

        $costItems = Item::query()
            ->whereIn('id', $groupItemIds->all())
            ->get(['id', 'hpp', 'base_unit_cost']);
        $costs = $costItems
            ->map(fn (Item $candidate) => $this->baseHpp($candidate))
            ->filter(fn (float $hpp) => $hpp > 0)
            ->values();
        $avgHpp = $costs->isNotEmpty() ? (float) $costs->avg() : $this->baseHpp($resolvedItem);

        return $this->cache[$itemId] = [
            'item' => $resolvedItem,
            'hpp' => $avgHpp,
            'hpp_source' => $variantIds->isNotEmpty() && $costs->isNotEmpty() ? 'variant_average' : ($avgHpp > 0 ? 'item' : 'none'),
            'variant_count' => $variantIds->count(),
            'product_id' => (int) $product->id,
            'product_name' => $product->name,
            'representative_item_id' => (int) ($product->item_id ?: $variantIds->first() ?: $itemId),
            'item_ids' => $groupItemIds->all(),
        ];
    }

    /**
     * Search product-level choices for the Ads mapping modal.
     * The returned id is a stable internal item representative, while HPP is
     * the average for the complete product group.
     */
    public function searchProducts(?string $query, int $limit = 15): Collection
    {
        $query = trim((string) $query);
        $like = '%' . $query . '%';

        return StorefrontProduct::query()
            ->with([
                'item:id,name,code,hpp,base_unit_cost',
                'variantItemMappings.item:id,name,code,hpp,base_unit_cost',
            ])
            ->when($query !== '', function ($builder) use ($like) {
                $builder->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhereHas('item', function ($item) use ($like) {
                            $item->where('name', 'like', $like)->orWhere('code', 'like', $like);
                        })
                        ->orWhereHas('variantItemMappings.item', function ($item) use ($like) {
                            $item->where('name', 'like', $like)->orWhere('code', 'like', $like);
                        });
                });
            })
            ->where(function ($builder) {
                $builder->whereNotNull('item_id')
                    ->orWhereHas('variantItemMappings', fn ($mapping) => $mapping->whereNotNull('item_id'));
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (StorefrontProduct $product) {
                $representativeId = (int) ($product->item_id ?: $product->variantItemMappings->pluck('item_id')->filter()->first());
                if (! $representativeId) {
                    return null;
                }

                $summary = $this->summary($representativeId);
                $representative = $summary['item'];

                return [
                    'id' => $representativeId,
                    'name' => $product->name,
                    'code' => $representative?->code ?: ('PRODUCT-' . $product->id),
                    'hpp' => round((float) $summary['hpp'], 2),
                    'hpp_source' => $summary['hpp_source'],
                    'variant_count' => $summary['variant_count'],
                    'product_id' => (int) $product->id,
                ];
            })
            ->filter()
            ->unique(fn (array $row) => (string) $row['id'])
            ->values();
    }

    private function baseHpp(Item $item): float
    {
        $hpp = (float) ($item->hpp ?? 0);
        return $hpp > 0 ? $hpp : (float) ($item->base_unit_cost ?? 0);
    }
}
