<?php

namespace App\Services;

use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductModel;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\Log;

class MarketplaceProductService
{
    public function __construct(protected ChannelManager $manager) {}

    /**
     * Sync semua produk sebuah toko (status NORMAL + UNLIST).
     * Return: ['synced' => n, 'errors' => []]
     */
    public function syncProducts(Store $store, array $statuses = ['NORMAL', 'UNLIST']): array
    {
        $driver  = $this->manager->driver($store);
        $itemIds = [];
        $errors  = [];

        // 1. Kumpulkan item_id per status
        foreach ($statuses as $status) {
            $offset = 0;
            while (true) {
                $res = $driver->getItemList($store, $status, $offset, 100);

                if (! empty($res['error'])) {
                    $errors[] = "get_item_list {$status}: " . ($res['message'] ?? $res['error']);
                    break;
                }

                foreach (data_get($res, 'response.item', []) as $it) {
                    if (! empty($it['item_id'])) {
                        $itemIds[(string) $it['item_id']] = $status;
                    }
                }

                if (! data_get($res, 'response.has_next_page', false)) break;
                $offset = (int) data_get($res, 'response.next_offset', $offset + 100);
                if ($offset <= 0) break;
            }
        }

        if (empty($itemIds)) {
            return ['synced' => 0, 'errors' => $errors];
        }

        // 2. Detail per chunk 50
        $synced = 0;
        foreach (array_chunk(array_keys($itemIds), 50) as $chunk) {
            $res = $driver->getItemBaseInfo($store, $chunk);
            if (! empty($res['error'])) {
                $errors[] = 'get_item_base_info: ' . ($res['message'] ?? $res['error']);
                continue;
            }

            // Extra info (sales/views/rating) — non-fatal jika gagal
            $extraMap = [];
            $extra = $driver->getItemExtraInfo($store, $chunk);
            foreach (data_get($extra, 'response.item_list', []) as $e) {
                $extraMap[(string) ($e['item_id'] ?? '')] = $e;
            }

            foreach (data_get($res, 'response.item_list', []) as $item) {
                try {
                    $this->upsertProduct($store, $item, $extraMap[(string) ($item['item_id'] ?? '')] ?? []);
                    $synced++;
                } catch (\Throwable $e) {
                    $errors[] = "item {$item['item_id']}: " . $e->getMessage();
                    Log::warning('Product sync: gagal upsert item', ['item_id' => $item['item_id'] ?? null, 'err' => $e->getMessage()]);
                }
            }
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    /**
     * Sync satu item (dipakai webhook item_update).
     */
    public function syncSingleItem(Store $store, $itemId): ?MarketplaceProduct
    {
        $driver = $this->manager->driver($store);
        $res = $driver->getItemBaseInfo($store, [(string) $itemId]);

        if (! empty($res['error'])) {
            Log::warning("Product sync: gagal ambil item {$itemId}: " . ($res['message'] ?? $res['error']));
            return null;
        }

        $item = data_get($res, 'response.item_list.0');
        if (! $item) return null;

        return $this->upsertProduct($store, $item, []);
    }

    /**
     * Update stok di Shopee + lokal.
     * $stockList: [['model_id' => '0', 'stock' => 10], ...]
     */
    public function updateStock(MarketplaceProduct $product, array $stockList): array
    {
        $driver = $this->manager->driver($product->store);
        $res = $driver->updateProductStock($product->store, $product->item_id, $stockList);

        if (! empty($res['error'])) {
            return $res;
        }

        foreach ($stockList as $s) {
            MarketplaceProductModel::where('marketplace_product_id', $product->id)
                ->where('model_id', (string) ($s['model_id'] ?? '0'))
                ->update(['stock' => (int) $s['stock']]);
        }
        $product->update([
            'stock_total' => (int) $product->models()->sum('stock'),
            'synced_at'   => now(),
        ]);

        return ['success' => true];
    }

    /**
     * Update harga di Shopee + lokal.
     * $priceList: [['model_id' => '0', 'original_price' => 125000], ...]
     */
    public function updatePrice(MarketplaceProduct $product, array $priceList): array
    {
        $driver = $this->manager->driver($product->store);
        $res = $driver->updateProductPrice($product->store, $product->item_id, $priceList);

        if (! empty($res['error'])) {
            return $res;
        }

        foreach ($priceList as $p) {
            MarketplaceProductModel::where('marketplace_product_id', $product->id)
                ->where('model_id', (string) ($p['model_id'] ?? '0'))
                ->update(['price' => (float) $p['original_price']]);
        }
        $prices = $product->models()->pluck('price')->filter();
        $product->update([
            'price_min' => $prices->min(),
            'price_max' => $prices->max(),
            'synced_at' => now(),
        ]);

        return ['success' => true];
    }

    /**
     * Unlist / tampilkan kembali item.
     */
    public function setUnlist(MarketplaceProduct $product, bool $unlist): array
    {
        $driver = $this->manager->driver($product->store);
        $res = $driver->unlistItems($product->store, [
            ['item_id' => $product->item_id, 'unlist' => $unlist],
        ]);

        if (! empty($res['error'])) {
            return $res;
        }

        // Cek failure list per-item dari response
        $failures = data_get($res, 'response.failure_list', []);
        foreach ($failures as $f) {
            if ((string) ($f['item_id'] ?? '') === (string) $product->item_id) {
                return ['error' => 'unlist_failed', 'message' => $f['failed_reason'] ?? 'Gagal ubah status tampil.'];
            }
        }

        $product->update([
            'item_status' => $unlist ? 'UNLIST' : 'NORMAL',
            'synced_at'   => now(),
        ]);

        return ['success' => true];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function upsertProduct(Store $store, array $item, array $extra = []): MarketplaceProduct
    {
        $itemId   = (string) $item['item_id'];
        $hasModel = (bool) ($item['has_model'] ?? false);

        // Harga & stok level item (untuk item tanpa varian)
        $priceInfo = data_get($item, 'price_info.0', []);
        $itemPrice = $priceInfo['original_price'] ?? $priceInfo['current_price'] ?? null;

        $stockInfo = data_get($item, 'stock_info_v2.seller_stock.0.stock')
            ?? data_get($item, 'stock_info_v2.summary_info.total_available_stock')
            ?? data_get($item, 'stock_info.0.current_stock');

        $product = MarketplaceProduct::updateOrCreate(
            ['store_id' => $store->id, 'item_id' => $itemId],
            [
                'item_name'   => $item['item_name'] ?? null,
                'item_sku'    => $item['item_sku'] ?? null,
                'item_status' => $item['item_status'] ?? null,
                'category_id' => isset($item['category_id']) ? (string) $item['category_id'] : null,
                'image_url'   => data_get($item, 'image.image_url_list.0'),
                'has_model'   => $hasModel,
                'sales'       => $extra['sale'] ?? $extra['sales'] ?? null,
                'views'       => $extra['views'] ?? null,
                'rating_star' => $extra['rating_star'] ?? null,
                'raw_json'    => $item,
                'synced_at'   => now(),
            ]
        );

        if ($hasModel) {
            $this->syncModels($store, $product);
        } else {
            // Item tanpa varian → simpan sebagai model_id 0
            MarketplaceProductModel::updateOrCreate(
                ['marketplace_product_id' => $product->id, 'model_id' => '0'],
                [
                    'model_name' => null,
                    'model_sku'  => $item['item_sku'] ?? null,
                    'price'      => $itemPrice,
                    'stock'      => (int) ($stockInfo ?? 0),
                    'raw_json'   => ['price_info' => $item['price_info'] ?? null, 'stock_info_v2' => $item['stock_info_v2'] ?? null],
                ]
            );
        }

        // Agregat harga & stok dari model
        $prices = $product->models()->pluck('price')->filter();
        $product->update([
            'price_min'   => $prices->min(),
            'price_max'   => $prices->max(),
            'stock_total' => (int) $product->models()->sum('stock'),
        ]);

        return $product;
    }

    private function syncModels(Store $store, MarketplaceProduct $product): void
    {
        $driver = $this->manager->driver($store);
        $res = $driver->getProductModelList($store, $product->item_id);

        if (! empty($res['error'])) {
            Log::warning("Product sync: gagal ambil model item {$product->item_id}: " . ($res['message'] ?? $res['error']));
            return;
        }

        // Nama varian dari kombinasi tier_variation
        $tiers = data_get($res, 'response.tier_variation', []);
        $keptIds = [];

        foreach (data_get($res, 'response.model', []) as $m) {
            $modelId = (string) ($m['model_id'] ?? '');
            if ($modelId === '') continue;

            // model_name langsung, atau rakit dari tier_index
            $name = $m['model_name'] ?? null;
            if (! $name && ! empty($m['tier_index']) && ! empty($tiers)) {
                $parts = [];
                foreach ($m['tier_index'] as $tierPos => $optIdx) {
                    $opt = data_get($tiers, "{$tierPos}.option_list.{$optIdx}.option");
                    if ($opt) $parts[] = $opt;
                }
                $name = implode(' / ', $parts) ?: null;
            }

            $priceInfo = data_get($m, 'price_info.0', []);
            $stock = data_get($m, 'stock_info_v2.seller_stock.0.stock')
                ?? data_get($m, 'stock_info_v2.summary_info.total_available_stock')
                ?? data_get($m, 'stock_info.0.current_stock');

            $row = MarketplaceProductModel::updateOrCreate(
                ['marketplace_product_id' => $product->id, 'model_id' => $modelId],
                [
                    'model_name' => $name,
                    'model_sku'  => $m['model_sku'] ?? null,
                    'price'      => $priceInfo['original_price'] ?? $priceInfo['current_price'] ?? null,
                    'stock'      => (int) ($stock ?? 0),
                    'raw_json'   => $m,
                ]
            );
            $keptIds[] = $row->id;
        }

        // Hapus model yang sudah tidak ada di Shopee
        if (! empty($keptIds)) {
            $product->models()->whereNotIn('id', $keptIds)->delete();
        }
    }
}
