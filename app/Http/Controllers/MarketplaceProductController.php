<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\Store;
use App\Services\MarketplaceProductService;
use Illuminate\Http\Request;

class MarketplaceProductController extends Controller
{
    public function __construct(protected MarketplaceProductService $products) {}

    public function page()
    {
        return view('marketplace.products');
    }

    /**
     * Daftar produk lokal + filter.
     */
    public function index(Request $request)
    {
        $q = MarketplaceProduct::with(['store:id,name', 'models'])
            ->when($request->filled('store_id'), fn ($qq) => $qq->where('store_id', $request->integer('store_id')))
            ->when($request->filled('status'), fn ($qq) => $qq->where('item_status', $request->string('status')))
            ->when($request->filled('search'), function ($qq) use ($request) {
                $s = '%' . $request->string('search') . '%';
                $qq->where(fn ($w) => $w->where('item_name', 'like', $s)
                    ->orWhere('item_sku', 'like', $s)
                    ->orWhere('item_id', 'like', $s)
                    ->orWhereHas('models', fn ($m) => $m->where('model_sku', 'like', $s)));
            })
            ->orderByDesc('synced_at');

        $products = $q->limit(500)->get();

        // ── Status mapping per model (dipakai juga oleh sync order) ─────────
        $skus = $products->flatMap(fn ($p) => $p->models->pluck('model_sku'))
            ->filter()->unique()->values();

        $mappings = \App\Models\SkuMapping::whereIn('marketplace_sku', $skus)
            ->where(fn ($w) => $w->whereNull('channel_code')->orWhere('channel_code', 'shopee'))
            ->with('item:id,code,name')
            ->get()
            // channel-spesifik menang atas global
            ->sortBy(fn ($m) => $m->channel_code === null ? 1 : 0)
            ->keyBy('marketplace_sku');

        foreach ($products as $p) {
            foreach ($p->models as $m) {
                $map = $m->model_sku ? ($mappings[$m->model_sku] ?? null) : null;
                $m->setAttribute('mapping', $map ? [
                    'id'        => $map->id,
                    'item_id'   => $map->item_id,
                    'item_code' => $map->item?->code,
                    'item_name' => $map->item?->name,
                ] : null);
            }
        }

        return response()->json($products);
    }

    /**
     * Auto-map: model_sku yang persis sama dengan kode item internal
     * dibuatkan mapping global otomatis.
     */
    public function autoMap()
    {
        $unmappedSkus = \App\Models\MarketplaceProductModel::whereNotNull('model_sku')
            ->where('model_sku', '!=', '')
            ->pluck('model_sku')->unique()
            ->reject(fn ($sku) => \App\Models\SkuMapping::where('marketplace_sku', $sku)->exists())
            ->values();

        $created = 0;
        foreach ($unmappedSkus as $sku) {
            $item = \App\Models\Item::where('code', $sku)->where('active', 1)->first();
            if ($item) {
                \App\Models\SkuMapping::create([
                    'marketplace_sku' => $sku,
                    'channel_code'    => null, // global — berlaku semua channel
                    'item_id'         => $item->id,
                    'notes'           => 'auto-map dari tab Produk (kode sama persis)',
                ]);
                $created++;
            }
        }

        return response()->json([
            'created' => $created,
            'message' => $created > 0
                ? "Berhasil auto-map {$created} SKU (kode persis sama)."
                : 'Tidak ada SKU yang cocok persis dengan kode item internal.',
        ]);
    }

    /**
     * Sync produk dari Shopee (semua toko aktif atau satu toko).
     */
    public function sync(Request $request)
    {
        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')
            ->when($request->filled('store_id'), fn ($q) => $q->where('id', $request->integer('store_id')))
            ->get();

        $total = 0;
        $errors = [];
        foreach ($stores as $store) {
            $res = $this->products->syncProducts($store);
            $total += $res['synced'];
            $errors = array_merge($errors, array_map(fn ($e) => "[{$store->name}] {$e}", $res['errors']));
        }

        return response()->json([
            'synced'  => $total,
            'errors'  => $errors,
            'message' => "Berhasil sync {$total} produk." . ($errors ? ' Sebagian gagal — cek detail.' : ''),
        ]);
    }

    /**
     * Update stok. Body: stock_list: [{model_id, stock}]
     */
    public function updateStock(MarketplaceProduct $product, Request $request)
    {
        $data = $request->validate([
            'stock_list'            => 'required|array|min:1',
            'stock_list.*.model_id' => 'required',
            'stock_list.*.stock'    => 'required|integer|min:0',
        ]);

        $res = $this->products->updateStock($product, $data['stock_list']);

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal update stok di Shopee.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update harga. Body: price_list: [{model_id, original_price}]
     */
    public function updatePrice(MarketplaceProduct $product, Request $request)
    {
        $data = $request->validate([
            'price_list'                  => 'required|array|min:1',
            'price_list.*.model_id'       => 'required',
            'price_list.*.original_price' => 'required|numeric|min:100',
        ]);

        $res = $this->products->updatePrice($product, $data['price_list']);

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal update harga di Shopee.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Toggle tampil/sembunyi (unlist).
     */
    public function toggleUnlist(MarketplaceProduct $product, Request $request)
    {
        $unlist = $request->boolean('unlist');
        $res = $this->products->setUnlist($product, $unlist);

        if (! empty($res['error'])) {
            return response()->json(['message' => $res['message'] ?? 'Gagal ubah status tampil.'], 422);
        }

        return response()->json(['success' => true, 'item_status' => $product->fresh()->item_status]);
    }
}
