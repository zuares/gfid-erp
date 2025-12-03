<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    /**
     * Helper: apply filter umum untuk Item query.
     */
    protected function applyCommonFilters($query, Request $request)
    {
        // 🔎 Search kode / nama
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        // 🎯 Filter type (single / multi)
        if ($type = $request->input('type')) {
            $types = collect(explode(',', $type))
                ->map(fn($t) => trim($t))
                ->filter()
                ->values();

            if ($types->isNotEmpty()) {
                $query->whereIn('type', $types);
            }
        }

        // 🎯 Filter kategori
        if ($categoryId = $request->input('item_category_id')) {
            $query->where('item_category_id', $categoryId);
        }

        // ✅ Filter active (default: hanya active=1)
        if ($request->has('active')) {
            $query->where('active', (int) $request->input('active') === 1);
        } else {
            $query->where('active', 1);
        }

        return $query;
    }

    /**
     * Helper: standard bentuk item JSON.
     *
     * $extra bisa diisi:
     * ['on_hand' => 10, 'warehouse_id' => 2, dll]
     */
    protected function mapItem(Item $item, array $extra = []): array
    {
        return array_merge([
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'type' => $item->type,
            'item_category_id' => $item->item_category_id,
            'item_category' => optional($item->category)->name,
        ], $extra);
    }

    /**
     * GET /api/v1/items
     *
     * Listing lengkap + pagination (lebih berat).
     */
    public function index(Request $request)
    {
        $query = Item::query()->with('category');

        $this->applyCommonFilters($query, $request);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = $perPage > 100 ? 100 : $perPage;

        $items = $query
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $items->getCollection()->map(function (Item $item) {
                return $this->mapItem($item);
            }),
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
            'filters' => [
                'q' => $request->input('q'),
                'type' => $request->input('type'),
                'item_category_id' => $request->input('item_category_id'),
                'active' => $request->input('active', 1),
            ],
        ]);
    }

    /**
     * GET /api/v1/items/suggest
     *
     * Dipakai untuk autocomplete: return ringan.
     *
     * Query param:
     * - q
     * - type (opsional)
     * - item_category_id (opsional)
     * - limit (default 20, max 50)
     * - warehouse_id (opsional, kalau diisi → include on_hand di gudang tsb)
     */
    public function suggest(Request $request)
    {
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 50 ? 50 : $limit;

        $warehouseId = $request->query('warehouse_id');

        $query = Item::query()
            ->with('category');

        $this->applyCommonFilters($query, $request);

        if ($warehouseId) {
            // join ke inventory_stocks untuk ambil stok per gudang
            $query->leftJoin('inventory_stocks as s', function ($join) use ($warehouseId) {
                $join->on('s.item_id', '=', 'items.id')
                    ->where('s.warehouse_id', '=', $warehouseId);
            })
                ->addSelect('items.*')
                ->addSelect(DB::raw('COALESCE(s.qty, 0) as on_hand'));
        }

        $query->orderBy('items.code')
            ->limit($limit);

        $items = $query->get();

        return response()->json([
            'data' => $items->map(function ($row) use ($warehouseId) {
                // kalau pakai join, $row->on_hand sudah ada, kalau tidak → null
                $extra = [];
                if ($warehouseId !== null) {
                    $extra['warehouse_id'] = (int) $warehouseId;
                    $extra['on_hand'] = isset($row->on_hand)
                    ? (float) $row->on_hand
                    : 0.0;
                }

                return $this->mapItem($row, $extra);
            }),
        ]);
    }

    /**
     * GET /api/v1/items/{item}
     */
    public function show(Item $item)
    {
        $item->load('category');

        return response()->json([
            'success' => true,
            'data' => $this->mapItem($item),
        ]);
    }
}
