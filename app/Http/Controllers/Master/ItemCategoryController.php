<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));
        $query = ItemCategory::query()->withCount([
            'items',
            'pieceRates',
            'pieceworkPayrollLines',
            'cuttingJobBundles',
            'supplierCategoryMapping',
        ]);
        $kindLabels = ItemCategory::kindLabels();
        $kindCounts = ItemCategory::query()
            ->selectRaw('kind, COUNT(*) as total')
            ->groupBy('kind')
            ->pluck('total', 'kind');
        $kindItemCounts = Item::query()
            ->join('item_categories as c', 'c.id', '=', 'items.item_category_id')
            ->selectRaw('c.kind, COUNT(items.id) as total')
            ->groupBy('c.kind')
            ->pluck('total', 'c.kind');
        $totalCategories = ItemCategory::count();
        $activeCount = ItemCategory::where('active', true)->count();
        $usedCount = ItemCategory::has('items')->count();
        $emptyCount = ItemCategory::doesntHave('items')->count();
        $kindItems = null;
        $itemCategoryOptions = ItemCategory::query()
            ->orderByRaw("CASE kind
                WHEN 'product' THEN 1
                WHEN 'material' THEN 2
                WHEN 'support' THEN 3
                WHEN 'accessory' THEN 4
                WHEN 'packaging' THEN 5
                ELSE 9
            END")
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'kind', 'active']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        if (($status = $request->input('status')) !== null && $status !== '') {
            $query->where('active', $status === 'active');
        }

        if (($kind = $request->input('kind')) !== null && $kind !== '') {
            $query->where('kind', $kind);

            $itemQuery = Item::query()
                ->with('category')
                ->withCount('barcodes')
                ->whereHas('category', fn ($q) => $q->where('kind', $kind));

            if ($search) {
                $itemQuery->where(function ($q) use ($search) {
                    $q->where('code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%');
                });
            }

            if ($status !== null && $status !== '') {
                $itemQuery->where('active', $status === 'active');
            }

            $kindItems = $itemQuery
                ->orderBy('code')
                ->paginate(100, ['*'], 'items_page')
                ->withQueryString();
        }

        $categories = $query
            ->orderByRaw("CASE kind
                WHEN 'product' THEN 1
                WHEN 'material' THEN 2
                WHEN 'support' THEN 3
                WHEN 'accessory' THEN 4
                WHEN 'packaging' THEN 5
                ELSE 9
            END")
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('master.item_categories.index', compact(
            'categories',
            'kindLabels',
            'kindCounts',
            'kindItemCounts',
            'kindItems',
            'totalCategories',
            'activeCount',
            'usedCount',
            'emptyCount',
            'itemCategoryOptions',
        ));
    }

    public function store(Request $request)
    {
        $this->normalizeRequest($request);
        $data = $this->validateRequest($request);

        ItemCategory::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'kind' => $data['kind'],
            'active' => isset($data['active']) ? (bool) $data['active'] : true,
        ]);

        return redirect()
            ->route('master.item_categories.index')
            ->with('success', 'Kategori item berhasil dibuat.');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

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
            'ok' => true,
            'category' => [
                'id' => (int) $category->id,
                'code' => (string) $category->code,
                'name' => (string) $category->name,
                'kind' => (string) $category->kind,
            ],
        ], 201);
    }

    public function update(Request $request, ItemCategory $item_category)
    {
        $this->normalizeRequest($request);
        $data = $this->validateRequest($request, $item_category);

        $item_category->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'kind' => $data['kind'],
            'active' => isset($data['active']) ? (bool) $data['active'] : true,
        ]);

        return redirect()
            ->route('master.item_categories.index')
            ->with('success', 'Kategori item berhasil diperbarui.');
    }

    public function destroy(ItemCategory $item_category)
    {
        $usage = [
            'item' => $item_category->items()->count(),
            'tarif piece rate' => $item_category->pieceRates()->count(),
            'riwayat payroll' => $item_category->pieceworkPayrollLines()->count(),
            'bundle cutting' => $item_category->cuttingJobBundles()->count(),
            'mapping supplier' => $item_category->supplierCategoryMapping()->count(),
        ];
        $usage = array_filter($usage);

        if ($usage !== []) {
            $usageSummary = collect($usage)
                ->map(fn (int $count, string $label) => "{$count} {$label}")
                ->implode(', ');

            return redirect()
                ->route('master.item_categories.index')
                ->with('error', "Kategori \"{$item_category->name}\" masih memiliki referensi ({$usageSummary}) dan tidak bisa dihapus.");
        }

        $item_category->delete();

        return redirect()
            ->route('master.item_categories.index')
            ->with('success', 'Kategori item berhasil dihapus.');
    }

    protected function validateRequest(Request $request, ?ItemCategory $category = null): array
    {
        $idToIgnore = $category?->id;

        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('item_categories', 'code')->ignore($idToIgnore),
            ],
            'name' => ['required', 'string', 'max:190'],
            'kind' => ['required', 'string', Rule::in(array_keys(ItemCategory::kindLabels()))],
            'active' => ['sometimes', 'boolean'],
        ]);
    }

    protected function normalizeRequest(Request $request): void
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
            'name' => trim((string) $request->input('name')),
        ]);
    }
}
