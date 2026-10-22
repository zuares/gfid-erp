<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemCostSnapshot;
use App\Models\ItemRole;
use App\Models\SkuMapping;
use App\Services\SkuMappingPropagationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SkuMappingController extends Controller
{
    public function __construct(
        private readonly SkuMappingPropagationService $mappingPropagation,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = SkuMapping::with('item:id,code,name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('marketplace_sku', 'like', "%{$search}%")
                  ->orWhereHas('item', function ($qi) use ($search) {
                      $qi->where('code', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        $channelCode = strtolower(trim((string) $request->input('channel_code')));
        if ($channelCode === 'global') {
            $query->whereNull('channel_code');
        } elseif ($channelCode !== '') {
            $query->whereRaw('LOWER(channel_code) = ?', [$channelCode]);
        }

        $stockStatus = strtolower(trim((string) $request->input('stock_status')));
        if (in_array($stockStatus, ['available', 'empty'], true)) {
            $stockAvailable = '(SELECT COALESCE(SUM(stock.qty), 0) - COALESCE(SUM(stock.allocated_qty), 0)
                FROM inventory_stocks AS stock
                WHERE stock.item_id = items.id)';

            $query->whereHas('item', function ($itemQuery) use ($stockAvailable, $stockStatus) {
                $operator = $stockStatus === 'available' ? '>' : '<=';
                $itemQuery->whereRaw("{$stockAvailable} {$operator} 0");
            });
        }

        $perPage = min(max($request->integer('per_page', 50), 1), 100);
        $mappings = $query->orderBy('marketplace_sku')
            ->paginate($perPage);

        $itemIds = collect($mappings->items())->pluck('item_id')->filter()->unique();

        $stocks = DB::table('inventory_stocks')
            ->whereIn('item_id', $itemIds)
            ->selectRaw('item_id, SUM(qty) as total_qty, SUM(allocated_qty) as allocated_qty')
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        foreach ($mappings->items() as $mapping) {
            if ($mapping->item) {
                $stock = $stocks->get($mapping->item_id);
                $mapping->item->stock_physical = (float) ($stock->total_qty ?? 0);
                $mapping->item->stock_packing = (float) ($stock->allocated_qty ?? 0);
                $mapping->item->stock_available = $mapping->item->stock_physical - $mapping->item->stock_packing;
            }
        }

        return response()->json($mappings);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'marketplace_sku' => ['required', 'string', 'max:100'],
            'channel_code'    => ['nullable', 'string', 'max:30'],
            'item_id'         => ['required', 'integer', 'exists:items,id'],
            'notes'           => ['nullable', 'string', 'max:255'],
        ]);

        $data['marketplace_sku'] = trim($data['marketplace_sku']);
        $data['channel_code'] = filled($data['channel_code'] ?? null)
            ? strtolower(trim($data['channel_code']))
            : null;

        $mapping = SkuMapping::updateOrCreate(
            [
                'marketplace_sku' => $data['marketplace_sku'],
                'channel_code'    => $data['channel_code'] ?? null,
            ],
            [
                'item_id' => $data['item_id'],
                'notes'   => $data['notes'] ?? null,
            ]
        );

        $this->mappingPropagation->propagate([[
            'marketplace_sku' => $data['marketplace_sku'],
            'channel_code' => $data['channel_code'],
        ]]);

        return response()->json($mapping->load('item:id,code,name'), 201);
    }

    public function update(Request $request, SkuMapping $skuMapping): JsonResponse
    {
        $data = $request->validate([
            'marketplace_sku' => ['required', 'string', 'max:100'],
            'channel_code' => ['nullable', 'string', 'max:30'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $data['marketplace_sku'] = trim($data['marketplace_sku']);
        $data['channel_code'] = filled($data['channel_code'] ?? null)
            ? strtolower(trim($data['channel_code']))
            : null;

        $duplicate = SkuMapping::query()
            ->where('marketplace_sku', $data['marketplace_sku'])
            ->where(function ($query) use ($data) {
                $data['channel_code'] === null
                    ? $query->whereNull('channel_code')
                    : $query->whereRaw('LOWER(channel_code) = ?', [$data['channel_code']]);
            })
            ->where('id', '!=', $skuMapping->id)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'SKU dan channel tersebut sudah memiliki mapping lain.',
            ], 422);
        }

        $oldSku = $skuMapping->marketplace_sku;
        $oldChannel = $skuMapping->channel_code;

        $skuMapping->update([
            'marketplace_sku' => $data['marketplace_sku'],
            'channel_code' => $data['channel_code'],
            'item_id' => $data['item_id'],
            'notes' => $data['notes'] ?? null,
        ]);

        $this->mappingPropagation->propagate([
            ['marketplace_sku' => $oldSku, 'channel_code' => $oldChannel],
            ['marketplace_sku' => $data['marketplace_sku'], 'channel_code' => $data['channel_code']],
        ]);

        return response()->json($skuMapping->fresh()->load('item:id,code,name'));
    }

    public function destroy(SkuMapping $skuMapping): JsonResponse
    {
        $oldKey = [
            'marketplace_sku' => $skuMapping->marketplace_sku,
            'channel_code' => $skuMapping->channel_code,
        ];
        $skuMapping->delete();
        $this->mappingPropagation->propagate([$oldKey]);

        return response()->json(['message' => 'Mapping dihapus.']);
    }

    /** SKU dari marketplace_order_items yang belum punya mapping. */
    public function unmappedSkus(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'channel_code' => ['nullable', 'string', 'max:30'],
            'search' => ['nullable', 'string', 'max:100'],
            'order_date_from' => ['nullable', 'date'],
            'order_date_to' => ['nullable', 'date', 'after_or_equal:order_date_from'],
            'sort' => ['nullable', Rule::in(['orders_desc', 'sku_asc', 'latest'])],
        ]);

        $skuExpression = "COALESCE(NULLIF(moi.marketplace_sku, ''), NULLIF(moi.model_sku, ''), NULLIF(moi.item_sku, ''))";
        $itemNameExpression = "COALESCE(NULLIF(moi.item_name, ''), NULLIF(moi.item_name_snapshot, ''))";
        $variantNameExpression = "COALESCE(NULLIF(moi.variant_name, ''), NULLIF(moi.variant_snapshot, ''))";
        $channelCode = strtolower(trim((string) ($filters['channel_code'] ?? '')));
        $search = trim((string) ($filters['search'] ?? ''));

        $unmapped = DB::table('marketplace_order_items as moi')
            ->join('marketplace_orders as mo', 'mo.id', '=', 'moi.marketplace_order_id')
            ->join('stores as s', 's.id', '=', 'mo.store_id')
            ->join('channels as c', 'c.id', '=', 's.channel_id')
            ->leftJoin('sku_mappings as sm', function ($join) {
                $join->whereRaw("(
                    sm.marketplace_sku = NULLIF(moi.marketplace_sku, '')
                    OR sm.marketplace_sku = NULLIF(moi.model_sku, '')
                    OR sm.marketplace_sku = NULLIF(moi.item_sku, '')
                )")
                ->whereRaw("(
                    sm.channel_code IS NULL
                    OR LOWER(TRIM(sm.channel_code)) = LOWER(TRIM(c.code))
                    OR (
                        LOWER(TRIM(c.code)) IN ('tiktok', 'ttk', 'tiktok shop')
                        AND LOWER(TRIM(sm.channel_code)) IN ('tiktok', 'ttk', 'tiktok shop')
                    )
                    OR (
                        LOWER(TRIM(c.code)) IN ('shopee', 'shp')
                        AND LOWER(TRIM(sm.channel_code)) IN ('shopee', 'shp')
                    )
                    OR (
                        LOWER(TRIM(c.code)) IN ('tokopedia', 'tkp')
                        AND LOWER(TRIM(sm.channel_code)) IN ('tokopedia', 'tkp')
                    )
                )");
            })
            ->selectRaw("{$skuExpression} as sku,
                MAX({$itemNameExpression}) as item_name,
                MAX({$variantNameExpression}) as variant_name,
                c.code as channel_code,
                COUNT(moi.id) as order_count,
                COUNT(DISTINCT s.id) as store_count,
                GROUP_CONCAT(DISTINCT s.name) as store_names,
                MAX(mo.ordered_at) as last_order_at")
            ->whereRaw("{$skuExpression} IS NOT NULL AND {$skuExpression} != ''")
            ->whereNull('sm.id')
            ->when(! empty($filters['store_id']), fn ($query) => $query->where('s.id', (int) $filters['store_id'])
            )
            ->when($channelCode !== '', fn ($query) => $query->whereRaw('LOWER(c.code) = ?', [$channelCode]))
            ->when($search !== '', function ($query) use ($search, $skuExpression, $itemNameExpression, $variantNameExpression) {
                $needle = '%' . mb_strtolower($search) . '%';
                $query->where(function ($searchQuery) use ($needle, $skuExpression, $itemNameExpression, $variantNameExpression) {
                    $searchQuery->whereRaw("LOWER({$skuExpression}) LIKE ?", [$needle])
                        ->orWhereRaw("LOWER({$itemNameExpression}) LIKE ?", [$needle])
                        ->orWhereRaw("LOWER({$variantNameExpression}) LIKE ?", [$needle]);
                });
            })
            ->when(! empty($filters['order_date_from']), fn ($query) => $query->whereDate('mo.ordered_at', '>=', $filters['order_date_from']))
            ->when(! empty($filters['order_date_to']), fn ($query) => $query->whereDate('mo.ordered_at', '<=', $filters['order_date_to']))
            ->groupByRaw("{$skuExpression}, c.code")
            ->when(($filters['sort'] ?? 'orders_desc') === 'sku_asc', fn ($query) => $query->orderBy('sku'))
            ->when(($filters['sort'] ?? 'orders_desc') === 'latest', fn ($query) => $query->orderByDesc('last_order_at'))
            ->when(($filters['sort'] ?? 'orders_desc') === 'orders_desc', fn ($query) => $query->orderByDesc('order_count'))
            ->orderBy('sku')
            ->get();

        return response()->json($unmapped);
    }

    public function categories(): JsonResponse
    {
        return response()->json(
            ItemCategory::query()
                ->active()
                ->orderByRaw("CASE kind
                    WHEN 'product' THEN 1
                    WHEN 'material' THEN 2
                    WHEN 'support' THEN 3
                    WHEN 'accessory' THEN 4
                    WHEN 'packaging' THEN 5
                    ELSE 9
                END")
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'kind'])
                ->map(fn (ItemCategory $category) => [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'kind' => $category->kind,
                    'kind_label' => $category->kind_label,
                ])
        );
    }

    public function quickCreateCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('item_categories', 'code')],
            'name' => ['required', 'string', 'max:190'],
            'kind' => ['required', 'string', Rule::in(array_keys(ItemCategory::kindLabels()))],
        ]);

        $category = ItemCategory::create([
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'kind' => $data['kind'],
            'active' => true,
        ]);

        return response()->json([
            'id' => $category->id,
            'code' => $category->code,
            'name' => $category->name,
            'kind' => $category->kind,
            'kind_label' => $category->kind_label,
        ], 201);
    }

    /** Buat item varian baru secara cepat dari modal SKU mapping. */
    public function quickCreateItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('items', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:20'],
            'type' => ['nullable', 'string', Rule::in(['material', 'finished_good', 'wip'])],
            'item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'production_source' => ['nullable', 'string', Rule::in(array_keys(Item::productionSourceLabels()))],
            'hpp' => ['nullable', 'numeric', 'min:0'],
        ]);

        $type = $data['type'] ?? 'finished_good';
        $categoryId = $data['item_category_id'] ?? null;
        $this->validateCategoryForType($type, $categoryId);

        $classification = $this->classificationFor($type, $categoryId);
        $productionSource = in_array($type, ['finished_good', 'wip'], true)
            ? ($data['production_source'] ?? Item::PRODUCTION_BUY)
            : null;
        $hpp = (float) ($data['hpp'] ?? 0);

        $item = DB::transaction(function () use ($data, $type, $categoryId, $classification, $productionSource, $hpp) {
            $item = Item::create([
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'unit' => $data['unit'] ?? 'pcs',
                'type' => $type,
                'item_category_id' => $categoryId,
                'item_role_id' => $classification['item_role_id'],
                'item_role' => $classification['item_role'],
                'is_stocked' => $classification['is_stocked'],
                'hpp_behavior' => $classification['hpp_behavior'],
                'production_source' => $productionSource,
                'can_buy' => in_array($type, ['finished_good', 'wip'], true),
                'can_make' => false,
                'default_supply_source' => in_array($type, ['finished_good', 'wip'], true) ? Item::SUPPLY_BUY : null,
                'hpp' => $hpp,
                'base_unit_cost' => $hpp,
                'active' => true,
                'affects_hpp' => false,
            ]);

            if ($hpp > 0) {
                ItemCostSnapshot::create([
                    'item_id' => $item->id,
                    'warehouse_id' => null,
                    'snapshot_date' => Carbon::now()->toDateString(),
                    'reference_type' => 'marketplace_quick_create',
                    'reference_id' => null,
                    'qty_basis' => 0,
                    'rm_unit_cost' => $hpp,
                    'cutting_unit_cost' => 0,
                    'sewing_unit_cost' => 0,
                    'finishing_unit_cost' => 0,
                    'packaging_unit_cost' => 0,
                    'overhead_unit_cost' => 0,
                    'unit_cost' => $hpp,
                    'notes' => 'HPP awal dari marketplace issue quick-create',
                    'is_active' => 1,
                    'created_by' => Auth::id(),
                ]);
            }

            return $item;
        });

        return response()->json(['id' => $item->id, 'code' => $item->code, 'name' => $item->name], 201);
    }

    private function validateCategoryForType(string $type, ?int $categoryId): void
    {
        if (!$categoryId) {
            return;
        }

        $kind = ItemCategory::whereKey($categoryId)->value('kind');
        $allowed = match ($type) {
            'finished_good', 'wip' => ['product'],
            'material' => ['material', 'support', 'accessory', 'packaging', 'other'],
            default => ['product', 'material', 'support', 'accessory', 'packaging', 'other'],
        };

        abort_if(!in_array($kind, $allowed, true), 422, 'Kategori tidak sesuai dengan tipe item.');
    }

    private function classificationFor(string $type, ?int $categoryId): array
    {
        $category = $categoryId ? ItemCategory::find($categoryId) : null;
        $roleCode = match (true) {
            $type === 'finished_good' || $type === 'wip' => ItemRole::FG,
            $category?->kind === 'support' || $category?->kind === 'accessory' => ItemRole::SUP,
            $category?->kind === 'packaging' => ItemRole::PKG,
            default => ItemRole::RM,
        };

        return match ($roleCode) {
            ItemRole::FG => [
                'item_role_id' => ItemRole::idByCode(ItemRole::FG),
                'item_role' => 'finished_good',
                'is_stocked' => true,
                'hpp_behavior' => 'hpp',
            ],
            ItemRole::SUP => [
                'item_role_id' => ItemRole::idByCode(ItemRole::SUP),
                'item_role' => 'production_supply',
                'is_stocked' => true,
                'hpp_behavior' => 'hpp',
            ],
            ItemRole::PKG => [
                'item_role_id' => ItemRole::idByCode(ItemRole::PKG),
                'item_role' => 'shipping_supply',
                'is_stocked' => false,
                'hpp_behavior' => 'non_hpp',
            ],
            default => [
                'item_role_id' => ItemRole::idByCode(ItemRole::RM),
                'item_role' => 'raw_material',
                'is_stocked' => true,
                'hpp_behavior' => 'hpp',
            ],
        };
    }

    /** Cari item by code/name untuk dropdown. */
    public function searchItems(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        $items = Item::where(function ($query) use ($q) {
            $query->where('code', 'like', "%{$q}%")
                  ->orWhere('name', 'like', "%{$q}%");
        })
            ->select('id', 'code', 'name')
            ->limit(20)
            ->get();

        return response()->json($items);
    }
}
