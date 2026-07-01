<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StorefrontProductCategoryController extends Controller
{
    public function index()
    {
        $categories = StorefrontProductCategory::withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.catalog.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.catalog.categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:80'],
            'slug'        => ['nullable', 'string', 'max:80', 'unique:storefront_product_categories,slug'],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug']       = Str::slug($data['slug'] ?: $data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active']  = true;

        StorefrontProductCategory::create($data);

        return redirect()->route('admin.catalog.categories.index')
            ->with('success', 'Kategori berhasil dibuat.');
    }

    public function edit(StorefrontProductCategory $category)
    {
        $category->load(['products' => fn($q) => $q->orderBy('sort_order')->orderBy('name')]);
        return view('admin.catalog.categories.edit', compact('category'));
    }

    public function update(Request $request, StorefrontProductCategory $category)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:80'],
            'slug'        => ['required', 'string', 'max:80', 'unique:storefront_product_categories,slug,' . $category->id],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active']  = $request->boolean('is_active', true);

        $category->update($data);

        return back()->with('success', 'Kategori disimpan.');
    }

    public function destroy(StorefrontProductCategory $category)
    {
        // Lepas semua produk dari kategori ini (set ke null)
        $category->products()->update(['category_id' => null]);
        $category->delete();

        return redirect()->route('admin.catalog.categories.index')
            ->with('success', 'Kategori dihapus. Produk yang ada dipindahkan ke "Tanpa Kategori".');
    }
}
