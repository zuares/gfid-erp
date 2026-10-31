<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceProductModel;
use App\Models\SkuMapping;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmptyVariantDashboardController extends Controller
{
    private const CHANNEL_CODES = [
        'SHOPEE', 'SHP', 'shopee', 'shp',
        'TIKTOK', 'TTK', 'TT', 'tiktok', 'ttk', 'tt',
    ];

    public function __invoke(Request $request): View
    {
        $stockStatus = in_array($request->query('stock_status'), ['zero', 'negative'], true)
            ? (string) $request->query('stock_status')
            : '';
        $channel = in_array($request->query('channel'), ['shopee', 'tiktok'], true)
            ? (string) $request->query('channel')
            : '';
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);
        $storeId = max(0, $request->integer('store_id'));
        $averageSalesInput = $request->query('min_avg_sales');
        $minAverageSales = $request->filled('min_avg_sales') && is_numeric($averageSalesInput)
            ? min(1_000_000, max(0, (float) $averageSalesInput))
            : null;
        $inventoryAverageSales = $this->inventoryAverageSalesExpression();

        $scopedQuery = $this->mappedEmptyStockQuery()
            ->when($storeId > 0, fn (Builder $query) => $query->where('mp_stock.store_id', $storeId))
            ->when($channel === 'tiktok', fn (Builder $query) => $query->whereIn(DB::raw('LOWER(ch_stock.code)'), ['tiktok', 'ttk', 'tt']))
            ->when($channel === 'shopee', fn (Builder $query) => $query->whereIn(DB::raw('LOWER(ch_stock.code)'), ['shopee', 'shp']))
            ->when($minAverageSales !== null, fn (Builder $query) => $query->whereRaw($inventoryAverageSales.' > CAST(? AS DECIMAL(18,4))', [$minAverageSales]))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function (Builder $searchQuery) use ($like): void {
                    $searchQuery->where('mp_stock.item_name', 'like', $like)
                        ->orWhere('marketplace_product_models.model_name', 'like', $like)
                        ->orWhere('marketplace_product_models.model_sku', 'like', $like)
                        ->orWhere('marketplace_product_models.model_id', 'like', $like);
                });
            });

        $aggregate = (clone $scopedQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN marketplace_product_models.stock = 0 THEN 1 ELSE 0 END) as zero_count')
            ->selectRaw('SUM(CASE WHEN marketplace_product_models.stock < 0 THEN 1 ELSE 0 END) as negative_count')
            ->selectRaw('COUNT(DISTINCT marketplace_product_models.marketplace_product_id) as product_count')
            ->toBase()
            ->first();
        $summary = [
            'total' => (int) ($aggregate->total ?? 0),
            'zero' => (int) ($aggregate->zero_count ?? 0),
            'negative' => (int) ($aggregate->negative_count ?? 0),
            'products' => (int) ($aggregate->product_count ?? 0),
        ];

        $variantsQuery = clone $scopedQuery;
        if ($stockStatus === 'zero') {
            $variantsQuery->where('marketplace_product_models.stock', 0);
        } elseif ($stockStatus === 'negative') {
            $variantsQuery->where('marketplace_product_models.stock', '<', 0);
        }

        $variants = $variantsQuery
            ->select('marketplace_product_models.*')
            ->selectRaw($inventoryAverageSales.' as average_daily_sales')
            ->with([
                'product:id,store_id,item_id,item_name,item_sku,item_status,image_url,synced_at',
                'product.store:id,name,code,channel_id',
                'product.store.channel:id,code,name',
            ])
            ->orderBy('marketplace_product_models.stock')
            ->orderBy('mp_stock.item_name')
            ->simplePaginate(25)
            ->withQueryString();

        $this->attachMappingsAndInternalStocks($variants->getCollection());

        $stores = Store::query()
            ->where('status', 'active')
            ->whereHas('channel', fn (Builder $query) => $query->whereIn('code', self::CHANNEL_CODES))
            ->with('channel:id,code,name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'channel_id']);

        return view('marketplace.empty-variants', compact(
            'variants',
            'stores',
            'summary',
            'stockStatus',
            'channel',
            'search',
            'storeId',
            'minAverageSales',
        ));
    }

    private function mappedEmptyStockQuery(): Builder
    {
        return MarketplaceProductModel::query()
            ->join('marketplace_products as mp_stock', 'mp_stock.id', '=', 'marketplace_product_models.marketplace_product_id')
            ->join('stores as st_stock', 'st_stock.id', '=', 'mp_stock.store_id')
            ->join('channels as ch_stock', 'ch_stock.id', '=', 'st_stock.channel_id')
            ->where('mp_stock.has_model', true)
            ->where('marketplace_product_models.model_id', '!=', '0')
            ->whereNotNull('marketplace_product_models.model_sku')
            ->whereRaw("TRIM(marketplace_product_models.model_sku) != ''")
            ->where('marketplace_product_models.stock', '<=', 0)
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

    private function inventoryAverageSalesExpression(): string
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

    private function attachMappingsAndInternalStocks(Collection $variants): void
    {
        if ($variants->isEmpty()) {
            return;
        }

        $skus = $variants->pluck('model_sku')
            ->map(fn ($sku) => trim((string) $sku))
            ->filter()
            ->unique()
            ->values();

        $mappings = SkuMapping::query()
            ->whereIn('marketplace_sku', $skus)
            ->with('item:id,code,name,avg_daily_sales,avg_daily_sales_window,avg_daily_sales_updated_at')
            ->get();

        $mappingFor = function (MarketplaceProductModel $variant) use ($mappings): ?SkuMapping {
            $sku = mb_strtolower(trim((string) $variant->model_sku));
            $channel = strtolower((string) $variant->product?->store?->channel?->code);
            $aliases = in_array($channel, ['tiktok', 'ttk', 'tt'], true)
                ? ['tiktok', 'tiktok shop', 'ttk', 'tt']
                : ['shopee', 'shp'];

            $candidates = $mappings->filter(fn (SkuMapping $mapping): bool => mb_strtolower(trim((string) $mapping->marketplace_sku)) === $sku);

            return $candidates->first(fn (SkuMapping $mapping): bool => in_array(strtolower(trim((string) $mapping->channel_code)), $aliases, true))
                ?? $candidates->first(fn (SkuMapping $mapping): bool => blank($mapping->channel_code));
        };

        $resolvedMappings = $variants->mapWithKeys(fn (MarketplaceProductModel $variant): array => [
            $variant->id => $mappingFor($variant),
        ]);
        $itemIds = $resolvedMappings->filter()->pluck('item_id')->unique()->values();
        $internalStocks = DB::table('inventory_stocks')
            ->whereIn('item_id', $itemIds)
            ->selectRaw('item_id, COALESCE(SUM(qty), 0) as physical, COALESCE(SUM(allocated_qty), 0) as allocated')
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        foreach ($variants as $variant) {
            $mapping = $resolvedMappings->get($variant->id);
            $stock = $mapping ? $internalStocks->get($mapping->item_id) : null;
            $physical = (float) ($stock->physical ?? 0);
            $allocated = (float) ($stock->allocated ?? 0);
            $variant->setAttribute('mapping_detail', $mapping ? [
                'item_id' => $mapping->item_id,
                'item_code' => $mapping->item?->code,
                'item_name' => $mapping->item?->name,
                'internal_physical' => $physical,
                'internal_allocated' => $allocated,
                'internal_available' => $physical - $allocated,
                'avg_daily_sales' => (float) ($mapping->item?->avg_daily_sales ?? 0),
                'avg_daily_sales_window' => (int) ($mapping->item?->avg_daily_sales_window ?? 30),
                'avg_daily_sales_updated_at' => $mapping->item?->avg_daily_sales_updated_at,
            ] : null);
        }
    }
}
