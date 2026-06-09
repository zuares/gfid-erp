<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\MarketplaceOrderItem;
use App\Models\SkuMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SkuMappingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            SkuMapping::with('item:id,code,name')
                ->orderBy('marketplace_sku')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'marketplace_sku' => ['required', 'string', 'max:100'],
            'channel_code'    => ['nullable', 'string', 'max:30'],
            'item_id'         => ['required', 'integer', 'exists:items,id'],
            'notes'           => ['nullable', 'string', 'max:255'],
        ]);

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

        return response()->json($mapping->load('item:id,code,name'), 201);
    }

    public function destroy(SkuMapping $skuMapping): JsonResponse
    {
        $skuMapping->delete();
        return response()->json(['message' => 'Mapping dihapus.']);
    }

    /** SKU dari marketplace_order_items yang belum punya mapping. */
    public function unmappedSkus(): JsonResponse
    {
        $mapped = SkuMapping::pluck('marketplace_sku')->unique();

        $unmapped = MarketplaceOrderItem::query()
            ->selectRaw('COALESCE(model_sku, item_sku) as sku, item_name, COUNT(*) as order_count')
            ->whereRaw('COALESCE(model_sku, item_sku) IS NOT NULL')
            ->groupByRaw('COALESCE(model_sku, item_sku), item_name')
            ->get()
            ->filter(fn ($row) => $row->sku && ! $mapped->contains($row->sku))
            ->map(fn ($row) => [
                'sku'         => $row->sku,
                'item_name'   => $row->item_name,
                'order_count' => (int) $row->order_count,
            ])
            ->unique('sku')
            ->values();

        return response()->json($unmapped);
    }

    /** Buat item varian baru secara cepat dari modal SKU mapping. */
    public function quickCreateItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('items', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:20'],
        ]);

        $item = Item::create([
            'code'        => strtoupper(trim($data['code'])),
            'name'        => $data['name'],
            'unit'        => $data['unit'] ?? 'pcs',
            'type'        => 'finished_good',
            'active'      => true,
            'is_stocked'  => true,
            'affects_hpp' => false,
        ]);

        return response()->json(['id' => $item->id, 'code' => $item->code, 'name' => $item->name], 201);
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
