<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceProductModel;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MarketplaceVariantStockSummaryService
{
    private const CHANNEL_CODES = [
        'SHOPEE', 'SHP', 'shopee', 'shp',
        'TIKTOK', 'TTK', 'TT', 'tiktok', 'ttk', 'tt',
    ];

    /**
     * Ringkasan variant ter-mapping dari toko marketplace yang aktif dan
     * terhubung. Angka ini menjadi sumber bersama dashboard dan inventory.
     *
     * @return array{mapped_total:int,mapped_product_count:int,empty_total:int,zero:int,negative:int,marketplace_zero_internal_available:int,internal_empty_marketplace_stock:int}
     */
    public function summary(): array
    {
        $empty = [
            'mapped_total' => 0,
            'mapped_product_count' => 0,
            'empty_total' => 0,
            'zero' => 0,
            'negative' => 0,
            'marketplace_zero_internal_available' => 0,
            'internal_empty_marketplace_stock' => 0,
        ];

        if (! $this->requiredTablesExist()) {
            return $empty;
        }

        try {
            $connectedStoreIds = $this->connectedStores()->pluck('id')->all();
            if ($connectedStoreIds === []) {
                return $empty;
            }

            $availableStock = $this->inventoryAvailableStockExpression();
            $variantStockQuery = $this->mappedEmptyStockQuery()
                ->whereIn('mp_stock.store_id', $connectedStoreIds)
                ->select([
                    'marketplace_product_models.stock',
                    'marketplace_product_models.marketplace_product_id',
                ])
                ->selectRaw("{$availableStock} as internal_available_stock");

            $row = DB::query()
                ->fromSub($variantStockQuery, 'variant_stock')
                ->selectRaw('COUNT(*) as mapped_total')
                ->selectRaw('COUNT(DISTINCT marketplace_product_id) as mapped_product_count')
                ->selectRaw('SUM(CASE WHEN stock <= 0 AND internal_available_stock > 0 THEN 1 ELSE 0 END) as empty_total')
                ->selectRaw('SUM(CASE WHEN stock = 0 AND internal_available_stock > 0 THEN 1 ELSE 0 END) as zero')
                ->selectRaw('SUM(CASE WHEN stock < 0 AND internal_available_stock > 0 THEN 1 ELSE 0 END) as negative')
                ->selectRaw('SUM(CASE WHEN stock = 0 AND internal_available_stock > 0 THEN 1 ELSE 0 END) as marketplace_zero_internal_available')
                ->selectRaw('SUM(CASE WHEN stock > 0 AND internal_available_stock <= 0 THEN 1 ELSE 0 END) as internal_empty_marketplace_stock')
                ->first();

            return [
                'mapped_total' => (int) ($row->mapped_total ?? 0),
                'mapped_product_count' => (int) ($row->mapped_product_count ?? 0),
                'empty_total' => (int) ($row->empty_total ?? 0),
                'zero' => (int) ($row->zero ?? 0),
                'negative' => (int) ($row->negative ?? 0),
                'marketplace_zero_internal_available' => (int) ($row->marketplace_zero_internal_available ?? 0),
                'internal_empty_marketplace_stock' => (int) ($row->internal_empty_marketplace_stock ?? 0),
            ];
        } catch (Throwable) {
            return $empty;
        }
    }

    public function mappedEmptyStockQuery(): Builder
    {
        return MarketplaceProductModel::query()
            ->join('marketplace_products as mp_stock', 'mp_stock.id', '=', 'marketplace_product_models.marketplace_product_id')
            ->join('stores as st_stock', 'st_stock.id', '=', 'mp_stock.store_id')
            ->join('channels as ch_stock', 'ch_stock.id', '=', 'st_stock.channel_id')
            ->where('mp_stock.has_model', true)
            ->where('marketplace_product_models.model_id', '!=', '0')
            ->whereNotNull('marketplace_product_models.model_sku')
            ->whereRaw("TRIM(marketplace_product_models.model_sku) != ''")
            ->whereExists(function ($mapping): void {
                $mapping->selectRaw('1')
                    ->from('sku_mappings as sm_stock')
                    ->whereColumn('sm_stock.marketplace_sku', 'marketplace_product_models.model_sku')
                    ->where(function ($channelMapping): void {
                        $channelMapping->whereNull('sm_stock.channel_code')
                            ->orWhereRaw("(LOWER(ch_stock.code) IN ('shopee', 'shp') AND LOWER(sm_stock.channel_code) IN ('shopee', 'shp'))")
                            ->orWhereRaw("(LOWER(ch_stock.code) IN ('tiktok', 'ttk', 'tt') AND LOWER(sm_stock.channel_code) IN ('tiktok', 'tiktok shop', 'ttk', 'tt'))");
                    });
            });
    }

    public function inventoryAverageSalesExpression(): string
    {
        return <<<'SQL'
COALESCE((
    SELECT mapped_ads.avg_daily_sales
    FROM sku_mappings AS ads_mapping
    INNER JOIN items AS mapped_ads ON mapped_ads.id = ads_mapping.item_id
    WHERE ads_mapping.marketplace_sku = marketplace_product_models.model_sku
      AND (
        ads_mapping.channel_code IS NULL
        OR (LOWER(ch_stock.code) IN ('shopee', 'shp') AND LOWER(ads_mapping.channel_code) IN ('shopee', 'shp'))
        OR (LOWER(ch_stock.code) IN ('tiktok', 'ttk', 'tt') AND LOWER(ads_mapping.channel_code) IN ('tiktok', 'tiktok shop', 'ttk', 'tt'))
      )
    ORDER BY CASE WHEN ads_mapping.channel_code IS NULL THEN 1 ELSE 0 END
    LIMIT 1
), 0)
SQL;
    }

    public function inventoryAvailableStockExpression(): string
    {
        return <<<'SQL'
COALESCE((
    SELECT COALESCE(SUM(internal_stock.qty), 0) - COALESCE(SUM(internal_stock.allocated_qty), 0)
    FROM inventory_stocks AS internal_stock
    WHERE internal_stock.item_id = (
        SELECT availability_mapping.item_id
        FROM sku_mappings AS availability_mapping
        WHERE availability_mapping.marketplace_sku = marketplace_product_models.model_sku
          AND (
            availability_mapping.channel_code IS NULL
            OR (LOWER(ch_stock.code) IN ('shopee', 'shp') AND LOWER(availability_mapping.channel_code) IN ('shopee', 'shp'))
            OR (LOWER(ch_stock.code) IN ('tiktok', 'ttk', 'tt') AND LOWER(availability_mapping.channel_code) IN ('tiktok', 'tiktok shop', 'ttk', 'tt'))
          )
        ORDER BY CASE WHEN availability_mapping.channel_code IS NULL THEN 1 ELSE 0 END
        LIMIT 1
    )
), 0)
SQL;
    }

    public function connectedStores(?string $channel = null): Collection
    {
        return Store::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn (Builder $query) => $query->whereIn('code', self::CHANNEL_CODES))
            ->when($channel === 'tiktok', fn (Builder $query) => $query->whereHas('channel', fn ($q) => $q->whereIn(DB::raw('LOWER(code)'), ['tiktok', 'ttk', 'tt'])))
            ->when($channel === 'shopee', fn (Builder $query) => $query->whereHas('channel', fn ($q) => $q->whereIn(DB::raw('LOWER(code)'), ['shopee', 'shp'])))
            ->with('channel:id,code,name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'channel_id', 'credentials', 'token_expires_at', 'status', 'is_active'])
            ->filter(fn (Store $store): bool => (bool) $store->is_active && strtolower(trim((string) $store->status)) === 'active' && $store->connection_status === 'CONNECTED')
            ->values();
    }

    private function requiredTablesExist(): bool
    {
        foreach (['marketplace_product_models', 'marketplace_products', 'stores', 'channels', 'sku_mappings', 'inventory_stocks', 'items'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
