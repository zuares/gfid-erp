<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceProduct;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmptyVariantDashboardController extends Controller
{
    private const CHANNEL_CODES = [
        'SHOPEE', 'SHP', 'shopee', 'shp',
        'TIKTOK', 'TTK', 'TT', 'tiktok', 'ttk', 'tt',
    ];

    public function __invoke(Request $request): View
    {
        $issue = in_array($request->query('issue'), ['missing_name', 'missing_sku', 'no_models'], true)
            ? (string) $request->query('issue')
            : '';
        $channel = in_array($request->query('channel'), ['shopee', 'tiktok'], true)
            ? (string) $request->query('channel')
            : '';
        $search = mb_substr(trim((string) $request->query('search')), 0, 100);
        $storeId = max(0, $request->integer('store_id'));

        $scopedQuery = MarketplaceProduct::query()
            ->where('has_model', true)
            ->when($storeId > 0, fn (Builder $query) => $query->where('store_id', $storeId))
            ->when($channel !== '', function (Builder $query) use ($channel): void {
                $codes = $channel === 'tiktok'
                    ? ['TIKTOK', 'TTK', 'TT', 'tiktok', 'ttk', 'tt']
                    : ['SHOPEE', 'SHP', 'shopee', 'shp'];

                $query->whereHas('store.channel', fn (Builder $channelQuery) => $channelQuery->whereIn('code', $codes));
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function (Builder $searchQuery) use ($like): void {
                    $searchQuery->where('item_name', 'like', $like)
                        ->orWhere('item_sku', 'like', $like)
                        ->orWhere('item_id', 'like', $like)
                        ->orWhereHas('models', function (Builder $modelQuery) use ($like): void {
                            $modelQuery->where('model_name', 'like', $like)
                                ->orWhere('model_sku', 'like', $like)
                                ->orWhere('model_id', 'like', $like);
                        });
                });
            });

        $summary = [
            'total' => $this->withAnyIssue(clone $scopedQuery)->count(),
            'missing_name' => $this->withMissingName(clone $scopedQuery)->count(),
            'missing_sku' => $this->withMissingSku(clone $scopedQuery)->count(),
            'no_models' => (clone $scopedQuery)->whereDoesntHave('models')->count(),
        ];

        $productsQuery = clone $scopedQuery;
        match ($issue) {
            'missing_name' => $this->withMissingName($productsQuery),
            'missing_sku' => $this->withMissingSku($productsQuery),
            'no_models' => $productsQuery->whereDoesntHave('models'),
            default => $this->withAnyIssue($productsQuery),
        };

        $products = $productsQuery
            ->with([
                'store:id,name,code,channel_id',
                'store.channel:id,code,name',
                'models' => fn (Builder|HasMany $query) => $this->onlyEmptyModels($query),
            ])
            ->orderByDesc('synced_at')
            ->orderBy('item_name')
            ->paginate(25)
            ->withQueryString();

        $stores = Store::query()
            ->where('status', 'active')
            ->whereHas('channel', fn (Builder $query) => $query->whereIn('code', self::CHANNEL_CODES))
            ->with('channel:id,code,name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'channel_id']);

        return view('marketplace.empty-variants', compact(
            'products',
            'stores',
            'summary',
            'issue',
            'channel',
            'search',
            'storeId',
        ));
    }

    private function withAnyIssue(Builder $query): Builder
    {
        return $query->where(function (Builder $issueQuery): void {
            $issueQuery->whereDoesntHave('models')
                ->orWhereHas('models', fn (Builder $modelQuery) => $this->onlyEmptyModels($modelQuery));
        });
    }

    private function withMissingName(Builder $query): Builder
    {
        return $query->whereHas('models', function (Builder $modelQuery): void {
            $modelQuery->where(function (Builder $blankQuery): void {
                $blankQuery->whereNull('model_name')->orWhereRaw("TRIM(model_name) = ''");
            });
        });
    }

    private function withMissingSku(Builder $query): Builder
    {
        return $query->whereHas('models', function (Builder $modelQuery): void {
            $modelQuery->where(function (Builder $blankQuery): void {
                $blankQuery->whereNull('model_sku')->orWhereRaw("TRIM(model_sku) = ''");
            });
        });
    }

    private function onlyEmptyModels(Builder|HasMany $query): Builder|HasMany
    {
        return $query->where(function (Builder $emptyQuery): void {
            $emptyQuery->whereNull('model_name')
                ->orWhereRaw("TRIM(model_name) = ''")
                ->orWhereNull('model_sku')
                ->orWhereRaw("TRIM(model_sku) = ''");
        })->orderBy('model_name')->orderBy('model_id');
    }
}
