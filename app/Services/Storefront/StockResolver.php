<?php

namespace App\Services\Storefront;

use App\Models\Item;
use App\Models\StorefrontProduct;
use App\Models\StorefrontProductSize;
use App\Models\StorefrontProductVariant;
use App\Models\StorefrontVariantItemMapping;

class StockResolver
{
    public function forMapping(StorefrontVariantItemMapping $mapping): int
    {
        if ($mapping->item_id) {
            return $this->forItem($mapping->item ?? Item::find($mapping->item_id));
        }

        if ($mapping->stock_override !== null) {
            return max(0, (int) $mapping->stock_override);
        }

        return 0;
    }

    public function forVariant(StorefrontProductVariant $variant): int
    {
        if ($variant->relationLoaded('itemMappings') && $variant->itemMappings->isNotEmpty()) {
            return (int) $variant->itemMappings->sum(fn (StorefrontVariantItemMapping $mapping) => $this->forMapping($mapping));
        }

        if ($variant->item_id) {
            return $this->forItem($variant->item ?? Item::find($variant->item_id));
        }

        if ($variant->stock_override !== null) {
            return max(0, (int) $variant->stock_override);
        }

        return max(0, (int) ($variant->stock ?? 0));
    }

    public function forItem(?Item $item): int
    {
        if (! $item) {
            return 0;
        }

        if ($item->relationLoaded('inventoryStocks')) {
            return max(0, (int) floor((float) $item->inventoryStocks
                ->reject(fn ($stock) => in_array(strtoupper((string) ($stock->warehouse?->code ?? '')), $this->excludedWarehouseCodes(), true))
                ->sum('qty')));
        }

        return max(0, (int) floor((float) $item->inventoryStocks()
            ->whereHas('warehouse', fn ($query) => $query->whereNotIn('code', $this->excludedWarehouseCodes()))
            ->sum('qty')));
    }

    public function forProduct(StorefrontProduct $product): int
    {
        if ($product->relationLoaded('variantItemMappings') && $product->variantItemMappings->isNotEmpty()) {
            return (int) $product->variantItemMappings->sum(fn (StorefrontVariantItemMapping $mapping) => $this->forMapping($mapping));
        }

        if ($product->relationLoaded('variants') && $product->variants->isNotEmpty()) {
            return (int) $product->variants->sum(fn (StorefrontProductVariant $variant) => $this->forVariant($variant));
        }

        return max(0, (int) ($product->stock ?? 0));
    }

    public function mappingForSelection(StorefrontProduct $product, string $colorName, ?string $sizeLabel = null): ?StorefrontVariantItemMapping
    {
        $variant = $this->variantForSelection($product, $colorName, null);
        $size = $this->sizeForSelection($product, $sizeLabel);

        if (! $variant || ! $size) {
            return null;
        }

        if ($product->relationLoaded('variantItemMappings')) {
            return $product->variantItemMappings->first(function (StorefrontVariantItemMapping $mapping) use ($variant, $size) {
                return (int) $mapping->variant_id === (int) $variant->id
                    && (int) $mapping->size_id === (int) $size->id;
            });
        }

        return StorefrontVariantItemMapping::query()
            ->where('product_id', $product->id)
            ->where('variant_id', $variant->id)
            ->where('size_id', $size->id)
            ->with('item.inventoryStocks.warehouse')
            ->first();
    }

    public function sizeForSelection(StorefrontProduct $product, ?string $sizeLabel = null): ?StorefrontProductSize
    {
        $sizes = $product->relationLoaded('sizes')
            ? $product->sizes
            : $product->sizes()->where('is_active', true)->orderBy('sort_order')->get();

        $sizeLabel = trim((string) $sizeLabel);

        return $sizes->first(fn (StorefrontProductSize $size) => strcasecmp((string) $size->size_label, $sizeLabel) === 0);
    }

    public function variantForSelection(StorefrontProduct $product, string $colorName, ?string $sizeLabel = null): ?StorefrontProductVariant
    {
        $variants = $product->relationLoaded('variants')
            ? $product->variants
            : $product->variants()->where('is_active', true)->orderBy('sort_order')->get();

        $colorName = trim($colorName);
        $sizeLabel = trim((string) $sizeLabel);

        $exact = $variants->first(function (StorefrontProductVariant $variant) use ($colorName, $sizeLabel) {
            return strcasecmp((string) $variant->color_name, $colorName) === 0
                && $sizeLabel !== ''
                && strcasecmp((string) $variant->size_label, $sizeLabel) === 0;
        });

        if ($exact) {
            return $exact;
        }

        return $variants->first(function (StorefrontProductVariant $variant) use ($colorName) {
            return strcasecmp((string) $variant->color_name, $colorName) === 0
                && blank($variant->size_label);
        }) ?: $variants->first(fn (StorefrontProductVariant $variant) => strcasecmp((string) $variant->color_name, $colorName) === 0);
    }

    public function forSelection(StorefrontProduct $product, string $colorName, ?string $sizeLabel = null): int
    {
        $mapping = $this->mappingForSelection($product, $colorName, $sizeLabel);

        if ($mapping) {
            return $this->forMapping($mapping);
        }

        if ($this->productUsesMappings($product)) {
            return 0;
        }

        $variant = $this->variantForSelection($product, $colorName, $sizeLabel);

        return $variant ? $this->forVariant($variant) : 0;
    }

    private function productUsesMappings(StorefrontProduct $product): bool
    {
        if ($product->relationLoaded('variantItemMappings')) {
            return $product->variantItemMappings->isNotEmpty();
        }

        return StorefrontVariantItemMapping::query()->where('product_id', $product->id)->exists();
    }

    private function excludedWarehouseCodes(): array
    {
        return ['REJECT', 'REJ-CUT', 'REJ-SEW', 'REJ-FIN'];
    }
}
