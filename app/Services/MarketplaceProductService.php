<?php

namespace App\Services;

use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductChangeLog;
use App\Models\MarketplaceProductImage;
use App\Models\MarketplaceProductModel;
use App\Models\MarketplaceProductPublication;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Marketplace\Ads\AdsExperimentService;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketplaceProductService
{
    public function __construct(
        protected MarketplaceApiGateway $gateway,
        protected ?ChannelManager $manager = null,
    ) {}

    /**
     * Jalankan smoke test aman untuk koneksi TikTok Shop tanpa mengembalikan
     * credential atau raw response ke browser. Setiap panggilan dibatasi ke
     * satu halaman/satu produk agar diagnostik tidak berubah menjadi full sync.
     */
    public function diagnoseTikTokConnection(Store $store): array
    {
        $store->loadMissing('channel:id,code,name');
        if (! $this->isTikTokStore($store)) {
            return [
                'status' => 'failed',
                'message' => 'Toko yang dipilih bukan TikTok Shop.',
                'reconnect_required' => false,
                'steps' => [],
            ];
        }

        $credentials = [
            'app_key' => filled($store->credential('app_key', config('tiktok_shop.app_key'))),
            'app_secret' => filled($store->credential('app_secret', config('tiktok_shop.app_secret'))),
            'access_token' => filled($store->credential('access_token')),
            'refresh_token' => filled($store->credential('refresh_token')),
            'shop_cipher' => filled($store->credential('shop_cipher', $store->meta['shop_cipher'] ?? null)),
        ];
        $requiredCredentialsReady = collect($credentials)
            ->except('refresh_token')
            ->every(static fn (bool $ready): bool => $ready);
        $steps = [[
            'key' => 'credentials',
            'label' => 'Kredensial lokal',
            'status' => $requiredCredentialsReady
                ? ($credentials['refresh_token'] ? 'passed' : 'warning')
                : 'failed',
            'message' => ! $requiredCredentialsReady
                ? 'Kredensial wajib TikTok Shop belum lengkap.'
                : ($credentials['refresh_token']
                    ? 'App, token, dan shop cipher tersedia.'
                    : 'Access token tersedia, tetapi refresh token belum tersimpan.'),
            'duration_ms' => 0,
            'details' => $credentials,
        ]];

        if (! $requiredCredentialsReady) {
            return $this->tiktokDiagnosticResponse(
                $store,
                $steps,
                'failed',
                'Lengkapi atau hubungkan ulang toko TikTok Shop sebelum menguji API.',
                ! $credentials['access_token'] || ! $credentials['refresh_token'],
            );
        }

        $initialTokenHash = hash('sha256', (string) $store->credential('access_token'));

        $shopCall = $this->runTikTokDiagnosticCall(
            fn (): array => $this->gateway->getShopInfo($store),
            'authorized_shop',
            'Authorized Shop',
        );
        $steps[] = $shopCall['step'];
        if ($shopCall['failed']) {
            $this->appendSkippedTikTokSteps($steps, ['Search Products', 'Get Product']);

            return $this->tiktokDiagnosticResponse(
                $store,
                $steps,
                'failed',
                'TikTok Shop menolak pemeriksaan toko.',
                $this->isTikTokAuthenticationFailure($shopCall['result']),
                $initialTokenHash,
            );
        }

        $shops = data_get($shopCall['result'], 'data.shops', []);
        $steps[array_key_last($steps)]['details'] = [
            'authorized_shop_count' => is_array($shops) ? count($shops) : null,
        ];

        $searchCall = $this->runTikTokDiagnosticCall(
            fn (): array => $this->gateway->searchProducts($store, 'ALL', '', 1),
            'search_products',
            'Search Products',
        );
        $steps[] = $searchCall['step'];
        if ($searchCall['failed']) {
            $this->appendSkippedTikTokSteps($steps, ['Get Product']);

            return $this->tiktokDiagnosticResponse(
                $store,
                $steps,
                'failed',
                'Daftar produk TikTok belum dapat diambil.',
                $this->isTikTokAuthenticationFailure($searchCall['result']),
                $initialTokenHash,
            );
        }

        $products = data_get($searchCall['result'], 'data.products', []);
        $products = is_array($products) ? $products : [];
        $sample = $products[0] ?? null;
        $productId = trim((string) data_get($sample, 'id', data_get($sample, 'product_id', '')));
        $steps[array_key_last($steps)]['status'] = $productId !== '' ? 'passed' : 'warning';
        $steps[array_key_last($steps)]['message'] = $productId !== ''
            ? 'Endpoint daftar produk merespons dan mengembalikan sampel produk.'
            : 'Endpoint merespons, tetapi toko belum memiliki produk.';
        $steps[array_key_last($steps)]['details'] = [
            'sample_count' => count($products),
            'has_more' => filled(data_get($searchCall['result'], 'data.next_page_token')),
        ];

        if ($productId === '') {
            $this->appendSkippedTikTokSteps($steps, ['Get Product'], 'Tidak ada product_id untuk diuji.');

            return $this->tiktokDiagnosticResponse(
                $store,
                $steps,
                'warning',
                'Koneksi TikTok sehat, tetapi belum ada produk untuk diuji lebih lanjut.',
                false,
                $initialTokenHash,
            );
        }

        $detailCall = $this->runTikTokDiagnosticCall(
            fn (): array => $this->gateway->getProduct($store, $productId),
            'get_product',
            'Get Product',
        );
        $steps[] = $detailCall['step'];
        if ($detailCall['failed']) {
            return $this->tiktokDiagnosticResponse(
                $store,
                $steps,
                'failed',
                'Detail produk TikTok belum dapat diambil.',
                $this->isTikTokAuthenticationFailure($detailCall['result']),
                $initialTokenHash,
            );
        }

        $detail = (array) data_get($detailCall['result'], 'data', []);
        $steps[array_key_last($steps)]['details'] = [
            'product_id' => $productId,
            'title' => (string) ($detail['title'] ?? data_get($sample, 'title', '')),
            'status' => (string) ($detail['status'] ?? data_get($sample, 'status', '')),
            'sku_count' => count((array) ($detail['skus'] ?? [])),
        ];

        return $this->tiktokDiagnosticResponse(
            $store,
            $steps,
            'success',
            'Semua endpoint utama TikTok Shop merespons dengan baik.',
            false,
            $initialTokenHash,
        );
    }

    private function runTikTokDiagnosticCall(\Closure $call, string $key, string $label): array
    {
        $startedAt = microtime(true);
        try {
            $result = $call();
        } catch (\Throwable $exception) {
            Log::warning('TikTok product diagnostic call failed unexpectedly.', [
                'step' => $key,
                'exception' => $exception::class,
                'code' => $exception->getCode(),
            ]);
            $result = [
                'error' => true,
                'message' => 'Terjadi kesalahan internal saat menghubungi TikTok Shop.',
            ];
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $failed = ! empty($result['error'])
            || (isset($result['code']) && (string) $result['code'] !== '0');

        return [
            'failed' => $failed,
            'result' => $result,
            'step' => [
                'key' => $key,
                'label' => $label,
                'status' => $failed ? 'failed' : 'passed',
                'message' => $failed
                    ? $this->safeTikTokDiagnosticMessage($result['message'] ?? 'TikTok Shop mengembalikan error.')
                    : 'Endpoint merespons dengan sukses.',
                'code' => isset($result['code']) ? (string) $result['code'] : null,
                'request_id' => filled($result['request_id'] ?? null) ? (string) $result['request_id'] : null,
                'duration_ms' => $durationMs,
            ],
        ];
    }

    private function appendSkippedTikTokSteps(array &$steps, array $labels, string $message = 'Dilewati karena tahap sebelumnya gagal.'): void
    {
        foreach ($labels as $label) {
            $steps[] = [
                'key' => Str::snake($label),
                'label' => $label,
                'status' => 'skipped',
                'message' => $message,
                'duration_ms' => 0,
            ];
        }
    }

    private function tiktokDiagnosticResponse(
        Store $store,
        array $steps,
        string $status,
        string $message,
        bool $reconnectRequired,
        ?string $initialTokenHash = null,
    ): array {
        $store->refresh();
        $currentTokenHash = hash('sha256', (string) $store->credential('access_token'));

        return [
            'status' => $status,
            'message' => $message,
            'reconnect_required' => $reconnectRequired,
            'token_refreshed' => $initialTokenHash !== null && ! hash_equals($initialTokenHash, $currentTokenHash),
            'tested_at' => now()->toIso8601String(),
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'code' => $store->code,
            ],
            'steps' => $steps,
        ];
    }

    private function isTikTokAuthenticationFailure(array $result): bool
    {
        $code = strtolower(trim((string) ($result['code'] ?? '')));
        $message = strtolower((string) ($result['message'] ?? ''));

        return in_array($code, ['105', '105001', '105002', 'invalid_access_token'], true)
            || ((str_contains($message, 'access token') || str_contains($message, 'x-tts-access-token'))
                && (str_contains($message, 'invalid') || str_contains($message, 'expired')));
    }

    private function safeTikTokDiagnosticMessage(mixed $message): string
    {
        $message = trim((string) $message);
        $message = preg_replace('/\bTTP_[A-Za-z0-9_-]+\b/', '[TOKEN DISEMBUNYIKAN]', $message) ?? $message;

        return Str::limit($message, 320, '…');
    }

    /**
     * Sync semua produk sebuah toko (status NORMAL + UNLIST).
     * Return: ['synced' => n, 'errors' => []]
     */
    public function syncProducts(Store $store, array $statuses = ['NORMAL', 'UNLIST']): array
    {
        if ($this->isTikTokStore($store)) {
            return $this->syncTikTokProducts($store);
        }

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
     * Sync TikTok Shop's product catalogue into the shared marketplace
     * product tables. TikTok exposes product IDs and SKU snapshots through
     * Search Products, so no Shopee-specific item/model endpoint is used here.
     */
    private function syncTikTokProducts(Store $store): array
    {
        $synced = 0;
        $errors = [];
        $pageToken = '';
        $seenTokens = [];

        do {
            $response = $this->gateway->searchProducts($store, 'ALL', $pageToken, 100);
            if (! empty($response['error'])) {
                $errors[] = 'search_products: ' . ($response['message'] ?? $response['error']);
                break;
            }

            $summaries = data_get($response, 'response.products')
                ?? data_get($response, 'data.products')
                ?? [];
            if (! is_array($summaries)) {
                $summaries = [];
            }

            foreach ($summaries as $summary) {
                $productId = trim((string) ($summary['id'] ?? $summary['product_id'] ?? ''));
                if ($productId === '') {
                    continue;
                }

                $detailResponse = $this->gateway->getProduct($store, $productId);
                $detail = data_get($detailResponse, 'data');
                if (! is_array($detail) || empty($detail['id'])) {
                    // The search snapshot is still useful when one detail call
                    // fails; retain the sync error and continue other products.
                    if (! empty($detailResponse['error'])) {
                        $errors[] = "get_product {$productId}: " . ($detailResponse['message'] ?? $detailResponse['error']);
                    }
                    $detail = $summary;
                }

                $this->upsertTikTokProduct($store, $detail);
                $synced++;
            }

            $nextToken = (string) (
                data_get($response, 'response.next_page_token')
                ?? data_get($response, 'data.next_page_token')
                ?? ''
            );
            if ($nextToken === '' || isset($seenTokens[$nextToken])) {
                break;
            }
            $seenTokens[$nextToken] = true;
            $pageToken = $nextToken;
        } while ($pageToken !== '');

        return ['synced' => $synced, 'errors' => $errors];
    }

    /**
     * Convert TikTok's product/SKU shape to the common local catalogue shape.
     */
    private function upsertTikTokProduct(Store $store, array $item): MarketplaceProduct
    {
        $itemId = trim((string) ($item['id'] ?? $item['product_id'] ?? ''));
        $status = strtoupper((string) ($item['status'] ?? 'UNKNOWN'));
        $localStatus = $status === 'ACTIVATE' ? 'NORMAL' : 'UNLIST';
        $skus = array_values(array_filter((array) ($item['skus'] ?? []), 'is_array'));
        $images = (array) ($item['main_images'] ?? []);
        $firstImage = data_get($images, '0');
        $imageUrl = data_get($firstImage, 'urls.0')
            ?? data_get($firstImage, 'url')
            ?? data_get($firstImage, 'uri');
        $categories = (array) ($item['categories'] ?? []);
        $categoryId = data_get($categories, '0.id')
            ?? data_get($item, 'category_id')
            ?? data_get($item, 'recommended_categories.0.id');

        $product = MarketplaceProduct::updateOrCreate(
            ['store_id' => $store->id, 'item_id' => $itemId],
            [
                'item_name' => $item['title'] ?? $item['product_name'] ?? null,
                'item_sku' => count($skus) === 1 ? ($skus[0]['seller_sku'] ?? null) : null,
                'item_status' => $localStatus,
                'category_id' => $categoryId !== null ? (string) $categoryId : null,
                'image_url' => $imageUrl,
                'has_model' => count($skus) > 1 || collect($skus)->contains(fn (array $sku): bool => ! empty($sku['sales_attributes'])),
                'raw_json' => $item,
                'synced_at' => now(),
            ]
        );

        $keptModelIds = [];
        foreach ($skus as $sku) {
            $modelId = trim((string) ($sku['id'] ?? $sku['sku_id'] ?? ''));
            if ($modelId === '') {
                continue;
            }

            $price = data_get($sku, 'price.sale_price')
                ?? data_get($sku, 'price.tax_exclusive_price')
                ?? data_get($sku, 'price.amount');
            $stock = collect((array) ($sku['inventory'] ?? []))
                ->sum(fn ($inventory): int => (int) ($inventory['quantity'] ?? 0));
            $attributes = collect((array) ($sku['sales_attributes'] ?? []))
                ->map(fn ($attribute): string => (string) ($attribute['value_name'] ?? $attribute['name'] ?? ''))
                ->filter()
                ->implode(' / ');

            $model = MarketplaceProductModel::updateOrCreate(
                ['marketplace_product_id' => $product->id, 'model_id' => $modelId],
                [
                    'model_name' => $attributes !== '' ? $attributes : null,
                    'model_sku' => $sku['seller_sku'] ?? null,
                    'price' => $price !== null ? (float) $price : null,
                    'stock' => $stock,
                    'raw_json' => $sku,
                ]
            );
            $keptModelIds[] = $model->id;
        }

        // A product with no SKU is unusual, but keeping a model row preserves
        // the same UI contract as the Shopee adapter.
        if ($keptModelIds === []) {
            $model = MarketplaceProductModel::updateOrCreate(
                ['marketplace_product_id' => $product->id, 'model_id' => '0'],
                [
                    'model_name' => null,
                    'model_sku' => null,
                    'price' => null,
                    'stock' => 0,
                    'raw_json' => [],
                ]
            );
            $keptModelIds[] = $model->id;
        }

        $product->models()->whereNotIn('id', $keptModelIds)->delete();
        $prices = $product->models()->pluck('price')->filter();
        $product->update([
            'price_min' => $prices->min(),
            'price_max' => $prices->max(),
            'stock_total' => (int) $product->models()->sum('stock'),
        ]);

        return $product->fresh();
    }

    private function isTikTokStore(Store $store): bool
    {
        return in_array(strtolower((string) $store->channel?->code), ['tiktok', 'ttk', 'tt'], true);
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

        if (in_array($channel, ['tiktok', 'ttk', 'tt'], true)) {
            return $this->getTikTokEditData($product);
        }

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
     * TikTok detail snapshot for the shared editor. TikTok supports SKU price
     * and inventory mutations through this module; listing content, SKU names,
     * and gallery remain read-only until their dedicated API flow is added.
     */
    private function getTikTokEditData(MarketplaceProduct $product): array
    {
        $store = $product->store;
        $response = $this->gateway->getProduct($store, (string) $product->item_id);
        $item = data_get($response, 'data');
        $staleSnapshot = false;
        $remoteMessage = null;

        // Detail TikTok kadang gagal sementara (502/429 dari upstream). Produk
        // sudah menyimpan raw snapshot saat sync, sehingga editor tetap bisa
        // dibuka dan harga/stok lokal tidak ikut hilang.
        if (! is_array($item) || empty($item['id'])) {
            $remoteMessage = $response['message'] ?? null;
            $item = is_array($product->raw_json) ? $product->raw_json : [];
            if (! empty($item)) {
                $item['id'] = $item['id'] ?? (string) $product->item_id;
                $staleSnapshot = true;
            }
        }

        if (! is_array($item) || empty($item['id'])) {
            return [
                'error' => 'item_not_found',
                'message' => 'Produk tidak ditemukan di TikTok Shop. Coba lakukan sync terlebih dahulu.',
            ];
        }

        $freshProduct = $staleSnapshot
            ? $product->fresh('models')
            : $this->upsertTikTokProduct($store, $item);
        $models = $freshProduct->fresh('models')->models->map(function (MarketplaceProductModel $model): array {
            $raw = is_array($model->raw_json) ? $model->raw_json : [];
            $price = data_get($raw, 'price.sale_price')
                ?? data_get($raw, 'price.tax_exclusive_price')
                ?? data_get($raw, 'price.amount')
                ?? $model->price;

            return [
                'model_id' => (string) $model->model_id,
                'model_name' => $model->model_name,
                'model_sku' => $model->model_sku,
                'original_price' => $price,
                'current_price' => $price,
                'stock' => (int) $model->stock,
                'tier_index' => [],
            ];
        })->values()->all();

        $images = collect((array) ($item['main_images'] ?? []))
            ->map(function (array $image, int $index): array {
                return [
                    'image_id' => data_get($image, 'uri') ?? data_get($image, 'urls.0'),
                    'image_url' => data_get($image, 'urls.0') ?? data_get($image, 'url') ?? data_get($image, 'uri'),
                    'sort_order' => $index,
                    'is_cover' => $index === 0,
                ];
            })->values()->all();

        return [
            'product' => [
                'local_id' => $freshProduct->id,
                'store_id' => $store->id,
                'store_name' => $store->name,
                'item_id' => (string) ($item['id'] ?? $freshProduct->item_id),
                'item_name' => $item['title'] ?? $freshProduct->item_name,
                'description' => $item['description'] ?? data_get($freshProduct->raw_json, 'description'),
                'item_sku' => $freshProduct->item_sku,
                'item_status' => $freshProduct->item_status,
                'category_id' => data_get($item, 'categories.0.id') ?? $freshProduct->category_id,
                'has_model' => $freshProduct->has_model,
            ],
            'images' => $images,
            'tiers' => [],
            'models' => $models,
            'source' => [
                'channel' => 'tiktok',
                'read_only_content' => true,
                'stale_snapshot' => $staleSnapshot,
                'warning' => $staleSnapshot
                    ? ($remoteMessage ?: 'Detail live TikTok sedang tidak tersedia; data snapshot terakhir ditampilkan.')
                    : null,
                'fetched_at' => now()->toIso8601String(),
                'product_endpoint' => '/product/202309/products/{product_id}',
            ],
        ];
    }

    /**
     * Update stok di Shopee + lokal.
     * $stockList: [['model_id' => '0', 'stock' => 10], ...]
     */
    public function updateStock(MarketplaceProduct $product, array $stockList): array
    {
        if ($product->has_model) {
            $rawModelIds = collect($stockList)->pluck('model_id');
            if ($rawModelIds->contains(fn ($modelId): bool => ! is_string($modelId) && ! is_int($modelId))) {
                return [
                    'error' => true,
                    'message' => 'ID variant stok tidak valid.',
                ];
            }

            $modelIds = $rawModelIds
                ->map(fn ($modelId): string => (string) $modelId)
                ->values();
            $validModelCount = $product->models()
                ->whereIn('model_id', $modelIds->unique()->all())
                ->count();

            if ($modelIds->duplicates()->isNotEmpty() || $validModelCount !== $modelIds->count()) {
                return [
                    'error' => true,
                    'message' => 'Variant stok tidak ditemukan pada produk marketplace ini.',
                ];
            }
        }

        $driver = $this->channelDriver($product->store);
        if ($this->isTikTokStore($product->store)) {
            $tiktokStockList = [];
            foreach ($stockList as $stock) {
                $model = $product->models()
                    ->where('model_id', (string) ($stock['model_id'] ?? '0'))
                    ->first();
                $raw = is_array($model?->raw_json) ? $model->raw_json : [];
                $warehouseId = data_get($raw, 'inventory.0.warehouse_id')
                    ?? data_get($product->raw_json, 'skus.0.inventory.0.warehouse_id');
                $tiktokStockList[] = [
                    'model_id' => $stock['model_id'] ?? '0',
                    'stock' => $stock['stock'],
                    'warehouse_id' => $warehouseId,
                ];
            }
            $res = $driver->updateProductInventory($product->store, (string) $product->item_id, $tiktokStockList);
        } else {
            $res = $driver->updateProductStock($product->store, $product->item_id, $stockList);
        }

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

        if ($this->isTikTokStore($product->store)) {
            $tiktokPriceList = [];
            foreach ($priceList as $price) {
                $model = $oldModels->get((string) ($price['model_id'] ?? '0'));
                $raw = is_array($model?->raw_json) ? $model->raw_json : [];
                $tiktokPriceList[] = [
                    'model_id' => $price['model_id'],
                    'original_price' => $price['original_price'],
                    'currency' => data_get($raw, 'price.currency', 'IDR'),
                ];
            }
            $res = $driver->updateProductPrices($product->store, (string) $product->item_id, $tiktokPriceList);
        } else {
            $originalPriceList = array_map(function ($p) {
                return [
                    'model_id' => $p['model_id'],
                    'original_price' => $p['original_price'],
                ];
            }, $priceList);
            $res = $driver->updateProductPrice($product->store, $product->item_id, $originalPriceList);
        }

        if (! empty($res['error'])) {
            return $res;
        }

        foreach ($priceList as $p) {
            $model = MarketplaceProductModel::where('marketplace_product_id', $product->id)
                ->where('model_id', (string) ($p['model_id'] ?? '0'))
                ->first();
                
            if ($model) {
                $raw = is_string($model->raw_json) ? json_decode($model->raw_json, true) : ($model->raw_json ?? []);
                if ($this->isTikTokStore($product->store)) {
                    $raw['price']['amount'] = (string) ((int) round((float) $p['original_price']));
                } else {
                    if (!isset($raw['price_info'])) $raw['price_info'] = [[]];
                    $raw['price_info'][0]['original_price'] = (float) $p['original_price'];
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
        if ($this->isTikTokStore($store)) {
            $result = $this->channelDriver($store)->getProductCategories($store);
            if (! empty($result['error'])) {
                return $result;
            }

            $categories = array_values(array_filter(
                (array) data_get($result, 'data.categories', []),
                static function ($category): bool {
                    if (! is_array($category)) {
                        return false;
                    }
                    $isLeaf = in_array($category['is_leaf'] ?? false, [true, 1, '1', 'true', 'TRUE'], true);
                    $permissions = array_map('strtoupper', (array) ($category['permission_statuses'] ?? []));

                    return $isLeaf && ($permissions === [] || in_array('AVAILABLE', $permissions, true));
                }
            ));
            $byId = collect((array) data_get($result, 'data.categories', []))
                ->filter(fn ($category): bool => is_array($category) && filled($category['id'] ?? null))
                ->keyBy(fn (array $category): string => (string) $category['id']);

            $normalized = collect($categories)->map(function (array $category) use ($byId): array {
                $path = [];
                $current = $category;
                $visited = [];
                while (filled($current['id'] ?? null) && ! isset($visited[(string) $current['id']])) {
                    $visited[(string) $current['id']] = true;
                    array_unshift($path, (string) ($current['local_name'] ?? $current['name'] ?? 'Kategori'));
                    $parentId = (string) ($current['parent_id'] ?? '');
                    $current = $parentId !== '' ? (array) $byId->get($parentId, []) : [];
                }

                return [
                    'category_id' => (string) $category['id'],
                    'category_name' => implode(' › ', $path),
                    'is_leaf' => true,
                ];
            })->sortBy('category_name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

            return ['response' => ['category_list' => $normalized]];
        }

        return $this->channelDriver($store)->getProductCategory($store, 'id');
    }

    /**
     * Ambil metadata kategori produk existing dari Shopee.
     * get_item_base_info hanya mengembalikan category_id, sehingga nama dan
     * breadcrumb perlu dicocokkan dengan tree get_category.
     */
    public function getCategoryInfo(MarketplaceProduct $product, ?int $categoryId = null): array
    {
        $categoryId = (int) ($categoryId ?: ($product->category_id ?? 0));
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

    public function getCreateAttributes(Store $store, int|string $categoryId): array
    {
        if ($this->isTikTokStore($store)) {
            $result = $this->channelDriver($store)->getProductAttributes($store, (string) $categoryId);
            if (! empty($result['error'])) {
                return $result;
            }

            $attributes = collect((array) data_get($result, 'data.attributes', []))
                ->filter(fn ($attribute): bool => is_array($attribute))
                ->map(function (array $attribute): array {
                    $values = collect((array) ($attribute['values'] ?? []))
                        ->filter(fn ($value): bool => is_array($value))
                        ->map(fn (array $value): array => [
                            'value_id' => (string) ($value['id'] ?? ''),
                            'original_value_name' => (string) ($value['name'] ?? ''),
                        ])->values()->all();

                    return [
                        'attribute_id' => (string) ($attribute['id'] ?? ''),
                        'attribute_name' => (string) ($attribute['name'] ?? 'Atribut'),
                        'is_mandatory' => in_array(
                            $attribute['is_requried'] ?? $attribute['is_required'] ?? false,
                            [true, 1, '1', 'true', 'TRUE'],
                            true
                        ),
                        'attribute_type' => (string) ($attribute['type'] ?? 'PRODUCT_PROPERTY'),
                        'attribute_value_list' => $values,
                    ];
                })->filter(fn (array $attribute): bool => $attribute['attribute_id'] !== '')
                ->values()->all();

            return ['response' => ['attribute_list' => $attributes]];
        }

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
        if ($this->isTikTokStore($store)) {
            $result = $this->channelDriver($store)->getProductWarehouses($store);
            if (! empty($result['error'])) {
                return $result;
            }

            $warehouses = collect((array) data_get($result, 'data.warehouses', []))
                ->filter(fn ($warehouse): bool => is_array($warehouse)
                    && ($warehouse['type'] ?? 'SALES_WAREHOUSE') === 'SALES_WAREHOUSE'
                    && ! in_array(
                        strtoupper((string) ($warehouse['effect_status'] ?? 'EFFECTIVE')),
                        ['INEFFECTIVE', 'INACTIVE', 'DISABLED'],
                        true
                    ))
                ->map(fn (array $warehouse): array => [
                    'logistics_channel_id' => (string) ($warehouse['id'] ?? ''),
                    'logistics_channel_name' => (string) ($warehouse['name'] ?? 'Gudang TikTok'),
                    'enabled' => in_array($warehouse['is_default'] ?? false, [true, 1, '1', 'true', 'TRUE'], true),
                    'is_default' => in_array($warehouse['is_default'] ?? false, [true, 1, '1', 'true', 'TRUE'], true),
                ])->filter(fn (array $warehouse): bool => $warehouse['logistics_channel_id'] !== '')
                ->values()->all();

            return ['response' => ['logistics_channel_list' => $warehouses]];
        }

        return $this->channelDriver($store)->getProductLogistics($store);
    }

    /**
     * Publish item baru lalu simpan item_id dan snapshot lokal bila detail dapat
     * langsung dibaca ulang dari Shopee.
     */
    public function createProduct(Store $store, array $payload): array
    {
        if ($this->isTikTokStore($store)) {
            return $this->createTikTokProduct($store, $payload);
        }

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

    private function createTikTokProduct(Store $store, array $payload): array
    {
        if (data_get($store->meta, 'api_access_mode') === 'read_only') {
            return [
                'error' => 'read_only_connection',
                'message' => 'Koneksi TikTok toko ini hanya memiliki akses baca. Hubungkan ulang dengan scope Product write.',
            ];
        }

        $driver = $this->channelDriver($store);
        $idempotencyKey = (string) ($payload['idempotency_key'] ?? Str::uuid());
        $payload['idempotency_key'] = $idempotencyKey;
        $requestHash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $publication = MarketplaceProductPublication::firstOrCreate(
            ['store_id' => $store->id, 'idempotency_key' => $idempotencyKey],
            [
                'channel_code' => 'tiktok',
                'status' => 'pending',
                'request_hash' => $requestHash,
                'payload_json' => $payload,
                'created_by' => auth()->id(),
            ]
        );

        if (! $publication->wasRecentlyCreated && $publication->request_hash !== $requestHash) {
            return [
                'error' => 'idempotency_conflict',
                'message' => 'Idempotency key sudah dipakai untuk payload produk yang berbeda.',
            ];
        }
        if ($publication->status === 'success' && filled($publication->platform_product_id)) {
            $product = MarketplaceProduct::where('store_id', $store->id)
                ->where('item_id', $publication->platform_product_id)
                ->first();

            return [
                'success' => true,
                'item_id' => $publication->platform_product_id,
                'product_id' => $product?->id,
                'idempotent_replay' => true,
            ];
        }

        $publication->update([
            'status' => 'checking',
            'payload_json' => $payload,
            'response_json' => null,
            'error_message' => null,
            'completed_at' => null,
        ]);

        $check = $driver->checkProductListing($store, $payload);
        $checkResult = strtoupper((string) data_get($check, 'data.check_result', ''));
        if (! empty($check['error']) || in_array($checkResult, ['FAIL', 'FAILED'], true)) {
            $reasons = collect((array) data_get($check, 'data.fail_reasons', []))
                ->map(function ($reason): string {
                    $message = data_get($reason, 'message') ?? data_get($reason, 'reason') ?? $reason;

                    return is_scalar($message) ? (string) $message : (json_encode($message) ?: 'Validasi TikTok gagal');
                })
                ->filter()->implode('; ');
            $message = $reasons ?: ($check['message'] ?? 'Produk tidak lolos pemeriksaan listing TikTok.');
            $publication->update([
                'status' => 'validation_failed',
                'response_json' => ['listing_check' => $check],
                'error_message' => $message,
                'platform_request_id' => $check['request_id'] ?? null,
                'completed_at' => now(),
            ]);

            return ['error' => 'listing_check_failed', 'message' => $message, 'response' => $check];
        }

        $publication->update(['status' => 'publishing']);
        $res = $driver->addProduct($store, $payload);
        if (! empty($res['error'])) {
            $publication->update([
                'status' => 'failed',
                'response_json' => ['listing_check' => $check, 'publish' => $res],
                'error_message' => $res['message'] ?? (string) $res['error'],
                'platform_request_id' => $res['request_id'] ?? null,
                'completed_at' => now(),
            ]);

            return $res;
        }

        $itemId = data_get($res, 'data.product_id') ?? data_get($res, 'data.id');
        if (! $itemId) {
            $publication->update([
                'status' => 'failed',
                'response_json' => ['listing_check' => $check, 'publish' => $res],
                'error_message' => 'TikTok menerima request tetapi tidak mengembalikan product_id.',
                'platform_request_id' => $res['request_id'] ?? null,
                'completed_at' => now(),
            ]);

            return [
                'error' => 'product_id_missing',
                'message' => 'TikTok menerima request tetapi tidak mengembalikan product_id.',
                'response' => $res,
            ];
        }

        $detail = $driver->getProduct($store, (string) $itemId);
        $item = data_get($detail, 'data');
        $warning = null;
        if (! is_array($item) || empty($item['id'])) {
            $warning = 'Produk berhasil dibuat di TikTok, tetapi detailnya belum dapat dibaca ulang. Jalankan sinkron produk untuk melengkapinya.';
            $createdSkus = (array) data_get($res, 'data.skus', []);
            $item = [
                'id' => (string) $itemId,
                'title' => $payload['title'] ?? null,
                'status' => ($payload['save_mode'] ?? 'AS_DRAFT') === 'LISTING' ? 'ACTIVATE' : 'DRAFT',
                'category_id' => $payload['category_id'] ?? null,
                'main_images' => $payload['main_images'] ?? [],
                'skus' => collect((array) ($payload['skus'] ?? []))->values()->map(function (array $sku, int $index) use ($createdSkus): array {
                    $sku['id'] = data_get($createdSkus, $index.'.id')
                        ?? data_get($createdSkus, $index.'.sku_id')
                        ?? 'pending-'.($index + 1);

                    return $sku;
                })->all(),
            ];
        }

        $product = $this->upsertTikTokProduct($store, $item);
        $this->logProductChange(
            product: $product,
            action: 'create',
            status: $warning ? 'warning' : 'success',
            after: ['item_id' => (string) $itemId, 'payload' => $payload],
            response: ['listing_check' => $check, 'publish' => $res, 'detail' => $detail],
            error: $warning,
        );
        $publication->update([
            'status' => 'success',
            'response_json' => ['listing_check' => $check, 'publish' => $res, 'detail' => $detail],
            'error_message' => $warning,
            'platform_product_id' => (string) $itemId,
            'platform_request_id' => $res['request_id'] ?? null,
            'completed_at' => now(),
        ]);

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
                ?? data_get($upload, 'image_id')
                ?? data_get($upload, 'data.uri');
            $imageUrl = data_get($upload, 'response.image_info.image_url')
                ?? data_get($upload, 'response.image_list.0.image_url')
                ?? data_get($upload, 'response.image_url')
                ?? data_get($upload, 'image_url')
                ?? data_get($upload, 'data.url');

            if (! $imageId) {
                return ['error' => 'image_id_missing', 'message' => 'Marketplace tidak mengembalikan ID/URI hasil upload gambar.'];
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
