<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StorefrontProduct;
use App\Models\StorefrontProductCategory;
use App\Models\StorefrontProductVariant;
use App\Models\StorefrontProductSize;
use App\Models\StorefrontVariantItemMapping;
use App\Services\ProductRankingService;
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

    public function suggestItems(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = min(12, max(1, (int) $request->query('limit', 8)));
        $excludedCodes = ['REJECT', 'REJ-CUT', 'REJ-SEW', 'REJ-FIN'];

        $items = Item::query()
            ->where('active', true)
            ->when($q !== '', function ($query) use ($q) {
                $tokens = preg_split('/[\s\-\/_]+/', $q, -1, PREG_SPLIT_NO_EMPTY);
                $compact = preg_replace('/[^A-Za-z0-9]/', '', $q);

                $query->where(function ($inner) use ($tokens, $compact) {
                    foreach ($tokens as $token) {
                        $like = '%' . $token . '%';
                        $inner->where(function ($part) use ($like) {
                            $part->where('code', 'like', $like)
                                ->orWhere('name', 'like', $like);
                        });
                    }

                    if (strlen($compact) >= 2) {
                        $inner->orWhere('code', 'like', '%' . implode('%', str_split($compact)) . '%');
                    }
                });
            })
            ->withSum(['inventoryStocks as available_stock' => function ($query) use ($excludedCodes) {
                $query->whereHas('warehouse', fn ($warehouse) => $warehouse->whereNotIn('code', $excludedCodes));
            }], 'qty')
            ->orderBy('code')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $items->map(fn (Item $item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'on_hand' => (float) ($item->available_stock ?? 0),
            ])->values(),
        ]);
    }

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
            'weight_kg'    => ['nullable', 'numeric', 'min:0', 'max:999'],
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
        $product->load([
            'variants.itemMappings.item.inventoryStocks.warehouse',
            'sizes',
            'variantItemMappings.item.inventoryStocks.warehouse',
        ]);
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
            'weight_kg'    => ['nullable', 'numeric', 'min:0', 'max:999'],
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

    public function updateVariantItems(Request $request, StorefrontProduct $product)
    {
        $data = $request->validate([
            'mappings' => ['nullable', 'array'],
            'mappings.*.*.item_id' => ['nullable', 'exists:items,id'],
            'mappings.*.*.price_override' => ['nullable', 'integer', 'min:0'],
            'mappings.*.*.stock_override' => ['nullable', 'integer', 'min:0'],
        ]);

        $variantIds = $product->variants()->pluck('id')->map(fn($id) => (int) $id)->all();
        $sizeIds = $product->sizes()->pluck('id')->map(fn($id) => (int) $id)->all();

        foreach (($data['mappings'] ?? []) as $variantId => $sizeRows) {
            $variantId = (int) $variantId;
            if (! in_array($variantId, $variantIds, true)) {
                continue;
            }

            foreach ((array) $sizeRows as $sizeId => $row) {
                $sizeId = (int) $sizeId;
                if (! in_array($sizeId, $sizeIds, true)) {
                    continue;
                }

                $itemId = filled($row['item_id'] ?? null) ? (int) $row['item_id'] : null;
                $priceOverride = isset($row['price_override']) && $row['price_override'] !== '' ? (int) $row['price_override'] : null;
                $stockOverride = isset($row['stock_override']) && $row['stock_override'] !== '' ? (int) $row['stock_override'] : null;

                if (! $itemId && $priceOverride === null && $stockOverride === null) {
                    StorefrontVariantItemMapping::query()
                        ->where('variant_id', $variantId)
                        ->where('size_id', $sizeId)
                        ->delete();
                    continue;
                }

                StorefrontVariantItemMapping::updateOrCreate(
                    [
                        'variant_id' => $variantId,
                        'size_id' => $sizeId,
                    ],
                    [
                        'product_id' => $product->id,
                        'item_id' => $itemId,
                        'price_override' => $priceOverride,
                        'stock_override' => $stockOverride,
                    ]
                );
            }
        }

        return redirect()
            ->route('admin.catalog.products.edit', $product)
            ->with('success', 'Mapping item internal disimpan.')
            ->withFragment('tab-mapping');
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

    // ─── Ranking Overview + Overrides ────────────────────────────────────────

    /**
     * Halaman overview ranking semua produk.
     * Route: GET /admin/catalog/products/ranking
     */
    public function rankingOverview()
    {
        $products = StorefrontProduct::where('is_published', true)
            ->with(['variants' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderByRaw('rank_position IS NULL ASC')
            ->orderBy('rank_position')
            ->orderBy('name')
            ->get();

        $lastUpdated = $products->whereNotNull('rank_updated_at')->max('rank_updated_at');
        $rankedCount = $products->whereNotNull('rank_position')->count();
        $pinnedCount = $products->where('is_pinned', true)->count();

        return view('admin.catalog.products.ranking', compact(
            'products', 'lastUpdated', 'rankedCount', 'pinnedCount'
        ));
    }

    /**
     * Simpan override ranking (pin, boost, featured_until, stock) untuk satu produk.
     * Route: PATCH /admin/catalog/products/{product}/ranking
     */
    public function updateRanking(Request $request, StorefrontProduct $product)
    {
        $data = $request->validate([
            'stock'          => ['nullable', 'integer', 'min:0'],
            'is_pinned'      => ['nullable', 'boolean'],
            'pin_position'   => ['nullable', 'integer', 'min:1', 'max:9999'],
            'manual_boost'   => ['nullable', 'numeric', 'min:0', 'max:5'],
            'featured_until' => ['nullable', 'date'],
        ]);

        $product->update([
            'stock'          => $data['stock'] ?? $product->stock,
            'is_pinned'      => $request->boolean('is_pinned'),
            'pin_position'   => $request->boolean('is_pinned') ? ($data['pin_position'] ?? null) : null,
            'manual_boost'   => $data['manual_boost'] ?? 0,
            'featured_until' => $data['featured_until'] ?? null,
        ]);

        return redirect()
            ->route('admin.catalog.products.edit', $product)
            ->with('success', 'Ranking override disimpan.')
            ->withFragment('tab-ranking');
    }

    /**
     * Jalankan ulang ranking semua produk sekarang (dari browser).
     * Route: POST /admin/catalog/products/rank-now
     */
    public function rankNow(ProductRankingService $service)
    {
        try {
            $results = $service->recalculate();
            $count   = count($results);
            return back()->with('success', "Ranking diperbarui: {$count} produk dihitung ulang.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghitung ranking: ' . $e->getMessage());
        }
    }
}
