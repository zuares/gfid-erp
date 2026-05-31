<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemCategory::query()->withCount('items');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        if (($status = $request->input('status')) !== null && $status !== '') {
            $query->where('active', $status === 'active');
        }

        $categories = $query
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('master.item_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        ItemCategory::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'active' => isset($data['active']) ? (bool) $data['active'] : true,
        ]);

        return redirect()
            ->route('master.item_categories.index')
            ->with('success', 'Kategori item berhasil dibuat.');
    }

    public function update(Request $request, ItemCategory $item_category)
    {
        $data = $this->validateRequest($request, $item_category);

        $item_category->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'active' => isset($data['active']) ? (bool) $data['active'] : true,
        ]);

        return redirect()
            ->route('master.item_categories.index')
            ->with('success', 'Kategori item berhasil diperbarui.');
    }

    public function destroy(ItemCategory $item_category)
    {
        // Lindungi: jangan hapus kategori yang masih dipakai item.
        $usedCount = $item_category->items()->count();

        if ($usedCount > 0) {
            return redirect()
                ->route('master.item_categories.index')
                ->with('error', "Kategori \"{$item_category->name}\" masih dipakai {$usedCount} item dan tidak bisa dihapus.");
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
            'active' => ['nullable'],
        ]);
    }
}
