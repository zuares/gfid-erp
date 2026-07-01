<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorefrontProduct;
use App\Models\StorefrontProductCategory;
use App\Models\StorefrontProductVariant;
use App\Models\StorefrontProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class StorefrontProductCatalogController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $type           = $request->query('type', '');
        $unpublished    = $request->boolean('unpublished');
        $categorySlug   = $request->query('kategori', '');
        $audienceFilter = $request->query('audience', '');

        $query = StorefrontProduct::withCount(['variants', 'sizes'])
            ->with([
                'variants'  => fn($q) => $q->where('is_default', true)->limit(1),
                'category',
            ])
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($type) {
            $query->where('product_type', $type);
        }

        if ($unpublished) {
            $query->where('is_published', false);
        }

        if ($categorySlug) {
            $query->whereHas('category', fn($q) => $q->where('slug', $categorySlug));
        }

        if ($audienceFilter) {
            $query->where('audience', $audienceFilter);
        }

        $products   = $query->get();
        $categories = StorefrontProductCategory::orderBy('sort_order')->orderBy('name')->get();

        $counts = [
            'all'         => StorefrontProduct::count(),
            'regular'     => StorefrontProduct::where('product_type', 'regular')->count(),
            'jumbo'       => StorefrontProduct::where('product_type', 'jumbo')->count(),
            'unpublished' => StorefrontProduct::where('is_published', false)->count(),
        ];

        $audienceOptions = [
            'pria'     => 'Pria',
            'wanita'   => 'Wanita',
            'anak'     => 'Anak',
            'olahraga' => 'Olahraga',
            'unisex'   => 'Unisex',
        ];

        return view('admin.catalog.products.index', compact(
            'products', 'type', 'unpublished', 'counts', 'categories', 'categorySlug',
            'audienceFilter', 'audienceOptions'
        ));
    }

    // ─── Create / Store ───────────────────────────────────────────────────────

    public function create()
    {
        $categories      = StorefrontProductCategory::orderBy('sort_order')->orderBy('name')->get();
        $audienceOptions = ['pria' => 'Pria', 'wanita' => 'Wanita', 'anak' => 'Anak', 'olahraga' => 'Olahraga', 'unisex' => 'Unisex'];
        return view('admin.catalog.products.create', compact('categories', 'audienceOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'slug'         => ['nullable', 'string', 'max:120', 'unique:storefront_products,slug'],
            'description'  => ['nullable', 'string'],
            'product_type' => ['required', 'in:regular,jumbo'],
            'category_id'  => ['nullable', 'exists:storefront_product_categories,id'],
            'audience'     => ['nullable', 'in:pria,wanita,anak,olahraga,unisex'],
            'base_price'   => ['required', 'integer', 'min:0'],
            'label'        => ['nullable', 'string', 'max:40'],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
            'image'        => ['nullable', 'image', 'max:4096'],
            'image_url'    => ['nullable', 'max:500'],
        ]);

        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['is_published'] = false;
        $data['sort_order']   = $data['sort_order'] ?? 0;

        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('storefront/products', 'public');
        } elseif (empty($data['image_url'])) {
            unset($data['image_url']);
        }

        unset($data['image']);

        $product = StorefrontProduct::create($data);

        return redirect()
            ->route('admin.catalog.products.edit', $product)
            ->with('success', 'Produk berhasil dibuat! Tambahkan variant warna dan ukuran di bawah.');
    }

    // ─── Edit / Update ────────────────────────────────────────────────────────

    public function edit(StorefrontProduct $product)
    {
        $product->load(['variants', 'sizes']);
        $categories      = StorefrontProductCategory::orderBy('sort_order')->orderBy('name')->get();
        $audienceOptions = ['pria' => 'Pria', 'wanita' => 'Wanita', 'anak' => 'Anak', 'olahraga' => 'Olahraga', 'unisex' => 'Unisex'];
        return view('admin.catalog.products.edit', compact('product', 'categories', 'audienceOptions'));
    }

    public function update(Request $request, StorefrontProduct $product)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'slug'         => ['required', 'string', 'max:120', 'unique:storefront_products,slug,' . $product->id],
            'description'  => ['nullable', 'string'],
            'product_type' => ['required', 'in:regular,jumbo'],
            'base_price'   => ['required', 'integer', 'min:0'],
            'label'        => ['nullable', 'string', 'max:40'],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
            'category_id'  => ['nullable', 'exists:storefront_product_categories,id'],
            'audience'     => ['nullable', 'in:pria,wanita,anak,olahraga,unisex'],
            'image'        => ['nullable', 'image', 'max:4096'],
            'image_url'    => ['nullable', 'max:500'],
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_url && !str_starts_with($product->image_url, 'http')) {
                Storage::disk('public')->delete($product->image_url);
            }
            $data['image_url'] = $request->file('image')->store('storefront/products', 'public');
        } elseif (empty($data['image_url'])) {
            unset($data['image_url']);
        }

        unset($data['image']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $product->update($data);

        return back()->with('success', 'Info produk disimpan.');
    }

    public function destroy(StorefrontProduct $product)
    {
        foreach ($product->variants as $v) {
            if ($v->image_url && !str_starts_with($v->image_url, 'http')) {
                Storage::disk('public')->delete($v->image_url);
            }
        }
        if ($product->image_url && !str_starts_with($product->image_url, 'http')) {
            Storage::disk('public')->delete($product->image_url);
        }
        $product->delete();

        return redirect()
            ->route('admin.catalog.products.index')
            ->with('success', 'Produk dihapus.');
    }

    // ─── Toggle Publish ───────────────────────────────────────────────────────

    public function togglePublish(StorefrontProduct $product)
    {
        $product->update(['is_published' => !$product->is_published]);
        $msg = $product->is_published
            ? '"' . $product->name . '" dipublikasikan ke website.'
            : '"' . $product->name . '" disembunyikan dari website.';

        return back()->with('success', $msg);
    }

    // ─── Variants ─────────────────────────────────────────────────────────────

    public function storeVariant(Request $request, StorefrontProduct $product)
    {
        $data = $request->validate([
            'color_name'     => ['required', 'string', 'max:60'],
            'hex_color'      => ['nullable', 'string', 'max:20'],
            'image'          => ['nullable', 'image', 'max:4096'],
            'image_url'      => ['nullable', 'max:500'],
            'price_override' => ['nullable', 'integer', 'min:0'],
            'is_default'     => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('storefront/variants', 'public');
        } elseif (empty($data['image_url'])) {
            unset($data['image_url']);
        }
        unset($data['image']);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            $product->variants()->update(['is_default' => false]);
        }

        // Jadikan default otomatis kalau ini variant pertama
        if ($product->variants()->count() === 0) {
            $isDefault = true;
        }

        StorefrontProductVariant::create([
            'product_id'     => $product->id,
            'color_name'     => $data['color_name'],
            'hex_color'      => $data['hex_color'] ?? null,
            'image_url'      => $data['image_url'] ?? null,
            'price_override' => !empty($data['price_override']) ? $data['price_override'] : null,
            'is_default'     => $isDefault,
            'sort_order'     => $product->variants()->max('sort_order') + 1,
            'is_active'      => true,
        ]);

        return back()->with('success', 'Variant warna ditambahkan.');
    }

    public function updateVariant(Request $request, StorefrontProduct $product, StorefrontProductVariant $variant)
    {
        abort_if($variant->product_id !== $product->id, 404);

        $data = $request->validate([
            'color_name'     => ['required', 'string', 'max:60'],
            'hex_color'      => ['nullable', 'string', 'max:20'],
            'image'          => ['nullable', 'image', 'max:4096'],
            'image_url'      => ['nullable', 'max:500'],
            'price_override' => ['nullable', 'integer', 'min:0'],
            'is_default'     => ['nullable', 'boolean'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            if ($variant->image_url && !str_starts_with($variant->image_url, 'http')) {
                Storage::disk('public')->delete($variant->image_url);
            }
            $data['image_url'] = $request->file('image')->store('storefront/variants', 'public');
        } elseif (array_key_exists('image_url', $data) && empty($data['image_url'])) {
            // keep existing
            unset($data['image_url']);
        }
        unset($data['image']);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            $product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
        }

        $variant->update([
            'color_name'     => $data['color_name'],
            'hex_color'      => $data['hex_color'] ?? $variant->hex_color,
            'image_url'      => $data['image_url'] ?? $variant->image_url,
            'price_override' => !empty($data['price_override']) ? $data['price_override'] : null,
            'is_default'     => $isDefault,
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Variant diupdate.');
    }

    public function destroyVariant(StorefrontProduct $product, StorefrontProductVariant $variant)
    {
        abort_if($variant->product_id !== $product->id, 404);

        if ($variant->image_url && !str_starts_with($variant->image_url, 'http')) {
            Storage::disk('public')->delete($variant->image_url);
        }
        $variant->delete();

        return back()->with('success', 'Variant dihapus.');
    }

    // ─── Sizes ────────────────────────────────────────────────────────────────

    public function storeSize(Request $request, StorefrontProduct $product)
    {
        $data = $request->validate([
            'size_label'     => ['required', 'string', 'max:20'],
            'price_override' => ['nullable', 'integer', 'min:0'],
        ]);

        StorefrontProductSize::create([
            'product_id'     => $product->id,
            'size_label'     => $data['size_label'],
            'price_override' => !empty($data['price_override']) ? $data['price_override'] : null,
            'sort_order'     => $product->sizes()->max('sort_order') + 1,
            'is_active'      => true,
        ]);

        return back()->with('success', 'Ukuran ditambahkan.');
    }

    public function updateSize(Request $request, StorefrontProduct $product, StorefrontProductSize $size)
    {
        abort_if($size->product_id !== $product->id, 404);

        $data = $request->validate([
            'size_label'     => ['required', 'string', 'max:20'],
            'price_override' => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        $size->update([
            'size_label'     => $data['size_label'],
            'price_override' => !empty($data['price_override']) ? $data['price_override'] : null,
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Ukuran diupdate.');
    }

    public function destroySize(StorefrontProduct $product, StorefrontProductSize $size)
    {
        abort_if($size->product_id !== $product->id, 404);
        $size->delete();

        return back()->with('success', 'Ukuran dihapus.');
    }
}
