<?php

namespace App\Services;

use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductChangeLog;
use App\Models\MarketplaceProductImage;
use App\Models\MarketplaceProductModel;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Marketplace\Ads\AdsExperimentService;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\Log;

class MarketplaceProductService
{
    public function __construct(
        protected MarketplaceApiGateway $gateway,
        protected ?ChannelManager $manager = null,
    ) {}

    /**
     * Sync semua produk sebuah toko (status NORMAL + UNLIST).
     * Return: ['synced' => n, 'errors' => []]
     */
    public function syncProducts(Store $store, array $statuses = ['NORMAL', 'UNLIST']): array
    {
        $driver  = $this->gateway;
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
        $driver = $this->gateway;
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
     * Ambil snapshot detail terbaru untuk editor produk.
     * Tidak mengirim mutation ke Shopee; gallery lokal disegarkan dari snapshot.
     */
    public function getEditData(MarketplaceProduct $product): array
    {
        $store = $product->store;
        $channel = strtolower((string) ($store?->channel?->code ?? ''));

        if (! in_array($channel, ['shopee', 'shp'], true)) {
            return [
                'error' => 'unsupported_channel',
                'message' => 'Editor tahap ini hanya tersedia untuk produk Shopee.',
            ];
        }

        $base = $this->gateway->getItemBaseInfo($store, [(string) $product->item_id]);
        if (! empty($base['error'])) {
            return [
                'error' => $base['error'],
                'message' => $base['message'] ?? 'Shopee gagal mengembalikan detail produk.',
            ];
        }

        $item = data_get($base, 'response.item_list.0');
        if (! is_array($item)) {
            return [
                'error' => 'item_not_found',
                'message' => 'Produk tidak ditemukan di Shopee. Coba lakukan sync terlebih dahulu.',
            ];
        }

        $hasModel = (bool) ($item['has_model'] ?? $product->has_model);
        $tiers = [];
        $models = [];

        if ($hasModel) {
            $modelsResponse = $this->gateway->getProductModelList($store, (string) $product->item_id);
            if (! empty($modelsResponse['error'])) {
                return [
                    'error' => $modelsResponse['error'],
                    'message' => $modelsResponse['message'] ?? 'Daftar varian produk gagal diambil dari Shopee.',
                ];
            }

            $tiers = data_get($modelsResponse, 'response.tier_variation', []);
            $models = collect(data_get($modelsResponse, 'response.model', []))
                ->map(function (array $model) use ($tiers): array {
                    $name = $model['model_name'] ?? null;
                    if (! $name && ! empty($model['tier_index']) && ! empty($tiers)) {
                        $parts = [];
                        foreach ($model['tier_index'] as $tierPos => $optionIndex) {
                            $option = data_get($tiers, "{$tierPos}.option_list.{$optionIndex}.option");
                            if ($option) {
                                $parts[] = $option;
                            }
                        }
                        $name = implode(' / ', $parts) ?: null;
                    }

                    $priceInfo = data_get($model, 'price_info.0', []);
                    $stock = data_get($model, 'stock_info_v2.seller_stock.0.stock')
                        ?? data_get($model, 'stock_info_v2.summary_info.total_available_stock')
                        ?? data_get($model, 'stock_info.0.current_stock');

                    return [
                        'model_id' => (string) ($model['model_id'] ?? ''),
                        'model_name' => $name,
                        'model_sku' => $model['model_sku'] ?? null,
                        'original_price' => $priceInfo['original_price'] ?? null,
                        'current_price' => $priceInfo['current_price'] ?? $priceInfo['original_price'] ?? null,
                        'stock' => $stock !== null ? (int) $stock : 0,
                        'tier_index' => $model['tier_index'] ?? [],
                    ];
                })
                ->filter(fn (array $model) => $model['model_id'] !== '')
                ->values()
                ->all();
        } else {
            $priceInfo = data_get($item, 'price_info.0', []);
            $stock = data_get($item, 'stock_info_v2.seller_stock.0.stock')
                ?? data_get($item, 'stock_info_v2.summary_info.total_available_stock')
                ?? data_get($item, 'stock_info.0.current_stock');

            $models[] = [
                'model_id' => '0',
                'model_name' => null,
                'model_sku' => $item['item_sku'] ?? $product->item_sku,
                'original_price' => $priceInfo['original_price'] ?? null,
                'current_price' => $priceInfo['current_price'] ?? $priceInfo['original_price'] ?? null,
                'stock' => $stock !== null ? (int) $stock : 0,
                'tier_index' => [],
            ];
        }

        $imageIds = array_values(array_filter(array_map(
            static fn ($imageId) => trim((string) $imageId),
            (array) data_get($item, 'image.image_id_list', [])
        ), static fn (string $imageId) => $imageId !== ''));
        $imageUrls = array_values(array_filter(array_map(
            static fn ($url) => trim((string) $url),
            (array) data_get($item, 'image.image_url_list', [])
        ), static fn (string $url) => $url !== ''));

        $images = [];
        $imageCount = max(count($imageIds), count($imageUrls));
        for ($index = 0; $index < $imageCount; $index++) {
            $images[] = [
                'image_id' => $imageIds[$index] ?? null,
                'image_url' => $imageUrls[$index] ?? null,
                'sort_order' => $index,
                'is_cover' => $index === 0,
            ];
        }

        $this->syncProductImages($product, $item);

        return [
            'product' => [
                'local_id' => $product->id,
                'store_id' => $store->id,
                'store_name' => $store->name,
                'item_id' => (string) ($item['item_id'] ?? $product->item_id),
                'item_name' => $item['item_name'] ?? $product->item_name,
                'description' => $item['description'] ?? data_get($product->raw_json, 'description'),
                'item_sku' => $item['item_sku'] ?? $product->item_sku,
                'item_status' => $item['item_status'] ?? $product->item_status,
                'category_id' => isset($item['category_id']) ? (string) $item['category_id'] : $product->category_id,
            'has_model' => $hasModel,
                'weight' => $item['weight'] ?? null,
                'dimension' => $item['dimension'] ?? null,
            ],
            'images' => $images,
            'tiers' => $tiers,
            'models' => $models,
            'source' => [
                'channel' => 'shopee',
                'fetched_at' => now()->toIso8601String(),
                'base_info_endpoint' => '/api/v2/product/get_item_base_info',
                'model_list_endpoint' => '/api/v2/product/get_model_list',
            ],
        ];
    }

    /**
     * Update stok di Shopee + lokal.
     * $stockList: [['model_id' => '0', 'stock' => 10], ...]
     */
    public function updateStock(MarketplaceProduct $product, array $stockList): array
    {
        $driver = $this->channelDriver($product->store);
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
     * Update harga normal/base price di Shopee + lokal.
     * Harga promo dikelola oleh MarketplacePromotionService melalui campaign.
     * $priceList: [['model_id' => '0', 'original_price' => 125000], ...]
     */
    public function updatePrice(MarketplaceProduct $product, array $priceList): array
    {
        $driver = $this->channelDriver($product->store);
        $oldModels = MarketplaceProductModel::where('marketplace_product_id', $product->id)
            ->whereIn('model_id', collect($priceList)->pluck('model_id')->map(fn ($id) => (string) $id)->all())
            ->get()
            ->keyBy(fn (MarketplaceProductModel $model) => (string) $model->model_id);

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

        foreach ($priceList as $p) {
            $model = MarketplaceProductModel::where('marketplace_product_id', $product->id)
                ->where('model_id', (string) ($p['model_id'] ?? '0'))
                ->first();
                
            if ($model) {
                $raw = is_string($model->raw_json) ? json_decode($model->raw_json, true) : ($model->raw_json ?? []);
                if (!isset($raw['price_info'])) $raw['price_info'] = [[]];
                $raw['price_info'][0]['original_price'] = (float) $p['original_price'];
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

        try {
            $experimentChanges = collect($priceList)->map(function (array $price) use ($oldModels): array {
                $oldModel = $oldModels->get((string) ($price['model_id'] ?? '0'));
                $oldRaw = is_string($oldModel?->raw_json)
                    ? json_decode($oldModel->raw_json, true)
                    : ($oldModel?->raw_json ?? []);
                return [
                    'model_id' => (string) ($price['model_id'] ?? '0'),
                    'old_price' => data_get($oldRaw, 'price_info.0.original_price') ?? $oldModel?->price,
                    'new_price' => $price['original_price'] ?? null,
                ];
            })->all();

            app(AdsExperimentService::class)->recordPriceChange(
                product: $product->fresh(),
                changes: $experimentChanges,
                createdBy: auth()->id(),
            );
        } catch (\Throwable $e) {
            Log::warning('[MarketplaceProductService] failed to record price experiment', [
                'product_id' => $product->id,
                'store_id' => $product->store_id,
                'error' => $e->getMessage(),
            ]);
        }

        return ['success' => true];
    }

    /**
     * Update metadata dasar produk di marketplace dan salinan lokal.
     * Dipakai untuk perubahan judul dari workflow evaluasi iklan.
     */
    public function updateBaseInfo(MarketplaceProduct $product, array $data): array
    {
        $driver = $this->channelDriver($product->store);
        $payload = array_filter([
            'item_name' => $data['item_name'] ?? null,
            'description' => $data['description'] ?? null,
        ], static fn ($value) => $value !== null);

        if ($payload === []) {
            return ['error' => 'empty_update', 'message' => 'Tidak ada metadata produk yang diubah.'];
        }

        $res = $driver->updateItemBaseInfo($product->store, (int) $product->item_id, $payload);
        if (! empty($res['error'])) {
            return $res;
        }

        $raw = is_array($product->raw_json) ? $product->raw_json : [];
        if (array_key_exists('item_name', $payload)) {
            $raw['item_name'] = $payload['item_name'];
        }
        if (array_key_exists('description', $payload)) {
            $raw['description'] = $payload['description'];
        }

        $product->forceFill([
            'item_name' => $payload['item_name'] ?? $product->item_name,
            'raw_json' => $raw,
            'synced_at' => now(),
        ])->save();

        return ['success' => true];
    }

    /**
     * Upload file baru ke media space tanpa mengubah listing produk.
     */
    public function uploadImages(MarketplaceProduct $product, array $files): array
    {
        $result = $this->uploadImageFiles($product->store, $files);
        if (! empty($result['error'])) {
            return $result;
        }

        $this->logProductChange(
            product: $product,
            action: 'image_upload',
            status: 'success',
            after: [
                'image_ids' => collect($result['images'])->pluck('image_id')->values()->all(),
                'image_urls' => collect($result['images'])->pluck('image_url')->values()->all(),
            ],
        );

        return $result;
    }

    /**
     * Upload gambar untuk wizard produk baru. Belum ada product lokal sebelum
     * publish, sehingga audit listing dicatat setelah add_item berhasil.
     */
    public function uploadImagesForStore(Store $store, array $files): array
    {
        return $this->uploadImageFiles($store, $files);
    }

    public function getCreateCategories(Store $store): array
    {
        return $this->channelDriver($store)->getProductCategory($store, 'id');
    }

    /**
     * Ambil metadata kategori produk existing dari Shopee.
     * get_item_base_info hanya mengembalikan category_id, sehingga nama dan
     * breadcrumb perlu dicocokkan dengan tree get_category.
     */
    public function getCategoryInfo(MarketplaceProduct $product): array
    {
        $categoryId = (int) ($product->category_id ?? 0);
        if ($categoryId <= 0) {
            return [
                'category_id' => null,
                'category_name' => null,
                'category_path' => [],
                'attributes' => [],
                'attribute_count' => 0,
                'required_attribute_count' => 0,
            ];
        }

        $store = $product->store;
        $driver = $this->channelDriver($store);
        $categoryResult = $driver->getProductCategory($store, 'id');
        if (! empty($categoryResult['error'])) {
            return $categoryResult;
        }

        $categories = data_get($categoryResult, 'response.category_list')
            ?? data_get($categoryResult, 'category_list')
            ?? [];
        $path = $this->findCategoryPath(is_array($categories) ? $categories : [], $categoryId);

        $attributeResult = $driver->getProductAttributeTree($store, $categoryId, 'id');
        $attributes = data_get($attributeResult, 'response.attribute_list')
            ?? data_get($attributeResult, 'attribute_list')
            ?? [];
        $attributes = is_array($attributes) ? collect($attributes)->map(function ($attribute): array {
            return [
                'id' => (int) data_get($attribute, 'attribute_id', 0),
                'name' => (string) (data_get($attribute, 'name') ?? data_get($attribute, 'attribute_name') ?? 'Atribut'),
                'mandatory' => (bool) (data_get($attribute, 'mandatory') ?? data_get($attribute, 'is_mandatory') ?? false),
            ];
        })->values()->all() : [];

        return [
            'category_id' => $categoryId,
            'category_name' => $path !== [] ? $path[array_key_last($path)] : null,
            'category_path' => $path,
            'attributes' => $attributes,
            'attribute_count' => count($attributes),
            'required_attribute_count' => count(array_filter($attributes, fn (array $attribute): bool => $attribute['mandatory'])),
        ];
    }

    public function getCreateAttributes(Store $store, int $categoryId): array
    {
        return $this->channelDriver($store)->getProductAttributeTree($store, $categoryId, 'id');
    }

    private function findCategoryPath(array $nodes, int $categoryId, array $parents = []): array
    {
        $flat = [];
        $this->flattenCategoryNodes($nodes, $flat);
        if (isset($flat[$categoryId])) {
            $path = [];
            $currentId = $categoryId;
            $visited = [];
            while ($currentId > 0 && isset($flat[$currentId]) && ! isset($visited[$currentId])) {
                $visited[$currentId] = true;
                $current = $flat[$currentId];
                array_unshift($path, $current['name']);
                $currentId = $current['parent_id'];
            }
            if ($path !== []) {
                return $path;
            }
        }

        foreach ($nodes as $node) {
            $nodeId = (int) (data_get($node, 'category_id') ?? data_get($node, 'id') ?? 0);
            $nodeName = (string) (data_get($node, 'display_category_name')
                ?? data_get($node, 'category_name')
                ?? data_get($node, 'name')
                ?? 'Kategori');
            $path = [...$parents, $nodeName];
            if ($nodeId === $categoryId) {
                return $path;
            }

            $children = data_get($node, 'children')
                ?? data_get($node, 'sub_category_list')
                ?? data_get($node, 'category_list')
                ?? [];
            $found = $this->findCategoryPath(is_array($children) ? $children : [], $categoryId, $path);
            if ($found !== []) {
                return $found;
            }
        }

        return [];
    }

    private function flattenCategoryNodes(array $nodes, array &$flat): void
    {
        foreach ($nodes as $node) {
            $id = (int) (data_get($node, 'category_id') ?? data_get($node, 'id') ?? 0);
            if ($id > 0) {
                $flat[$id] = [
                    'name' => (string) (data_get($node, 'display_category_name')
                        ?? data_get($node, 'category_name')
                        ?? data_get($node, 'name')
                        ?? 'Kategori'),
                    'parent_id' => (int) (data_get($node, 'parent_category_id') ?? 0),
                ];
            }
            $children = data_get($node, 'children')
                ?? data_get($node, 'sub_category_list')
                ?? data_get($node, 'category_list')
                ?? [];
            if (is_array($children) && $children !== []) {
                $this->flattenCategoryNodes($children, $flat);
            }
        }
    }

    public function getCreateLogistics(Store $store): array
    {
        return $this->channelDriver($store)->getProductLogistics($store);
    }

    /**
     * Publish item baru lalu simpan item_id dan snapshot lokal bila detail dapat
     * langsung dibaca ulang dari Shopee.
     */
    public function createProduct(Store $store, array $payload): array
    {
        $driver = $this->channelDriver($store);
        if (! is_callable([$driver, 'addProduct'])) {
            return ['error' => 'not_supported', 'message' => 'Channel toko ini belum mendukung tambah produk.'];
        }

        $res = $driver->addProduct($store, $payload);
        if (! empty($res['error'])) {
            return $res;
        }

        $itemId = data_get($res, 'response.item_id')
            ?? data_get($res, 'item_id')
            ?? data_get($res, 'response.item.item_id');
        if (! $itemId) {
            return [
                'error' => 'item_id_missing',
                'message' => 'Shopee menerima request tetapi tidak mengembalikan item_id.',
                'response' => $res,
            ];
        }

        $detail = $driver->getItemBaseInfo($store, [(string) $itemId]);
        $item = data_get($detail, 'response.item_list.0');
        $warning = null;

        if (! is_array($item)) {
            $warning = 'Produk sudah dipublish, tetapi detail belum dapat disinkronkan. Jalankan sync produk untuk melengkapinya.';
            $item = [
                'item_id' => (string) $itemId,
                'item_name' => $payload['item_name'] ?? null,
                'item_sku' => $payload['item_sku'] ?? null,
                'item_status' => $payload['item_status'] ?? 'UNLIST',
                'category_id' => $payload['category_id'] ?? null,
                'image' => [
                    'image_id_list' => data_get($payload, 'image.image_id_list', []),
                ],
                'price_info' => [['original_price' => $payload['original_price'] ?? null]],
                'stock_info_v2' => ['seller_stock' => [['stock' => data_get($payload, 'seller_stock.0.stock', 0)]]],
            ];
        }

        $product = $this->upsertProduct($store, $item);
        $this->syncProductImages($product, $item);
        $this->logProductChange(
            product: $product,
            action: 'create',
            status: $warning ? 'warning' : 'success',
            after: ['item_id' => (string) $itemId, 'payload' => $payload],
            response: ['publish' => $res, 'detail' => $detail],
            error: $warning,
        );

        return array_filter([
            'success' => true,
            'item_id' => (string) $itemId,
            'product_id' => $product->id,
            'warning' => $warning,
        ], static fn ($value) => $value !== null);
    }

    /**
     * Simpan urutan gallery final ke Shopee.
     *
     * $imageOrder berisi token existing:{image_id} atau new:{index}. File baru
     * dipetakan berdasarkan urutan index tersebut agar gambar dapat dihapus,
     * ditambah, dan diurutkan dalam satu aksi.
     */
    public function updateImages(MarketplaceProduct $product, array $imageOrder, array $files = []): array
    {
        $driver = $this->channelDriver($product->store);
        $current = $driver->getItemBaseInfo($product->store, [(string) $product->item_id]);

        if (! empty($current['error'])) {
            return [
                'error' => $current['error'],
                'message' => $current['message'] ?? 'Gambar produk gagal dibaca dari Shopee.',
            ];
        }

        $item = data_get($current, 'response.item_list.0');
        if (! is_array($item)) {
            return ['error' => 'item_not_found', 'message' => 'Produk tidak ditemukan di Shopee.'];
        }

        $currentIds = array_values(array_filter(array_map(
            static fn ($imageId) => trim((string) $imageId),
            (array) data_get($item, 'image.image_id_list', [])
        ), static fn (string $imageId) => $imageId !== ''));
        $currentUrls = array_values(array_map(
            static fn ($url) => $url !== null ? trim((string) $url) : null,
            (array) data_get($item, 'image.image_url_list', [])
        ));
        $beforeImages = [
            'image_ids' => $currentIds,
            'image_urls' => $currentUrls,
        ];

        $imageOrder = array_values(array_filter(array_map(
            static fn ($token) => trim((string) $token),
            $imageOrder
        ), static fn (string $token) => $token !== ''));

        if ($imageOrder === [] && $files === []) {
            return ['error' => 'empty_images', 'message' => 'Pilih minimal satu gambar untuk disimpan.'];
        }

        $uploadedIds = [];
        if ($files !== []) {
            $uploadResult = $this->uploadImages($product, $files);
            if (! empty($uploadResult['error'])) {
                return $uploadResult;
            }
            $uploadedIds = collect($uploadResult['images'] ?? [])->pluck('image_id')->map(fn ($id) => (string) $id)->all();
        }

        $finalIds = [];
        $newIndex = 0;
        foreach ($imageOrder as $token) {
            if (str_starts_with($token, 'existing:')) {
                $imageId = substr($token, strlen('existing:'));
                if ($imageId !== '' && in_array($imageId, $currentIds, true)) {
                    $finalIds[] = $imageId;
                }
                continue;
            }

            if (str_starts_with($token, 'new:')) {
                $finalIds[] = $uploadedIds[$newIndex] ?? null;
                $newIndex++;
            }
        }

        if ($imageOrder === []) {
            $finalIds = array_merge($currentIds, $uploadedIds);
        }

        // Fallback untuk caller non-UI yang hanya mengirim existing IDs.
        if ($finalIds === [] && $imageOrder !== [] && $uploadedIds === []) {
            $finalIds = array_values(array_filter($imageOrder, fn (string $id) => in_array($id, $currentIds, true)));
        }

        $finalIds = array_values(array_unique(array_filter($finalIds)));
        if ($finalIds === []) {
            return ['error' => 'empty_images', 'message' => 'Gallery harus memiliki minimal satu gambar.'];
        }
        if (count($finalIds) > 9) {
            return ['error' => 'too_many_images', 'message' => 'Maksimal 9 gambar produk yang dapat disimpan.'];
        }

        $res = $driver->updateProductImages($product->store, (int) $product->item_id, $finalIds);
        if (! empty($res['error'])) {
            $this->logProductChange(
                product: $product,
                action: 'images',
                status: 'failed',
                before: $beforeImages,
                after: ['image_ids' => $finalIds],
                response: $res,
                error: $res['message'] ?? $res['error'],
            );

            return [
                'error' => $res['error'],
                'message' => $res['message'] ?? 'Urutan gallery gagal disimpan ke Shopee.',
            ];
        }

        // Refresh snapshot lokal agar thumbnail katalog tidak tertinggal.
        $fresh = $driver->getItemBaseInfo($product->store, [(string) $product->item_id]);
        $freshItem = data_get($fresh, 'response.item_list.0');
        if (! is_array($freshItem)) {
            // Remote update berhasil, tetapi jangan menimpa lokal dengan snapshot
            // sebelum perubahan. Biarkan sync berikutnya memperbaiki snapshot.
            $message = $fresh['message'] ?? 'Update gambar berhasil, tetapi verifikasi ulang ke Shopee gagal.';
            $this->logProductChange(
                product: $product,
                action: 'images',
                status: 'warning',
                before: $beforeImages,
                after: ['image_ids' => $finalIds],
                response: ['update' => $res, 'refresh' => $fresh],
                error: $message,
            );

            return [
                'success' => true,
                'image_ids' => $finalIds,
                'warning' => $message,
                'sync_required' => true,
            ];
        }

        $actualIds = array_values(array_filter(array_map(
            static fn ($imageId) => trim((string) $imageId),
            (array) data_get($freshItem, 'image.image_id_list', [])
        ), static fn (string $imageId) => $imageId !== ''));
        $actualUrls = array_values(array_map(
            static fn ($url) => $url !== null ? trim((string) $url) : null,
            (array) data_get($freshItem, 'image.image_url_list', [])
        ));
        $verificationWarning = $actualIds !== $finalIds
            ? 'Shopee menerima perubahan, tetapi urutan gambar aktual berbeda dari permintaan. Data lokal mengikuti hasil aktual Shopee.'
            : null;

        $product->forceFill([
            'item_name' => $freshItem['item_name'] ?? $product->item_name,
            'item_sku' => $freshItem['item_sku'] ?? $product->item_sku,
            'item_status' => $freshItem['item_status'] ?? $product->item_status,
            'category_id' => isset($freshItem['category_id']) ? (string) $freshItem['category_id'] : $product->category_id,
            'image_url' => $actualUrls[0] ?? null,
            'has_model' => (bool) ($freshItem['has_model'] ?? $product->has_model),
            'raw_json' => $freshItem,
            'synced_at' => now(),
        ])->save();

        $this->syncProductImages($product, $freshItem);
        $this->logProductChange(
            product: $product,
            action: 'images',
            status: $verificationWarning ? 'warning' : 'success',
            before: $beforeImages,
            after: ['image_ids' => $actualIds, 'image_urls' => $actualUrls],
            response: ['update' => $res, 'refresh' => $fresh],
            error: $verificationWarning,
        );

        return array_filter([
            'success' => true,
            'image_ids' => $actualIds,
            'warning' => $verificationWarning,
        ], static fn ($value) => $value !== null);
    }

    /**
     * Unlist / tampilkan kembali item.
     */
    public function setUnlist(MarketplaceProduct $product, bool $unlist): array
    {
        $driver = $this->channelDriver($product->store);
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

    private function uploadImageFiles(Store $store, array $files): array
    {
        if ($files === []) {
            return ['error' => 'empty_images', 'message' => 'Pilih minimal satu file gambar.'];
        }

        if (count($files) > 9) {
            return ['error' => 'too_many_images', 'message' => 'Maksimal 9 file dapat di-upload dalam satu batch.'];
        }

        $driver = $this->channelDriver($store);
        if (! is_callable([$driver, 'uploadProductImage'])) {
            return ['error' => 'not_supported', 'message' => 'Channel toko ini belum mendukung upload gambar.'];
        }

        $uploaded = [];
        foreach ($files as $file) {
            $contents = @file_get_contents($file->getRealPath());
            if ($contents === false) {
                return ['error' => 'image_read_failed', 'message' => 'File gambar tidak dapat dibaca server.'];
            }

            $upload = $driver->uploadProductImage(
                $store,
                $contents,
                $file->getClientOriginalName(),
                $file->getMimeType() ?: 'application/octet-stream',
            );

            if (! empty($upload['error'])) {
                return [
                    'error' => $upload['error'],
                    'message' => $upload['message'] ?? 'Gambar gagal di-upload ke Shopee.',
                ];
            }

            $imageId = data_get($upload, 'response.image_info.image_id')
                ?? data_get($upload, 'response.image_list.0.image_id')
                ?? data_get($upload, 'response.image_id')
                ?? data_get($upload, 'image_id');
            $imageUrl = data_get($upload, 'response.image_info.image_url')
                ?? data_get($upload, 'response.image_list.0.image_url')
                ?? data_get($upload, 'response.image_url')
                ?? data_get($upload, 'image_url');

            if (! $imageId) {
                return ['error' => 'image_id_missing', 'message' => 'Shopee tidak mengembalikan image ID hasil upload.'];
            }

            $uploaded[] = [
                'image_id' => (string) $imageId,
                'image_url' => $imageUrl ? (string) $imageUrl : null,
                'raw_json' => $upload,
            ];
        }

        return ['success' => true, 'images' => $uploaded];
    }

    private function syncProductImages(MarketplaceProduct $product, array $item): void
    {
        $imageIds = array_values(array_filter(array_map(
            static fn ($imageId) => trim((string) $imageId),
            (array) data_get($item, 'image.image_id_list', [])
        ), static fn (string $imageId) => $imageId !== ''));
        $imageUrls = array_values(array_map(
            static fn ($url) => $url !== null ? trim((string) $url) : null,
            (array) data_get($item, 'image.image_url_list', [])
        ));

        if ($imageIds === []) {
            $product->images()->update([
                'status' => 'removed',
                'is_cover' => false,
            ]);
            return;
        }

        foreach ($imageIds as $index => $imageId) {
            MarketplaceProductImage::updateOrCreate(
                [
                    'marketplace_product_id' => $product->id,
                    'shopee_image_id' => $imageId,
                ],
                [
                    'image_url' => $imageUrls[$index] ?? null,
                    'sort_order' => $index,
                    'is_cover' => $index === 0,
                    'status' => 'active',
                    'raw_json' => [
                        'image_id' => $imageId,
                        'image_url' => $imageUrls[$index] ?? null,
                    ],
                ]
            );
        }

        $product->images()
            ->whereNotIn('shopee_image_id', $imageIds)
            ->update([
                'status' => 'removed',
                'is_cover' => false,
            ]);
    }

    private function logProductChange(
        MarketplaceProduct $product,
        string $action,
        string $status,
        ?array $before = null,
        ?array $after = null,
        ?array $response = null,
        ?string $error = null,
    ): void {
        try {
            MarketplaceProductChangeLog::create([
                'marketplace_product_id' => $product->id,
                'action' => $action,
                'status' => $status,
                'before_json' => $before,
                'after_json' => $after,
                'response_json' => $response,
                'error_message' => $error,
                'created_by' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            // Audit tidak boleh menggagalkan update marketplace yang sudah sukses.
            Log::warning('[MarketplaceProductService] failed to write product change log', [
                'product_id' => $product->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

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
        $driver = $this->gateway;
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
        $driver = $this->channelDriver($product->store);
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
        $driver = $this->channelDriver($product->store);

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

    /** Resolve channel driver for product mutations.
     *
     * The nullable fallback keeps direct/unit construction with only the
     * gateway working while the application container injects ChannelManager.
     */
    private function channelDriver(Store $store)
    {
        return ($this->manager ??= app(ChannelManager::class))->driver($store);
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
        $driver = $this->gateway;

        return $driver->updateDiscount($store, $discountId, $discountName, $startTime, $endTime);
    }

    /**
     * Hapus satu item/model dari promo diskon Shoppe.
     */
    public function deleteDiscountItem(Store $store, int $discountId, int $itemId, int $modelId = 0): array
    {
        $driver = $this->gateway;

        return $driver->deleteDiscountItem($store, $discountId, $itemId, $modelId);
    }

}
