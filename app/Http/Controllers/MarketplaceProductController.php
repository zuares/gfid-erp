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

        return response()->json($q->limit(500)->get());
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
