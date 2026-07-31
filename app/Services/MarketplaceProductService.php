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
        $now = now();

        foreach (array_chunk(array_keys($itemIds), 50) as $chunk) {
            $res = $driver->getItemBaseInfo($store, $chunk);
            if (! empty($res['error'])) {
                $errors[] = 'get_item_base_info: ' . ($res['message'] ?? $res['error']);
                continue;
            }

            // Extra info (sales/views/rating)
            $extraMap = [];
            $extra = $driver->getItemExtraInfo($store, $chunk);
            foreach (data_get($extra, 'response.item_list', []) as $e) {
                $extraMap[(string) ($e['item_id'] ?? '')] = $e;
            }

            $productBatch = [];
            $itemList = data_get($res, 'response.item_list', []);

            foreach ($itemList as $item) {
                $itemId = (string) $item['item_id'];
                $hasModel = (bool) ($item['has_model'] ?? false);
                $extraData = $extraMap[$itemId] ?? [];

                $productBatch[] = [
                    'store_id'    => $store->id,
                    'item_id'     => $itemId,
                    'item_name'   => $item['item_name'] ?? null,
                    'item_sku'    => $item['item_sku'] ?? null,
                    'item_status' => $item['item_status'] ?? null,
                    'category_id' => isset($item['category_id']) ? (string) $item['category_id'] : null,
                    'image_url'   => data_get($item, 'image.image_url_list.0'),
                    'has_model'   => $hasModel,
                    'sales'       => $extraData['sale'] ?? $extraData['sales'] ?? null,
                    'views'       => $extraData['views'] ?? null,
                    'rating_star' => $extraData['rating_star'] ?? null,
                    'raw_json'    => json_encode($item),
                    'synced_at'   => $now,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            if (empty($productBatch)) continue;

            // 1. Bulk Upsert Products
            MarketplaceProduct::upsert(
                $productBatch,
                ['store_id', 'item_id'],
                ['item_name', 'item_sku', 'item_status', 'category_id', 'image_url', 'has_model', 'sales', 'views', 'rating_star', 'raw_json', 'synced_at', 'updated_at']
            );
            $synced += count($productBatch);

            // 2. Ambil ID product yang baru di-upsert untuk foreign key models
            $insertedProducts = MarketplaceProduct::where('store_id', $store->id)
                ->whereIn('item_id', $chunk)
                ->get()
                ->keyBy('item_id');

            // 3. Kumpulkan models
            $modelBatch = [];
            $productsToUpdateTotals = [];

            foreach ($itemList as $item) {
                $itemId = (string) $item['item_id'];
                $product = $insertedProducts->get($itemId);
                if (!$product) continue;

                $hasModel = (bool) ($item['has_model'] ?? false);
                
                $priceInfo = data_get($item, 'price_info.0', []);
                $itemPrice = $priceInfo['current_price'] ?? $priceInfo['original_price'] ?? null;
                $stockInfo = data_get($item, 'stock_info_v2.seller_stock.0.stock')
                    ?? data_get($item, 'stock_info_v2.summary_info.total_available_stock')
                    ?? data_get($item, 'stock_info.0.current_stock');

                $productModelsData = [];

                if ($hasModel) {
                    $modelRes = $driver->getProductModelList($store, $itemId);
                    if (! empty($modelRes['error'])) {
                        Log::warning("Product sync: gagal ambil model item {$itemId}: " . ($modelRes['message'] ?? $modelRes['error']));
                        continue;
                    }

                    $tiers = data_get($modelRes, 'response.tier_variation', []);
                    foreach (data_get($modelRes, 'response.model', []) as $m) {
                        $modelId = (string) ($m['model_id'] ?? '');
                        if ($modelId === '') continue;

                        $name = $m['model_name'] ?? null;
                        if (! $name && ! empty($m['tier_index']) && ! empty($tiers)) {
                            $parts = [];
                            foreach ($m['tier_index'] as $tierPos => $optIdx) {
                                $opt = data_get($tiers, "{$tierPos}.option_list.{$optIdx}.option");
                                if ($opt) $parts[] = $opt;
                            }
                            $name = implode(' / ', $parts) ?: null;
                        }

                        $mPriceInfo = data_get($m, 'price_info.0', []);
                        $mStock = data_get($m, 'stock_info_v2.seller_stock.0.stock')
                            ?? data_get($m, 'stock_info_v2.summary_info.total_available_stock')
                            ?? data_get($m, 'stock_info.0.current_stock');

                        $productModelsData[] = [
                            'marketplace_product_id' => $product->id,
                            'model_id'   => $modelId,
                            'model_name' => $name,
                            'model_sku'  => $m['model_sku'] ?? null,
                            'price'      => $mPriceInfo['current_price'] ?? $mPriceInfo['original_price'] ?? null,
                            'stock'      => (int) ($mStock ?? 0),
                            'raw_json'   => json_encode($m),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                } else {
                    $productModelsData[] = [
                        'marketplace_product_id' => $product->id,
                        'model_id'   => '0',
                        'model_name' => null,
                        'model_sku'  => $item['item_sku'] ?? null,
                        'price'      => $itemPrice,
                        'stock'      => (int) ($stockInfo ?? 0),
                        'raw_json'   => json_encode(['price_info' => $item['price_info'] ?? null, 'stock_info_v2' => $item['stock_info_v2'] ?? null]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (!empty($productModelsData)) {
                    array_push($modelBatch, ...$productModelsData);
                    $productsToUpdateTotals[$product->id] = $productModelsData;
                }
            }

            // 4. Bulk Upsert Models
            if (!empty($modelBatch)) {
                MarketplaceProductModel::upsert(
                    $modelBatch,
                    ['marketplace_product_id', 'model_id'],
                    ['model_name', 'model_sku', 'price', 'stock', 'raw_json', 'updated_at']
                );
            }

            // 5. Update Total Stock & Price Min/Max efficiently
            $updateProductTotals = [];
            foreach ($productsToUpdateTotals as $pId => $models) {
                $prices = array_filter(array_column($models, 'price'), fn($p) => $p !== null);
                $updateProductTotals[] = [
                    'id' => $pId,
                    'price_min' => !empty($prices) ? min($prices) : null,
                    'price_max' => !empty($prices) ? max($prices) : null,
                    'stock_total' => array_sum(array_column($models, 'stock')),
                ];
            }

            // Memanfaatkan batch update via query builder (karena MySql tidak support update batch langsung tanpa CASE WHEN, 
            // kita loop ringan, atau biarkan loop query karena ini sudah tersaring hanya utk product yg terupdate).
            // Berhubung ini sudah di level product_id, kita bisa loop karena sangat sedikit.
            foreach ($updateProductTotals as $pTotal) {
                MarketplaceProduct::where('id', $pTotal['id'])->update([
                    'price_min' => $pTotal['price_min'],
                    'price_max' => $pTotal['price_max'],
                    'stock_total' => $pTotal['stock_total'],
                ]);
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
     * $priceList: [['model_id' => '0', 'original_price' => 125000, 'discount_price' => 110000], ...]
     */
    public function updatePrice(MarketplaceProduct $product, array $priceList): array
    {
        $driver = $this->manager->driver($product->store);

        $originalPriceList = array_map(function ($p) {
            return [
                'model_id' => $p['model_id'],
                'original_price' => $p['original_price'],
            ];
        }, $priceList);

        $res = $driver->updateProductPrice($product->store, $product->item_id, $originalPriceList);

        if (! empty($res['error'])) {
            return $res;
        }

        // 2. Handle Discount Price
        $hasDiscount = false;
        foreach ($priceList as $p) {
            if (isset($p['discount_price']) && $p['discount_price'] < $p['original_price']) {
                $hasDiscount = true;
                break;
            }
        }

        if ($hasDiscount) {
            $discountId = $this->findDiscountCampaignId($product->store, 'GFID-Harga-Jual');

            if (! $discountId) {
                $newD = $driver->addDiscount($product->store, 'GFID-Harga-Jual-' . date('Ym'), time(), time() + (86400 * 180));
                if (! empty($newD['error'])) {
                    Log::error("Failed to create discount: " . ($newD['message'] ?? $newD['error']));
                } else {
                    $discountId = data_get($newD, 'response.discount_id');
                }
            }

            if ($discountId) {
                $itemList = $this->buildDiscountItemList($product, $priceList);

                $addRes = $driver->addDiscountItem($product->store, $discountId, $itemList);

                // Jika gagal karena item sudah ada di dalam diskon ini, maka update item tersebut
                if (! empty($addRes['error']) && str_contains(strtolower($addRes['message'] ?? ''), 'exists')) {
                    $driver->updateDiscountItem($product->store, $discountId, $itemList);
                }
            }
        }

        foreach ($priceList as $p) {
            $model = MarketplaceProductModel::where('marketplace_product_id', $product->id)
                ->where('model_id', (string) ($p['model_id'] ?? '0'))
                ->first();
                
            if ($model) {
                $raw = is_string($model->raw_json) ? json_decode($model->raw_json, true) : ($model->raw_json ?? []);
                if (!isset($raw['price_info'])) $raw['price_info'] = [[]];
                $raw['price_info'][0]['original_price'] = (float) $p['original_price'];
                if (isset($p['discount_price'])) {
                    $raw['price_info'][0]['current_price'] = (float) $p['discount_price'];
                }
                
                $model->update([
                    'price' => (float) $p['original_price'],
                    'raw_json' => $raw
                ]);
            }
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
        $itemPrice = $priceInfo['current_price'] ?? $priceInfo['original_price'] ?? null;

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
                    'price'      => $priceInfo['current_price'] ?? $priceInfo['original_price'] ?? null,
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

    public function updateSku(MarketplaceProduct $product, string $newSku): array
    {
        $driver = $this->manager->driver($product->store);
        $res = $driver->updateItemBaseInfo($product->store, (int)$product->item_id, ['item_sku' => $newSku]);

        if (!empty($res['error'])) {
            throw new \Exception($res['message'] ?? $res['error']);
        }

        $product->update(['item_sku' => $newSku]);
        return $res;
    }

    public function updateModelSku(MarketplaceProductModel $model, string $newSku): array
    {
        $product = $model->product;
        $driver = $this->manager->driver($product->store);

        $modelsParam = [
            [
                'model_id' => (int)$model->model_id,
                'model_sku' => $newSku,
            ]
        ];

        $res = $driver->updateModel($product->store, (int)$product->item_id, $modelsParam);

        if (!empty($res['error'])) {
            throw new \Exception($res['message'] ?? $res['error']);
        }

        $model->update(['model_sku' => $newSku]);
        return $res;
    }

    /**
     * Update metadata promo diskon Shoppe yang sudah ada.
     */
    public function updateDiscount(
        Store $store,
        int $discountId,
        ?string $discountName = null,
        ?int $startTime = null,
        ?int $endTime = null
    ): array {
        $driver = $this->manager->driver($store);

        return $driver->updateDiscount($store, $discountId, $discountName, $startTime, $endTime);
    }

    /**
     * Hapus satu item/model dari promo diskon Shoppe.
     */
    public function deleteDiscountItem(Store $store, int $discountId, int $itemId, int $modelId = 0): array
    {
        $driver = $this->manager->driver($store);

        return $driver->deleteDiscountItem($store, $discountId, $itemId, $modelId);
    }

    private function findDiscountCampaignId(Store $store, string $namePrefix): ?int
    {
        $driver = $this->manager->driver($store);

        foreach (['ongoing', 'upcoming'] as $status) {
            $discounts = $driver->getDiscountList($store, $status);
            if (! empty($discounts['error'])) {
                continue;
            }

            foreach (data_get($discounts, 'response.discount_list', []) as $discount) {
                if (str_contains((string) ($discount['discount_name'] ?? ''), $namePrefix)) {
                    return (int) ($discount['discount_id'] ?? 0) ?: null;
                }
            }
        }

        return null;
    }

    private function buildDiscountItemList(MarketplaceProduct $product, array $priceList): array
    {
        return [
            [
                'item_id' => (int) $product->item_id,
                'model_list' => array_map(function ($p) {
                    return [
                        'model_id' => (int) ($p['model_id'] ?? 0),
                        'model_promotion_price' => (float) ($p['discount_price'] ?? 0),
                    ];
                }, $priceList),
            ],
        ];
    }
}
