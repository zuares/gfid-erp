<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceProductModel;
use App\Models\SkuMapping;
use App\Services\Marketplace\MarketplaceVariantStockSummaryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmptyVariantDashboardController extends Controller
{
    private const STOCK_SCOPES = [
        'all',
        'marketplace_empty',
        'internal_empty_marketplace_stock',
    ];

    public function __construct(
        private MarketplaceVariantStockSummaryService $variantStockSummary,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $stockScope = in_array($request->query('stock_scope'), self::STOCK_SCOPES, true)
            ? (string) $request->query('stock_scope')
            : 'all';
        $stockStatus = in_array($request->query('stock_status'), ['zero', 'negative'], true)
            ? (string) $request->query('stock_status')
            : 'zero';
        $channel = in_array($request->query('channel'), ['shopee', 'tiktok'], true)
            ? (string) $request->query('channel')
            : '';
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);
        $storeId = max(0, $request->integer('store_id'));
        $averageSalesInput = $request->query('min_avg_sales');
        $minAverageSales = $request->filled('min_avg_sales')
            && is_numeric($averageSalesInput)
            && (float) $averageSalesInput > 0
            ? min(1_000_000, (float) $averageSalesInput)
            : null;
        $inventoryAverageSales = $this->variantStockSummary->inventoryAverageSalesExpression();
        $inventoryAvailableStock = $this->variantStockSummary->inventoryAvailableStockExpression();
        $stores = $this->variantStockSummary->connectedStores($channel);
        $connectedStoreIds = $stores->pluck('id');

        if ($storeId > 0 && ! $connectedStoreIds->contains($storeId)) {
            $storeId = 0;
        }

        $baseScopedQuery = $this->variantStockSummary->mappedEmptyStockQuery()
            ->whereIn('mp_stock.store_id', $connectedStoreIds)
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

        $emptyAggregate = (clone $baseScopedQuery)
            ->where('marketplace_product_models.stock', '<=', 0)
            ->whereRaw($inventoryAvailableStock.' > 0')
            ->selectRaw('COUNT(*) as empty_total')
            ->selectRaw('SUM(CASE WHEN marketplace_product_models.stock = 0 THEN 1 ELSE 0 END) as zero_count')
            ->selectRaw('SUM(CASE WHEN marketplace_product_models.stock < 0 THEN 1 ELSE 0 END) as negative_count')
            ->selectRaw('COUNT(DISTINCT marketplace_product_models.marketplace_product_id) as empty_product_count')
            ->toBase()
            ->first();

        $internalEmptyAggregate = (clone $baseScopedQuery)
            ->where('marketplace_product_models.stock', '>', 0)
            ->whereRaw($inventoryAvailableStock.' <= 0')
            ->selectRaw('COUNT(*) as internal_empty_mp_stock_count')
            ->selectRaw('COUNT(DISTINCT marketplace_product_models.marketplace_product_id) as internal_empty_product_count')
            ->toBase()
            ->first();

        $allAggregate = (clone $baseScopedQuery)
            ->selectRaw('COUNT(*) as mapped_total')
            ->selectRaw('COUNT(DISTINCT marketplace_product_models.marketplace_product_id) as mapped_product_count')
            ->toBase()
            ->first();

        $emptyTotal = (int) ($emptyAggregate->empty_total ?? 0);
        $zeroCount = (int) ($emptyAggregate->zero_count ?? 0);
        $negativeCount = (int) ($emptyAggregate->negative_count ?? 0);
        $internalEmptyCount = (int) ($internalEmptyAggregate->internal_empty_mp_stock_count ?? 0);
        $mappedTotal = (int) ($allAggregate->mapped_total ?? 0);
        $mappedProductCount = (int) ($allAggregate->mapped_product_count ?? 0);

        $summary = [
            'total' => match ($stockScope) {
                'internal_empty_marketplace_stock' => $internalEmptyCount,
                'marketplace_empty' => $emptyTotal,
                default => $mappedTotal,
            },
            'mapped_total' => $mappedTotal,
            'empty_total' => $emptyTotal,
            'zero' => $zeroCount,
            'negative' => $negativeCount,
            'marketplace_available' => $internalEmptyCount,
            'internal_empty_mp_stock' => $internalEmptyCount,
            'products' => match ($stockScope) {
                'internal_empty_marketplace_stock' => (int) ($internalEmptyAggregate->internal_empty_product_count ?? 0),
                'marketplace_empty' => (int) ($emptyAggregate->empty_product_count ?? 0),
                default => $mappedProductCount,
            },
        ];

        $variantsQuery = clone $baseScopedQuery;
        if ($stockScope === 'internal_empty_marketplace_stock') {
            $variantsQuery
                ->where('marketplace_product_models.stock', '>', 0)
                ->whereRaw($inventoryAvailableStock.' <= 0');
        } elseif ($stockScope === 'marketplace_empty') {
            $variantsQuery->whereRaw($inventoryAvailableStock.' > 0');
            if ($stockStatus === 'zero') {
                $variantsQuery->where('marketplace_product_models.stock', 0);
            } elseif ($stockStatus === 'negative') {
                $variantsQuery->where('marketplace_product_models.stock', '<', 0);
            }
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

        return view('marketplace.empty-variants', compact(
            'variants',
            'stores',
            'summary',
            'stockScope',
            'stockStatus',
            'channel',
            'search',
            'storeId',
            'minAverageSales',
        ));
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
