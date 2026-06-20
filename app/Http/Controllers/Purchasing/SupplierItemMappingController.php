<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Supplier;
use App\Models\SupplierCategoryMapping;
use App\Models\SupplierItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierItemMappingController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $supplierId = (int) $request->input('supplier_id', 0);
        $status = (string) $request->input('status', 'active');
        $mode = $request->input('mode') === 'category' ? 'category' : 'item';

        $mappings = SupplierItem::query()
            ->with(['item:id,code,name,unit,type', 'supplier:id,code,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('item', fn ($item) => $item
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
            })
            ->when($supplierId > 0, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($status === 'active', fn ($query) => $query->where('active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('active', false))
            ->orderByDesc('is_primary')
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        $suppliers = Supplier::query()->where('active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $categories = ItemCategory::query()
            ->active()
            ->whereIn('kind', ['material', 'support', 'accessory', 'packaging'])
            ->orderBy('kind')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'kind']);
        $categoryMappings = SupplierCategoryMapping::query()
            ->with(['category:id,code,name,kind', 'supplier:id,code,name'])
            ->orderByDesc('is_primary')
            ->orderByDesc('active')
            ->orderByDesc('updated_at')
            ->get();
        $canSeeMoney = $request->user()?->isOwner() ?? false;

        return view('purchasing.supplier_items.index', compact(
            'mappings',
            'suppliers',
            'search',
            'supplierId',
            'status',
            'mode',
            'categories',
            'categoryMappings',
            'canSeeMoney',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateMapping($request);

        DB::transaction(function () use ($data, $request) {
            if ($data['is_primary']) {
                SupplierItem::query()->where('item_id', $data['item_id'])->update(['is_primary' => false]);
            }

            $mapping = SupplierItem::query()->firstOrNew([
                'supplier_id' => $data['supplier_id'],
                'item_id' => $data['item_id'],
            ]);
            $mapping->fill($data);

            if ($request->user()->isOwner()) {
                $mapping->last_price = (float) ($request->input('last_price') ?? $mapping->last_price ?? 0);
            } elseif (!$mapping->exists) {
                $mapping->last_price = 0;
            }

            $mapping->save();
        });

        return back()->with('success', 'Mapping pemasok barang berhasil disimpan.');
    }

    public function update(Request $request, SupplierItem $supplierItem): RedirectResponse
    {
        $data = $this->validateMapping($request, $supplierItem);

        DB::transaction(function () use ($data, $request, $supplierItem) {
            if ($data['is_primary']) {
                SupplierItem::query()
                    ->where('item_id', $data['item_id'])
                    ->where('id', '!=', $supplierItem->id)
                    ->update(['is_primary' => false]);
            }

            $supplierItem->fill($data);
            if ($request->user()->isOwner() && $request->filled('last_price')) {
                $supplierItem->last_price = (float) $request->input('last_price');
            }
            $supplierItem->save();
        });

        return back()->with('success', 'Mapping pemasok barang berhasil diperbarui.');
    }

    public function destroy(SupplierItem $supplierItem): RedirectResponse
    {
        $supplierItem->delete();

        return back()->with('success', 'Mapping pemasok barang berhasil dihapus.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $this->validateCategoryMapping($request);
        DB::transaction(function () use ($data) {
            $hasMapping = SupplierCategoryMapping::query()
                ->where('item_category_id', $data['item_category_id'])
                ->exists();
            $data['is_primary'] = $data['is_primary'] || !$hasMapping;

            if ($data['is_primary']) {
                SupplierCategoryMapping::query()
                    ->where('item_category_id', $data['item_category_id'])
                    ->update(['is_primary' => false]);
            }

            SupplierCategoryMapping::query()->create($data);
        });

        return redirect()
            ->route('purchasing.supplier_items.index', ['mode' => 'category'])
            ->with('success', 'Pemasok default kategori berhasil disimpan.');
    }

    public function updateCategory(
        Request $request,
        SupplierCategoryMapping $supplierCategoryMapping
    ): RedirectResponse {
        $data = $this->validateCategoryMapping($request, $supplierCategoryMapping);
        DB::transaction(function () use ($data, $supplierCategoryMapping) {
            if ($data['is_primary']) {
                SupplierCategoryMapping::query()
                    ->where('item_category_id', $data['item_category_id'])
                    ->where('id', '!=', $supplierCategoryMapping->id)
                    ->update(['is_primary' => false]);
            }

            $supplierCategoryMapping->update($data);
        });

        return redirect()
            ->route('purchasing.supplier_items.index', ['mode' => 'category'])
            ->with('success', 'Pemasok default kategori berhasil diperbarui.');
    }

    public function destroyCategory(SupplierCategoryMapping $supplierCategoryMapping): RedirectResponse
    {
        $supplierCategoryMapping->delete();

        return redirect()
            ->route('purchasing.supplier_items.index', ['mode' => 'category'])
            ->with('success', 'Pemasok default kategori berhasil dihapus.');
    }

    public function syncCategorySupplier(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_category_id' => [
                'required',
                'integer',
                Rule::exists('item_categories', 'id')->whereIn('kind', ['material', 'support', 'accessory', 'packaging']),
            ],
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('active', true),
            ],
            'selected' => ['required', 'boolean'],
            'is_primary' => ['required', 'boolean'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        DB::transaction(function () use ($data) {
            $categoryId = (int) $data['item_category_id'];
            $supplierId = (int) $data['supplier_id'];

            if (!$data['selected']) {
                $removedWasPrimary = SupplierCategoryMapping::query()
                    ->where('item_category_id', $categoryId)
                    ->where('supplier_id', $supplierId)
                    ->where('is_primary', true)
                    ->exists();

                SupplierCategoryMapping::query()
                    ->where('item_category_id', $categoryId)
                    ->where('supplier_id', $supplierId)
                    ->delete();

                if ($removedWasPrimary) {
                    SupplierCategoryMapping::query()
                        ->where('item_category_id', $categoryId)
                        ->where('active', true)
                        ->orderByDesc('updated_at')
                        ->first()?->update(['is_primary' => true]);
                }

                return;
            }

            $hasPrimary = SupplierCategoryMapping::query()
                ->where('item_category_id', $categoryId)
                ->where('is_primary', true)
                ->where('active', true)
                ->exists();
            $makePrimary = (bool) $data['is_primary'] || !$hasPrimary;

            if ($makePrimary) {
                SupplierCategoryMapping::query()
                    ->where('item_category_id', $categoryId)
                    ->update(['is_primary' => false]);
            }

            SupplierCategoryMapping::query()->updateOrCreate(
                [
                    'item_category_id' => $categoryId,
                    'supplier_id' => $supplierId,
                ],
                [
                    'is_primary' => $makePrimary,
                    'lead_time_days' => $data['lead_time_days'] ?? null,
                    'active' => true,
                ],
            );
        });

        return response()->json([
            'ok' => true,
            'mappings' => $this->categoryMappingState((int) $data['item_category_id']),
        ]);
    }

    private function validateMapping(Request $request, ?SupplierItem $mapping = null): array
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'supplier_id' => [
                'required',
                'integer',
                'exists:suppliers,id',
                Rule::unique('supplier_items')->where(fn ($query) => $query
                    ->where('item_id', $request->integer('item_id')))
                    ->ignore($mapping?->id),
            ],
            'is_primary' => ['nullable', 'boolean'],
            'minimum_order_qty' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'active' => ['nullable', 'boolean'],
            'last_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        return [
            'item_id' => (int) $data['item_id'],
            'supplier_id' => (int) $data['supplier_id'],
            'is_primary' => $request->boolean('is_primary'),
            'minimum_order_qty' => isset($data['minimum_order_qty']) ? (float) $data['minimum_order_qty'] : null,
            'lead_time_days' => isset($data['lead_time_days']) ? (int) $data['lead_time_days'] : null,
            'active' => $request->boolean('active', true),
        ];
    }

    private function categoryMappingState(int $categoryId): array
    {
        return SupplierCategoryMapping::query()
            ->where('item_category_id', $categoryId)
            ->get(['id', 'item_category_id', 'supplier_id', 'is_primary', 'lead_time_days', 'active'])
            ->map(fn ($mapping) => [
                'id' => $mapping->id,
                'item_category_id' => $mapping->item_category_id,
                'supplier_id' => $mapping->supplier_id,
                'is_primary' => $mapping->is_primary,
                'lead_time_days' => $mapping->lead_time_days,
                'active' => $mapping->active,
            ])
            ->values()
            ->all();
    }

    private function validateCategoryMapping(
        Request $request,
        ?SupplierCategoryMapping $mapping = null
    ): array {
        $data = $request->validate([
            'item_category_id' => [
                'required',
                'integer',
                Rule::exists('item_categories', 'id')->whereIn('kind', ['material', 'support', 'accessory', 'packaging']),
            ],
            'supplier_id' => [
                'required',
                'integer',
                'exists:suppliers,id',
                Rule::unique('supplier_category_mappings')->where(fn ($query) => $query
                    ->where('item_category_id', $request->integer('item_category_id')))
                    ->ignore($mapping?->id),
            ],
            'is_primary' => ['nullable', 'boolean'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'active' => ['nullable', 'boolean'],
        ]);

        return [
            'item_category_id' => (int) $data['item_category_id'],
            'supplier_id' => (int) $data['supplier_id'],
            'is_primary' => $request->boolean('is_primary'),
            'lead_time_days' => isset($data['lead_time_days']) ? (int) $data['lead_time_days'] : null,
            'active' => $request->boolean('active', true),
        ];
    }
}
