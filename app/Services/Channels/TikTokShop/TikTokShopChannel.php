<?php

namespace App\Services\Channels\TikTokShop;

use App\Models\Store;
use App\Models\MarketplaceOrder;
use App\Services\Channels\Contracts\MarketplaceChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokShopChannel implements MarketplaceChannel
{
    private const SEA_REGIONS = ['ID', 'SG', 'PH', 'VN', 'TH', 'MY'];

    // ─── Credential helpers ───────────────────────────────────────────────────

    protected function baseUrl(Store $store): string
    {
        return rtrim(config('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com'), '/');
    }

    protected function appKey(Store $store): string
    {
        return trim((string) $store->credential('app_key', config('tiktok_shop.app_key')));
    }

    protected function appSecret(Store $store): string
    {
        return trim((string) $store->credential('app_secret', config('tiktok_shop.app_secret')));
    }

    protected function shopId(Store $store): string
    {
        return trim((string) $store->credential('shop_id', $store->external_shop_id));
    }

    protected function shopCipher(Store $store): string
    {
        return trim((string) $store->credential('shop_cipher', $store->meta['shop_cipher'] ?? ''));
    }

    protected function accessToken(Store $store): string
    {
        return trim((string) $store->credential('access_token'));
    }

    // ─── Signature (TikTok Shop v202309+) ───────────────────────────────────
    // Format: HMAC-SHA256(
    //   appSecret + path + sorted(query key/value pairs) + body + appSecret,
    //   key=appSecret
    // )

    protected function sign(Store $store, string $path, array $params, string $body = ''): string
    {
        $appSecret = $this->appSecret($store);

        unset($params['sign'], $params['access_token']);

        ksort($params);
        $paramStr = '';
        foreach ($params as $k => $v) {
            $paramStr .= $k . $v;
        }

        $toSign = $appSecret . $path . $paramStr . $body . $appSecret;

        return hash_hmac('sha256', $toSign, $appSecret);
    }

    // ─── HTTP helpers ─────────────────────────────────────────────────────────

    protected function get(Store $store, string $path, array $params = []): array
    {
        $result = $this->doRequest($store, 'GET', $path, $params);

        // Token expired → auto-refresh sekali lalu retry
        $code = $result['code'] ?? null;
        if ($code === 105 || $code === 'invalid_access_token') {
            $refreshed = $this->refreshToken($store);
            if (empty($refreshed['error'])) {
                $store->refresh();
                $result = $this->doRequest($store, 'GET', $path, $params);
            }
        }

        return $result;
    }

    protected function post(Store $store, string $path, array $body = [], array $params = []): array
    {
        $result = $this->doRequest($store, 'POST', $path, $params, $body);

        $code = $result['code'] ?? null;
        if ($code === 105 || $code === 'invalid_access_token') {
            $refreshed = $this->refreshToken($store);
            if (empty($refreshed['error'])) {
                $store->refresh();
                $result = $this->doRequest($store, 'POST', $path, $params, $body);
            }
        }

        return $result;
    }

    protected function doRequest(
        Store $store,
        string $method,
        string $path,
        array $params = [],
        array $body = []
    ): array
    {
        $timestamp = (string) time();

        $query = array_merge($params, [
            'app_key'   => $this->appKey($store),
            'timestamp' => $timestamp,
        ]);

        $shopCipher = $this->shopCipher($store);
        if ($shopCipher !== '') {
            $query['shop_cipher'] = $shopCipher;
        }

        $bodyJson = $method === 'POST'
            ? json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : '';
        if ($bodyJson === false) {
            return ['error' => true, 'code' => -1, 'message' => 'Gagal membuat JSON request TikTok Shop.'];
        }

        $query['sign'] = $this->sign($store, $path, $query, $bodyJson);

        // Cetak dokumen berjalan synchronously dari request browser. Endpoint
        // shipping_documents sebelumnya memakai 30s x 3 percobaan, lalu
        // controller masih mengunduh PDF; totalnya dapat melewati timeout
        // Nginx dan tampil sebagai 502 Bad Gateway. Batasi khusus endpoint
        // dokumen agar aplikasi sempat mengembalikan error yang jelas atau PDF.
        $isShippingDocumentRequest = str_ends_with($path, '/shipping_documents');
        $connectTimeout = $isShippingDocumentRequest ? 5 : 10;
        $requestTimeout = $isShippingDocumentRequest ? 12 : 30;
        $retryTimes = $isShippingDocumentRequest ? 1 : 2;

        $request = Http::connectTimeout($connectTimeout)
            ->timeout($requestTimeout)
            ->retry($retryTimes, function (int $attempt, \Throwable $exception) {
                // Hanya retry pada error transien (5xx, connection timeout)
                if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                    return $attempt * 1000;
                }
                if ($exception instanceof \Illuminate\Http\Client\RequestException
                    && $exception->response->status() >= 500) {
                    return $attempt * 1000;
                }
                return false; // Jangan retry 4xx (client error)
            }, throw: false)
            ->withHeaders([
                'x-tts-access-token' => $this->accessToken($store),
                'content-type'       => 'application/json',
            ]);

        $url = $this->baseUrl($store) . $path;

        // TikTok expects app_key, timestamp, sign, shop_cipher, and endpoint
        // parameters in the query string even when the request body is JSON.
        // Passing $query as the second argument to post() would treat it as
        // the request body. Set the raw JSON body explicitly in the Guzzle
        // options so TikTok receives application/json (not form parameters).
        $response = $method === 'POST'
            ? $request
                ->withOptions(['body' => $bodyJson])
                ->send('POST', $url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986))
            : $request->get($url, $query);

        $json = $response->json();
        if ($json === null) {
            return [
                'error'   => true,
                'code'    => -1,
                'message' => $response->body(),
                'status'  => $response->status(),
            ];
        }

        // TikTok Shop API v202309 usually returns code != 0 for errors
        if (isset($json['code']) && (int) $json['code'] !== 0) {
            $json['error'] = true;
        }

        return $json;
    }

    // ─── MarketplaceChannel interface ─────────────────────────────────────────

    public function getShopInfo(Store $store): array
    {
        return $this->get($store, '/seller/202309/shops');
    }

    // ─── Products (TikTok Shop Product API v202309) ─────────────────────────

    /**
     * Search products with opaque-token pagination.
     *
     * TikTok returns a compact product/SKU snapshot here. The product service
     * uses it for the catalogue sync and can request getProduct() when it needs
     * the full listing detail.
     */
    public function searchProducts(
        Store $store,
        string $status = 'ALL',
        string $pageToken = '',
        int $pageSize = 100
    ): array {
        $params = [
            'page_size' => min(100, max(1, $pageSize)),
        ];
        if ($pageToken !== '') {
            $params['page_token'] = $pageToken;
        }

        $response = $this->post($store, '/product/202309/products/search', [
            'status' => $status ?: 'ALL',
            'locale' => 'id-ID',
        ], $params);

        $products = data_get($response, 'data.products');
        if (is_array($products)) {
            $response['response']['products'] = $products;
            $response['response']['next_page_token'] = data_get($response, 'data.next_page_token');
            $response['response']['more'] = filled(data_get($response, 'data.next_page_token'));
        }

        return $response;
    }

    /**
     * Retrieve a complete product listing, including images and SKU inventory.
     */
    public function getProduct(Store $store, string $productId): array
    {
        return $this->get($store, '/product/202309/products/' . rawurlencode($productId));
    }

    /**
     * Update SKU inventory. Each row must contain model_id, stock and the
     * warehouse_id captured from the latest product snapshot.
     */
    public function updateProductInventory(Store $store, string $productId, array $stockList): array
    {
        $skus = [];
        foreach ($stockList as $stock) {
            $skuId = trim((string) ($stock['model_id'] ?? ''));
            $warehouseId = trim((string) ($stock['warehouse_id'] ?? ''));
            if ($skuId === '' || $warehouseId === '') {
                continue;
            }

            $skus[] = [
                'id' => $skuId,
                'inventory' => [[
                    'warehouse_id' => $warehouseId,
                    'quantity' => (int) ($stock['stock'] ?? 0),
                ]],
            ];
        }

        if ($skus === []) {
            return [
                'error' => 'warehouse_missing',
                'message' => 'Warehouse TikTok tidak ditemukan pada snapshot SKU. Sinkronkan produk terlebih dahulu.',
            ];
        }

        return $this->post($store, '/product/202309/products/' . rawurlencode($productId) . '/inventory/update', [
            'skus' => $skus,
        ]);
    }

    /**
     * Update SKU prices. TikTok prices are SKU-level and use an amount object.
     */
    public function updateProductPrices(Store $store, string $productId, array $priceList): array
    {
        $skus = [];
        foreach ($priceList as $price) {
            $skuId = trim((string) ($price['model_id'] ?? ''));
            if ($skuId === '') {
                continue;
            }

            $currency = strtoupper(trim((string) ($price['currency'] ?? 'IDR')));
            $skus[] = [
                'id' => $skuId,
                'price' => [
                    'amount' => (string) ((int) round((float) ($price['original_price'] ?? 0))),
                    'currency' => $currency,
                ],
            ];
        }

        if ($skus === []) {
            return ['error' => 'sku_missing', 'message' => 'SKU TikTok tidak ditemukan.'];
        }

        return $this->post($store, '/product/202309/products/' . rawurlencode($productId) . '/prices/update', [
            'skus' => $skus,
        ]);
    }

    // ─── Logistics / Fulfillment ──────────────────────────────────────────────

    public function getShippingParameter(Store $store, string $orderSn): array
    {
        $context = $this->logisticsContext($store, $orderSn);
        $orderId = $context['order_id'];
        $isSellerShipping = $this->isSellerShipping($context);
        $responseMeta = [
            'platform' => 'tiktok',
            'order_id' => $orderId,
            'package_id' => $context['package_id'],
            'shipping_type' => $context['shipping_type'],
            'is_seller_shipping' => $isSellerShipping,
            'is_tiktok_shipping' => ! $isSellerShipping,
            'supports_handover_method' => ! $this->isSeaStore($store),
            'delivery_option' => $context['delivery_option'],
        ];

        // Seller Shipping does not use TikTok's eligible shipping-service
        // query. The seller provides carrier/tracking data in ship().
        if ($isSellerShipping) {
            return [
                'code' => 0,
                'message' => 'Success',
                'response' => array_merge($responseMeta, [
                    'shipping_services' => [],
                    'weight' => $context['weight'],
                    'dimension' => $context['dimension'],
                    'order_line_item_ids' => $context['order_line_item_ids'],
                    'order_line_list' => $context['order_line_list'],
                ]),
            ];
        }

        // An existing package already has its shipping service allocated. In
        // that case the UI only needs to confirm handover; querying eligible
        // services again can return a misleading "service not found" error.
        if (filled($context['package_id'])) {
            return [
                'code' => 0,
                'message' => 'Success',
                'response' => array_merge($responseMeta, [
                    'shipping_services' => [],
                    'weight' => $context['weight'],
                    'dimension' => $context['dimension'],
                    'order_line_item_ids' => $context['order_line_item_ids'],
                    'order_line_list' => $context['order_line_list'],
                ]),
            ];
        }

        $body = [
            'order_line_item_ids' => $context['order_line_item_ids'],
            'weight' => $context['weight'],
            'dimension' => $context['dimension'],
        ];

        $body = array_filter($body, static fn ($value): bool => $value !== null && $value !== []);
        $response = $this->post(
            $store,
            '/fulfillment/202309/orders/' . rawurlencode($orderId) . '/shipping_services/query',
            $body
        );

        if (($response['code'] ?? null) !== 0) {
            return $response;
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $response['response'] = array_merge($responseMeta, [
            'shipping_services' => $data['shipping_services'] ?? [],
            'weight' => $body['weight'] ?? null,
            'dimension' => $body['dimension'] ?? null,
            'order_line_item_ids' => $context['order_line_item_ids'],
            'order_line_list' => $context['order_line_list'],
        ]);

        return $response;
    }

    public function shipOrder(Store $store, string $orderSn, array $params = []): array
    {
        $context = $this->logisticsContext($store, $orderSn);
        $orderId = $context['order_id'];
        $packageId = trim((string) ($params['package_id'] ?? $context['package_id'] ?? ''));
        $isSellerShipping = $this->isSellerShipping($context, $params);

        if ($isSellerShipping && $packageId === '') {
            return [
                'error' => 'seller_shipping_package_id_missing',
                'message' => 'Package ID TikTok belum tersedia untuk Seller Shipping. Sinkronkan detail pesanan terlebih dahulu.',
            ];
        }

        if (! $isSellerShipping && $packageId === '') {
            $shipType = (string) ($params['ship_type'] ?? '1');
            $createPayload = [
                'ship_type' => $shipType,
                'order_id' => $orderId,
                'order_line_item' => $params['order_line_item'] ?? $context['order_line_list'],
                'dimension' => $params['dimension'] ?? $context['dimension'],
                'shipping_service_id' => $params['shipping_service_id'] ?? null,
                'weight' => $params['weight'] ?? $context['weight'],
            ];

            // TikTok only consumes order_line_item for split shipments
            // (ship_type=2). Omitting it for a normal single-package order
            // keeps the request aligned with the documented contract.
            if ($shipType !== '2' || $createPayload['order_line_item'] === []) {
                unset($createPayload['order_line_item']);
            }
            $createPayload = array_filter($createPayload, static fn ($value): bool => $value !== null && $value !== []);

            $created = $this->post($store, '/fulfillment/202309/packages', $createPayload);
            if (($created['code'] ?? null) !== 0) {
                return $created;
            }

            $packageId = trim((string) data_get($created, 'data.package_id'));
            if ($packageId === '') {
                return [
                    'error' => 'package_id_missing',
                    'message' => 'TikTok tidak mengembalikan package_id setelah membuat paket.',
                    'response' => $created,
                ];
            }
        }

        $shipPayload = $this->buildShipPayload($store, $params, $context);
        $selfShipment = $shipPayload['self_shipment'] ?? [];
        if ($isSellerShipping
            && (trim((string) ($selfShipment['tracking_number'] ?? '')) === ''
                || trim((string) ($selfShipment['shipping_provider_id'] ?? '')) === '')) {
            return [
                'error' => 'self_shipment_required',
                'message' => 'Seller Shipping memerlukan nomor resi dan shipping_provider_id.',
                'package_id' => $packageId,
            ];
        }
        $shipped = $this->post(
            $store,
            '/fulfillment/202309/packages/' . rawurlencode($packageId) . '/ship',
            $shipPayload
        );

        // Make the generic logistics service idempotent for TikTok's known
        // "already shipped" responses.
        if (($shipped['code'] ?? null) !== 0 && $this->isAlreadyShipped($shipped)) {
            return [
                'error' => 'logistics.ship_order_already_shipped',
                'message' => $shipped['message'] ?? 'Paket TikTok sudah dikirim sebelumnya.',
                'response' => $shipped,
                'package_id' => $packageId,
            ];
        }

        $shipped['package_id'] = $packageId;
        return $shipped;
    }

    public function createShippingDocument(Store $store, array $orderSnList): array
    {
        return $this->getShippingDocument($store, $orderSnList);
    }

    public function getShippingDocument(Store $store, array $orderSnList): array
    {
        $documents = [];

        foreach ($orderSnList as $entry) {
            $orderId = is_array($entry)
                ? (string) ($entry['order_id'] ?? $entry['order_sn'] ?? '')
                : (string) $entry;
            if ($orderId === '') {
                continue;
            }

            $packageId = is_array($entry)
                ? (string) ($entry['package_id'] ?? $entry['package_number'] ?? '')
                : '';
            if ($packageId === '') {
                $packageId = (string) ($this->logisticsContext($store, $orderId)['package_id'] ?? '');
            }
            if ($packageId === '') {
                return [
                    'error' => 'package_id_missing',
                    'message' => 'Package ID TikTok belum tersedia. Atur pengiriman terlebih dahulu.',
                ];
            }

            // TikTok requires the document type for this endpoint. Without
            // it the API returns 36009004 ("DocumentType is required") even
            // when the package has already been shipped.
            $result = $this->get(
                $store,
                '/fulfillment/202309/packages/' . rawurlencode($packageId) . '/shipping_documents',
                [
                    'document_type' => 'SHIPPING_LABEL',
                    'document_size' => 'A6',
                    'document_format' => 'PDF',
                ]
            );
            if (($result['code'] ?? null) !== 0) {
                return $result;
            }

            $data = is_array($result['data'] ?? null) ? $result['data'] : [];
            $documents[] = [
                'order_id' => $orderId,
                'package_id' => $packageId,
                'doc_url' => $data['doc_url'] ?? $data['document_url'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'document_type' => $data['document_type'] ?? null,
                'raw' => $data,
            ];
        }

        return [
            'code' => 0,
            'message' => 'Success',
            'response' => ['documents' => $documents],
        ];
    }

    /**
     * Ambil daftar retur dan refund dari TikTok Shop.
     * Menggunakan endpoint resmi v202309 /return_refund/202309/returns/search
     * dan dinormalisasi agar kompatibel dengan modul retur & SyncMarketplaceReturns.
     */
    public function getReturnList(Store $store, int $pageNo = 0, int $pageSize = 40, ?int $createTimeFrom = null, ?int $createTimeTo = null): array
    {
        $pageSize = max(1, min($pageSize, 100));
        $body = [
            'page_size' => $pageSize,
        ];

        // Filter waktu jika disediakan (TikTok format: unix timestamp seconds)
        if ($createTimeFrom !== null && $createTimeTo !== null) {
            $body['create_time_from'] = $createTimeFrom;
            $body['create_time_to'] = $createTimeTo;
        }

        $result = $this->post($store, '/return_refund/202309/returns/search', $body);

        // Tangani error izin/scope (105005) atau error API lainnya secara anggun
        if (! empty($result['error']) || ((int) ($result['code'] ?? 0) !== 0)) {
            $code = $result['code'] ?? -1;
            $isScopeError = (string) $code === '105005'
                || str_contains(strtolower((string) ($result['message'] ?? '')), 'access denied')
                || str_contains(strtolower((string) ($result['message'] ?? '')), 'scope');

            $message = $isScopeError
                ? 'Akses Return & Refund belum diaktifkan di Partner Center (Scope seller.return_refund.basic diperlukan).'
                : (string) ($result['message'] ?? $result['error'] ?? 'Gagal mengambil retur TikTok Shop.');

            Log::warning("[TikTokShopChannel] getReturnList: {$message}", [
                'store_id' => $store->id,
                'code' => $code,
            ]);

            return [
                'error' => $isScopeError ? 'scope_missing' : true,
                'code' => $code,
                'message' => $message,
                'response' => [
                    'return' => [],
                    'more' => false,
                ],
            ];
        }

        $returnsData = data_get($result, 'data.returns', []);
        if (! is_array($returnsData)) {
            $returnsData = [];
        }

        $normalizedReturns = array_map(function (array $r): array {
            $returnType = strtoupper((string) ($r['return_type'] ?? ''));
            $isRefundOnly = $returnType === 'REFUND_ONLY'
                || (str_contains($returnType, 'REFUND') && ! str_contains($returnType, 'RETURN'));

            $rawItems = is_array($r['sku_list'] ?? null)
                ? $r['sku_list']
                : (is_array($r['items'] ?? null) ? $r['items'] : []);

            $items = array_map(function (array $it): array {
                return [
                    'item_sku' => $it['seller_sku'] ?? $it['sku_id'] ?? null,
                    'variation_sku' => $it['sku_id'] ?? null,
                    'item_name' => $it['product_name'] ?? null,
                    'variation_name' => $it['sku_name'] ?? null,
                    'return_item_quantity' => (int) ($it['return_quantity'] ?? $it['quantity'] ?? 1),
                    'images' => ! empty($it['sku_image']) ? [$it['sku_image']] : [],
                ];
            }, $rawItems);

            return [
                'return_sn' => (string) ($r['return_id'] ?? $r['id'] ?? ''),
                'order_sn' => (string) ($r['order_id'] ?? ''),
                'status' => (string) ($r['return_status'] ?? $r['status'] ?? ''),
                'reason' => $r['return_reason_text'] ?? $r['return_reason'] ?? $r['reason'] ?? null,
                'reason_text_code' => $r['return_reason'] ?? null,
                'return_solution' => $isRefundOnly ? 1 : 0,
                'amount_before_discount' => (float) ($r['refund_total'] ?? $r['refund_amount'] ?? 0),
                'needs_logistics' => ! $isRefundOnly,
                'tracking_number' => $r['tracking_number'] ?? null,
                'create_time' => $r['create_time'] ?? null,
                'update_time' => $r['update_time'] ?? null,
                'item' => $items,
                'raw' => $r,
            ];
        }, $returnsData);

        return [
            'code' => 0,
            'message' => 'Success',
            'response' => [
                'return' => $normalizedReturns,
                'more' => (bool) data_get($result, 'data.next_page_token') || (bool) data_get($result, 'data.has_more'),
                'next_cursor' => (string) data_get($result, 'data.next_page_token', ''),
            ],
        ];
    }

    /**
     * Ambil detail satu retur dari TikTok Shop.
     */
    public function getReturnDetail(Store $store, string $returnSn): array
    {
        $returnSn = trim($returnSn);
        if ($returnSn === '') {
            return ['error' => 'return_sn_missing', 'message' => 'Return ID wajib diisi.'];
        }

        $result = $this->get($store, '/return_refund/202309/returns/' . rawurlencode($returnSn));

        if (! empty($result['error']) || ((int) ($result['code'] ?? 0) !== 0)) {
            return [
                'error' => true,
                'code' => $result['code'] ?? -1,
                'message' => $result['message'] ?? 'Gagal mengambil detail retur TikTok Shop.',
            ];
        }

        $r = data_get($result, 'data', []);
        $returnType = strtoupper((string) ($r['return_type'] ?? ''));
        $isRefundOnly = $returnType === 'REFUND_ONLY'
            || (str_contains($returnType, 'REFUND') && ! str_contains($returnType, 'RETURN'));

        $rawItems = is_array($r['sku_list'] ?? null)
            ? $r['sku_list']
            : (is_array($r['items'] ?? null) ? $r['items'] : []);

        $items = array_map(function (array $it): array {
            return [
                'item_sku' => $it['seller_sku'] ?? $it['sku_id'] ?? null,
                'variation_sku' => $it['sku_id'] ?? null,
                'item_name' => $it['product_name'] ?? null,
                'variation_name' => $it['sku_name'] ?? null,
                'return_item_quantity' => (int) ($it['return_quantity'] ?? $it['quantity'] ?? 1),
                'images' => ! empty($it['sku_image']) ? [$it['sku_image']] : [],
            ];
        }, $rawItems);

        return [
            'code' => 0,
            'message' => 'Success',
            'response' => [
                'return_sn' => (string) ($r['return_id'] ?? $r['id'] ?? $returnSn),
                'order_sn' => (string) ($r['order_id'] ?? ''),
                'status' => (string) ($r['return_status'] ?? $r['status'] ?? ''),
                'reason' => $r['return_reason_text'] ?? $r['return_reason'] ?? $r['reason'] ?? null,
                'reason_text_code' => $r['return_reason'] ?? null,
                'return_solution' => $isRefundOnly ? 1 : 0,
                'amount_before_discount' => (float) ($r['refund_total'] ?? $r['refund_amount'] ?? 0),
                'needs_logistics' => ! $isRefundOnly,
                'tracking_number' => $r['tracking_number'] ?? null,
                'create_time' => $r['create_time'] ?? null,
                'update_time' => $r['update_time'] ?? null,
                'item' => $items,
                'raw' => $r,
            ],
        ];
    }

    /**
     * Ambil rincian transaksi keuangan per order dari TikTok Shop.
     *
     * Endpoint v202501 menggantikan versi v202309 dan mengembalikan
     * revenue, fee/tax, ongkir, settlement_amount, serta rincian SKU.
     */
    public function getOrderStatementTransactions(Store $store, string $orderId): array
    {
        $orderId = trim($orderId);
        if ($orderId === '') {
            return [
                'error' => 'order_id_missing',
                'message' => 'Order ID TikTok wajib diisi.',
            ];
        }

        return $this->get(
            $store,
            '/finance/202501/orders/' . rawurlencode($orderId) . '/statement_transactions'
        );
    }

    /**
     * Ambil transaksi finansial level akun seller.
     *
     * Endpoint ini berbeda dari statement per order: hasilnya berisi mutasi
     * akun seperti WITHDRAW, SETTLE, TRANSFER, REVERSE, PAYMENT, dan REFUND.
     */
    public function getSellerFinancialTransactions(
        Store $store,
        int $createTimeFrom,
        int $createTimeTo,
        array $types,
        string $pageToken = '',
        int $pageSize = 100,
    ): array {
        $pageSize = max(1, min($pageSize, 100));
        $types = array_values(array_filter(array_map(
            static fn (mixed $type): string => strtoupper(trim((string) $type)),
            $types,
        )));

        return $this->get($store, '/finance/202309/withdrawals', array_filter([
            'create_time_ge' => $createTimeFrom,
            'create_time_lt' => $createTimeTo,
            'types' => implode(',', $types),
            'page_size' => $pageSize,
            'page_token' => $pageToken !== '' ? $pageToken : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    /**
     * Ambil daftar statement seller dalam rentang waktu tertentu.
     * Statement endpoint dipakai sebagai sumber biaya GMV Max yang benar-benar
     * dipotong dari settlement, bukan sebagai pengganti report performa iklan.
     */
    public function getStatements(
        Store $store,
        int $statementTimeFrom,
        int $statementTimeTo,
        string $pageToken = '',
        int $pageSize = 100,
    ): array {
        $pageSize = max(1, min($pageSize, 100));

        return $this->get($store, '/finance/202309/statements', [
            'statement_time_ge' => $statementTimeFrom,
            'statement_time_lt' => $statementTimeTo,
            'page_size' => $pageSize,
            'page_token' => $pageToken,
            'sort_field' => 'statement_time',
            'sort_order' => 'ASC',
        ]);
    }

    /**
     * Ambil transaksi detail dari satu statement.
     * v202501 menyediakan fee_tax_breakdown, termasuk biaya GMV Max.
     */
    public function getTransactionsByStatement(
        Store $store,
        string $statementId,
        string $pageToken = '',
        int $pageSize = 100,
    ): array {
        $statementId = trim($statementId);
        if ($statementId === '') {
            return [
                'error' => 'statement_id_missing',
                'message' => 'Statement ID TikTok wajib diisi.',
            ];
        }

        $pageSize = max(1, min($pageSize, 100));

        return $this->get(
            $store,
            '/finance/202501/statements/' . rawurlencode($statementId) . '/statement_transactions',
            [
                'page_size' => $pageSize,
                'page_token' => $pageToken,
                'sort_field' => 'order_create_time',
                'sort_order' => 'ASC',
            ],
        );
    }

    /**
     * Ambil transaksi TikTok yang belum settle.
     *
     * Nilai dari endpoint ini adalah estimasi dan dapat berubah sampai
     * transaksi masuk ke statement final. Endpoint ini hanya digunakan untuk
     * mengisi kolom estimasi di halaman settlement, bukan sebagai payout final.
     */
    public function getUnsettledTransactions(
        Store $store,
        int $searchTimeFrom,
        int $searchTimeTo,
        string $pageToken = '',
        int $pageSize = 100,
    ): array {
        $pageSize = max(1, min($pageSize, 100));

        return $this->get($store, '/finance/202507/orders/unsettled', [
            'search_time_ge' => $searchTimeFrom,
            'search_time_lt' => $searchTimeTo,
            'page_size' => $pageSize,
            'page_token' => $pageToken,
            'sort_field' => 'order_create_time',
            'sort_order' => 'DESC',
        ]);
    }

    /**
     * TikTok's tracking API is order-based, unlike Shopee's order detail
     * package shape. Keep a small compatibility response for the shared
     * logistics service/controller.
     */
    public function getTrackingNumber(Store $store, string $orderSn): array
    {
        $result = $this->get($store, '/fulfillment/202309/orders/' . rawurlencode($orderSn) . '/tracking');
        if (($result['code'] ?? null) !== 0) {
            return $result;
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $tracking = $data['tracking_number']
            ?? data_get($data, 'packages.0.tracking_number')
            ?? data_get($data, 'package_list.0.tracking_number')
            ?? data_get($data, 'tracking.0.tracking_number')
            ?? data_get($data, 'tracking.0.tracking_no')
            ?? data_get($data, 'tracking_info.0.tracking_number');

        $result['response'] = ['tracking_number' => $tracking, 'raw' => $data];
        return $result;
    }

    public function getTrackingInfo(Store $store, string $orderSn): array
    {
        $result = $this->getTrackingNumber($store, $orderSn);
        $tracking = data_get($result, 'response.raw.tracking', data_get($result, 'response.raw.tracking_info', []));
        $result['response']['tracking_info'] = is_array($tracking) ? $tracking : [];
        return $result;
    }

    public function getPackageDetail(Store $store, string $packageId): array
    {
        return $this->get($store, '/fulfillment/202309/packages/' . rawurlencode($packageId));
    }

    protected function buildShipPayload(Store $store, array $params, array $context): array
    {
        $payload = [];

        if ($this->isSellerShipping($context, $params)) {
            $selfShipment = is_array($params['self_shipment'] ?? null)
                ? $params['self_shipment']
                : [
                    'tracking_number' => $params['tracking_number'] ?? null,
                    'shipping_provider_id' => $params['shipping_provider_id'] ?? null,
                ];
            $selfShipment = array_filter(
                $selfShipment,
                static fn ($value): bool => $value !== null && trim((string) $value) !== ''
            );
            if ($selfShipment !== []) {
                $payload['self_shipment'] = $selfShipment;
            }

            return $payload;
        }

        // TikTok's SEA API no longer accepts handover_method/pickup_slot for
        // ID, SG, PH, VN, TH, and MY. Collection is configured in Seller
        // Center warehouse settings, so omit these fields in those regions.
        if (! $this->isSeaStore($store)) {
            if (isset($params['dropoff'])) {
                $payload['handover_method'] = 'DROP_OFF';
            } elseif (isset($params['pickup'])) {
                $payload['handover_method'] = 'PICKUP';
            }

            foreach (['handover_method', 'pickup_slot'] as $key) {
                if (array_key_exists($key, $params) && $params[$key] !== null && $params[$key] !== []) {
                    $payload[$key] = $params[$key];
                }
            }
        }

        return $payload;
    }

    protected function isSellerShipping(array $context, array $params = []): bool
    {
        $shippingType = trim((string) ($params['shipping_type'] ?? ''));
        if ($shippingType === '') {
            $shippingType = trim((string) ($context['shipping_type'] ?? ''));
        }
        if ($shippingType === '') {
            $shippingType = trim((string) ($context['delivery_option'] ?? ''));
        }
        $shippingType = strtoupper($shippingType);
        $shippingType = str_replace(['-', ' '], '_', $shippingType);

        return str_contains($shippingType, 'SELLER')
            || in_array($shippingType, ['SELF', 'SELF_SHIPPING', 'MERCHANT'], true);
    }

    protected function isSeaStore(Store $store): bool
    {
        $region = strtoupper(trim((string) $store->region));
        return in_array(substr($region, 0, 2), self::SEA_REGIONS, true);
    }

    protected function isAlreadyShipped(array $response): bool
    {
        $code = (string) ($response['code'] ?? '');
        $message = strtolower((string) ($response['message'] ?? ''));

        return in_array($code, ['11006021', '21008099', '21011020', '21011040'], true)
            || str_contains($message, 'already shipped')
            || str_contains($message, 'already been shipped');
    }

    /**
     * Build fulfillment inputs from the latest local TikTok order snapshot.
     * No write is performed here; this method is safe for the GET modal call.
     */
    protected function logisticsContext(Store $store, string $orderSn): array
    {
        $order = MarketplaceOrder::query()
            ->where('store_id', $store->id)
            ->where(function ($query) use ($orderSn) {
                $query->where('channel_order_id', $orderSn)
                    ->orWhere('external_order_id', $orderSn);
            })
            ->first();
        $raw = is_array($order?->raw_json) ? $order->raw_json : [];

        $orderId = (string) ($raw['order_id'] ?? $raw['id'] ?? $order?->channel_order_id ?? $orderSn);
        $packages = is_array($raw['packages'] ?? null)
            ? $raw['packages']
            : (is_array($raw['package_list'] ?? null) ? $raw['package_list'] : []);
        $package = is_array($packages[0] ?? null) ? $packages[0] : [];
        $packageId = (string) ($package['package_id'] ?? $package['id'] ?? $package['package_number'] ?? '');

        $lineItems = is_array($raw['line_items'] ?? null)
            ? $raw['line_items']
            : (is_array($raw['item_list'] ?? null) ? $raw['item_list'] : []);
        $orderLineItemIds = [];
        $orderLineList = [];
        foreach ($lineItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            $lineId = (string) ($item['order_line_item_id'] ?? $item['line_item_id'] ?? $item['order_line_id'] ?? $item['id'] ?? '');
            $subItemId = (string) ($item['sub_item_id'] ?? '');
            if ($lineId !== '') {
                $orderLineItemIds[] = $lineId;
                $orderLineList[] = array_filter([
                    'order_line_id' => $lineId,
                    'sub_item_id' => $subItemId !== '' ? $subItemId : null,
                ], static fn ($value): bool => $value !== null && $value !== '');
            }
        }

        $fallback = config('tiktok_shop.logistics', []);
        $weight = $this->firstArray($package, $raw, ['weight', 'package_weight']);
        $dimension = $this->firstArray($package, $raw, ['dimension', 'dimensions', 'package_dimension']);
        $weight = $this->normalizeWeight($weight, $fallback);
        $dimension = $this->normalizeDimension($dimension, $fallback);

        return [
            'order_id' => $orderId,
            'package_id' => $packageId !== '' ? $packageId : null,
            'shipping_type' => $package['shipping_type']
                ?? $package['shipping_type_name']
                ?? $raw['shipping_type']
                ?? $raw['shipping_type_name']
                ?? $raw['fulfillment_type']
                ?? null,
            'delivery_option' => $package['delivery_option'] ?? $raw['delivery_option'] ?? $raw['delivery_option_id'] ?? null,
            'order_line_item_ids' => array_values(array_unique($orderLineItemIds)),
            'order_line_list' => $orderLineList,
            'weight' => $weight,
            'dimension' => $dimension,
        ];
    }

    protected function firstArray(array $primary, array $secondary, array $keys): array
    {
        foreach ($keys as $key) {
            $value = $primary[$key] ?? $secondary[$key] ?? null;
            if (is_array($value) && $value !== []) {
                return $value;
            }
        }

        return [];
    }

    protected function normalizeWeight(array $weight, array $fallback): array
    {
        return [
            'value' => (string) ($weight['value'] ?? $weight['amount'] ?? $fallback['weight_gram'] ?? 1000),
            'unit' => strtoupper((string) ($weight['unit'] ?? 'GRAM')),
        ];
    }

    protected function normalizeDimension(array $dimension, array $fallback): array
    {
        return [
            'length' => (string) ($dimension['length'] ?? $fallback['length_cm'] ?? 20),
            'width' => (string) ($dimension['width'] ?? $fallback['width_cm'] ?? 15),
            'height' => (string) ($dimension['height'] ?? $fallback['height_cm'] ?? 5),
            'unit' => strtoupper((string) ($dimension['unit'] ?? 'CM')),
        ];
    }

    public function getOrders(Store $store, int $timeFrom, int $timeTo, int $pageSize = 20, string $cursor = '', string $orderStatus = '', string $timeRangeField = 'update_time'): array
    {
        // TikTok Shop v202309 uses POST /orders/search and page_token
        // pagination. The channel contract calls this value cursor.
        $prefix = $timeRangeField === 'create_time' ? 'create_time' : 'update_time';
        $body = [
            $prefix . '_ge' => $timeFrom,
            $prefix . '_lt' => $timeTo,
        ];

        if ($orderStatus !== '') {
            $body['order_status'] = $orderStatus;
        }

        $params = [
            'page_size'  => min(100, max(1, $pageSize)),
            'sort_field' => $prefix,
            'sort_order' => 'ASC',
        ];
        if ($cursor) {
            $params['page_token'] = $cursor;
        }

        return $this->normalizeOrderResponse(
            $this->post($store, '/order/202309/orders/search', $body, $params)
        );
    }

    public function getOrderDetail(Store $store, array $orderSnList): array
    {
        return $this->normalizeOrderResponse($this->get($store, '/order/202309/orders', [
            'ids' => implode(',', $orderSnList),
        ]));
    }

    /**
     * Keep the existing marketplace sync contract while accepting the
     * current TikTok response shape (`data.orders`).
     */
    protected function normalizeOrderResponse(array $response): array
    {
        $orders = data_get($response, 'data.orders');
        if (! is_array($orders)) {
            return $response;
        }

        // MarketplaceSyncService memakai schema order yang sama untuk semua
        // channel. TikTok mengembalikan `id`, `status`, `line_items`, dan
        // `payment`, sedangkan schema internal memakai `order_sn`,
        // `order_status`, `item_list`, dan field finansial di level order.
        // Tanpa normalisasi ini detail TikTok akan dianggap kosong karena
        // upsertOrders() hanya menerima detail yang memiliki order_sn.
        $orders = array_map(fn (array $order): array => $this->normalizeOrder($order), $orders);

        $response['response']['order_list'] = $orders;
        $response['response']['next_cursor'] = data_get($response, 'data.next_page_token');
        $response['response']['more'] = filled(data_get($response, 'data.next_page_token'));

        return $response;
    }

    /**
     * Adapt one TikTok Shop order to the shared marketplace order schema.
     * Keep the original TikTok fields as well so raw_json remains useful for
     * diagnostics and future fields can be consumed without another API call.
     */
    protected function normalizeOrder(array $order): array
    {
        $status = strtoupper((string) ($order['status'] ?? $order['order_status'] ?? ''));
        $statusMap = [
            'ON_HOLD'            => 'PENDING',
            'AWAITING_SHIPMENT'  => 'READY_TO_SHIP',
            'PARTIALLY_SHIPPING' => 'PROCESSED',
            'AWAITING_COLLECTION' => 'PROCESSED',
            'IN_TRANSIT'         => 'SHIPPED',
            'DELIVERED'          => 'TO_CONFIRM_RECEIVE',
            'COMPLETED'          => 'COMPLETED',
            'CANCELLED'          => 'CANCELLED',
            'IN_CANCEL'          => 'IN_CANCEL',
        ];
        $canonicalStatus = $statusMap[$status] ?? ($status !== '' ? $status : null);

        $address = is_array($order['recipient_address'] ?? null)
            ? $order['recipient_address']
            : [];
        $firstName = trim((string) ($address['first_name'] ?? ''));
        $lastName = trim((string) ($address['last_name'] ?? ''));
        $addressName = trim((string) ($address['name'] ?? ''));
        $fullAddress = trim((string) ($address['full_address'] ?? ''));
        if ($fullAddress === '') {
            $fullAddress = trim(implode(', ', array_filter([
                $address['address_line1'] ?? null,
                $address['address_line2'] ?? null,
                $address['address_line3'] ?? null,
                $address['address_line4'] ?? null,
                $address['address_detail'] ?? null,
            ], static fn ($part): bool => trim((string) $part) !== '')));
        }

        $normalizedAddress = array_merge($address, [
            'name' => $addressName !== '' ? $addressName : trim($firstName . ' ' . $lastName),
            'phone_number' => $address['phone_number'] ?? $address['phone'] ?? null,
            'full_address' => $fullAddress !== '' ? $fullAddress : null,
            'zip_code' => $address['zip_code'] ?? $address['postal_code'] ?? null,
            'city' => $address['city'] ?? $address['district'] ?? null,
            'state' => $address['state'] ?? $address['province'] ?? null,
        ]);

        $payment = is_array($order['payment'] ?? null) ? $order['payment'] : [];
        $trackingNumber = $order['tracking_number'] ?? null;
        $shippingCarrier = $order['shipping_provider'] ?? $order['shipping_provider_name'] ?? null;
        $packages = is_array($order['packages'] ?? null) ? $order['packages'] : [];
        $packageList = array_map(static function (array $package) use ($trackingNumber, $shippingCarrier): array {
            return array_merge($package, [
                'package_number' => $package['id'] ?? $package['package_id'] ?? null,
                'tracking_no' => $package['tracking_number'] ?? $trackingNumber,
                'tracking_number' => $package['tracking_number'] ?? $trackingNumber,
                'shipping_carrier' => $package['shipping_carrier'] ?? $shippingCarrier,
            ]);
        }, $packages);
        if ($packageList === [] && ($trackingNumber || $shippingCarrier)) {
            $packageList[] = [
                'tracking_no' => $trackingNumber,
                'tracking_number' => $trackingNumber,
                'shipping_carrier' => $shippingCarrier,
            ];
        }

        $lineItems = is_array($order['line_items'] ?? null) ? $order['line_items'] : [];
        $disputeReasons = [];
        $itemList = array_map(static function (array $item) use (&$disputeReasons): array {
            $quantity = (int) ($item['quantity']
                ?? $item['quantity_purchased']
                ?? $item['model_quantity_purchased']
                ?? 1);
            $sellerSku = $item['seller_sku'] ?? $item['sku'] ?? null;
            $originalPrice = $item['original_price'] ?? $item['model_original_price'] ?? 0;
            $salePrice = $item['sale_price']
                ?? $item['discounted_price']
                ?? $item['model_discounted_price']
                ?? $originalPrice;

            $cancelReason = trim((string) ($item['cancel_reason'] ?? ''));
            $cancelUser = trim((string) ($item['cancel_user'] ?? ''));
            $displayStatus = strtoupper(trim((string) ($item['display_status'] ?? '')));
            if ($cancelReason !== '') {
                $disputeReasons[] = $cancelReason . ($cancelUser !== '' ? " ({$cancelUser})" : '');
            }

            return array_merge($item, [
                'item_id' => $item['product_id'] ?? $item['item_id'] ?? null,
                'model_id' => $item['sku_id'] ?? $item['model_id'] ?? null,
                'item_name' => $item['product_name'] ?? $item['item_name'] ?? null,
                'item_sku' => $sellerSku,
                'model_sku' => $sellerSku,
                'model_name' => $item['sku_name'] ?? $item['model_name'] ?? null,
                'model_quantity_purchased' => max(1, $quantity),
                'model_original_price' => $originalPrice,
                'model_discounted_price' => $salePrice,
                'cancel_reason' => $cancelReason !== '' ? $cancelReason : null,
                'cancel_user' => $cancelUser !== '' ? $cancelUser : null,
                'display_status' => $displayStatus !== '' ? $displayStatus : null,
                'image_info' => [
                    'image_url' => $item['sku_image'] ?? data_get($item, 'image_info.image_url'),
                ],
            ]);
        }, $lineItems);

        return array_merge($order, [
            'order_sn' => (string) ($order['id'] ?? $order['order_id'] ?? $order['order_sn'] ?? ''),
            'order_status' => $canonicalStatus,
            'platform_order_status' => $status,
            'recipient_address' => $normalizedAddress,
            'package_list' => $packageList,
            'item_list' => $itemList,
            'buyer_username' => $order['buyer_username'] ?? $order['user_id'] ?? null,
            'shipping_carrier' => $shippingCarrier,
            'tracking_number' => $trackingNumber,
            'payment_method' => $order['payment_method_name'] ?? $order['payment_method'] ?? null,
            'total_amount' => $payment['total_amount'] ?? $order['total_amount'] ?? 0,
            'actual_shipping_fee' => $payment['shipping_fee'] ?? $order['shipping_fee'] ?? 0,
            'buyer_paid_shipping_fee' => $payment['shipping_fee'] ?? $order['shipping_fee'] ?? 0,
            'currency' => $payment['currency'] ?? data_get($itemList, '0.currency', 'IDR'),
            'has_return_request' => $disputeReasons !== [],
            'dispute_reason' => $disputeReasons !== [] ? implode('; ', array_unique($disputeReasons)) : null,
        ]);
    }

    public function getEscrowDetail(Store $store, string $orderSn): array
    {
        // TikTok Shop belum support escrow detail via open API — return stub
        return ['code' => -1, 'message' => 'Not supported for TikTok Shop'];
    }

    public function getEscrowDetailBatch(Store $store, array $orderSnList): array
    {
        return ['code' => -1, 'message' => 'Not supported for TikTok Shop'];
    }

    public function getEscrowList(
        Store $store,
        int $releaseTimeFrom,
        int $releaseTimeTo,
        int $pageNo = 1,
        int $pageSize = 100
    ): array {
        return ['code' => -1, 'error' => 'not_supported', 'message' => 'Not supported for TikTok Shop'];
    }

    public function getPayoutInfo(
        Store $store,
        int $payoutTimeFrom,
        int $payoutTimeTo,
        string $cursor = '',
        int $pageSize = 100
    ): array {
        return ['code' => -1, 'error' => 'not_supported', 'message' => 'Not supported for TikTok Shop'];
    }

    public function getPayoutDetail(
        Store $store,
        int $payoutTimeFrom,
        int $payoutTimeTo,
        int $pageNo = 1,
        int $pageSize = 100
    ): array {
        return ['code' => -1, 'error' => 'not_supported', 'message' => 'Not supported for TikTok Shop'];
    }

    // ─── Ads stubs (TikTok Ads API berbeda, belum diimplementasi) ────────────

    public function getShopToggleInfo(Store $store): array
    {
        return ['code' => -1, 'message' => 'Not supported for TikTok Shop'];
    }

    public function getCampaignIdList(Store $store, int $pageNo = 1, int $pageSize = 100): array
    {
        return ['code' => -1, 'message' => 'Not supported for TikTok Shop'];
    }

    public function getCampaignSettingInfo(Store $store, array $campaignIds): array
    {
        return ['code' => -1, 'message' => 'Not supported for TikTok Shop'];
    }

    public function getCampaignDailyPerformance(Store $store, array $campaignIds, string $startDate, string $endDate): array
    {
        return ['code' => -1, 'message' => 'Not supported for TikTok Shop'];
    }

    public function getDiscountList(
        Store $store,
        string $status = 'ongoing',
        int $pageNo = 1,
        int $pageSize = 100,
        ?int $updateTimeFrom = null,
        ?int $updateTimeTo = null
    ): array {
        return ['error' => 'not_implemented', 'message' => 'Fitur diskon belum tersedia untuk TikTok Shop'];
    }

    public function addDiscount(Store $store, string $discountName, int $startTime, int $endTime): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur diskon belum tersedia untuk TikTok Shop'];
    }

    public function addDiscountItem(Store $store, int $discountId, array $itemList): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur diskon belum tersedia untuk TikTok Shop'];
    }

    public function updateDiscountItem(Store $store, int $discountId, array $itemList): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur diskon belum tersedia untuk TikTok Shop'];
    }

    public function endDiscount(Store $store, int $discountId): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur diskon belum tersedia untuk TikTok Shop'];
    }

    public function getDiscount(Store $store, int $discountId, int $pageNo = 1, int $pageSize = 50): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur diskon belum tersedia untuk TikTok Shop'];
    }

    public function updateDiscount(
        Store $store,
        int $discountId,
        ?string $discountName = null,
        ?int $startTime = null,
        ?int $endTime = null
    ): array {
        return ['error' => 'not_implemented', 'message' => 'Fitur diskon belum tersedia untuk TikTok Shop'];
    }

    public function deleteDiscount(Store $store, int $discountId): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur diskon belum tersedia untuk TikTok Shop'];
    }

    public function deleteDiscountItem(Store $store, int $discountId, int $itemId, int $modelId = 0): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur diskon belum tersedia untuk TikTok Shop'];
    }

    public function getSipDiscounts(Store $store, ?string $region = null): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur SIP discount belum tersedia untuk TikTok Shop'];
    }

    public function setSipDiscount(Store $store, string $region, int $sipDiscountRate): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur SIP discount belum tersedia untuk TikTok Shop'];
    }

    public function deleteSipDiscount(Store $store, string $region): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur SIP discount belum tersedia untuk TikTok Shop'];
    }

    public function getVoucherList(Store $store, string $status = 'all', int $pageNo = 1, int $pageSize = 100): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur voucher belum tersedia untuk TikTok Shop'];
    }

    public function addVoucher(Store $store, array $payload): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur voucher belum tersedia untuk TikTok Shop'];
    }

    public function updateVoucher(Store $store, int $voucherId, array $payload = []): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur voucher belum tersedia untuk TikTok Shop'];
    }

    public function getVoucher(Store $store, int $voucherId): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur voucher belum tersedia untuk TikTok Shop'];
    }

    public function endVoucher(Store $store, int $voucherId): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur voucher belum tersedia untuk TikTok Shop'];
    }

    public function deleteVoucher(Store $store, int $voucherId): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur voucher belum tersedia untuk TikTok Shop'];
    }

    // ─── Refresh token ────────────────────────────────────────────────────────

    public function refreshToken(Store $store, bool $force = false): array
    {
        if (! $store->is_active) {
            // Toko nonaktif: jangan hubungi API auth TikTok sama sekali.
            return ['error' => 'store_inactive', 'message' => 'Toko nonaktif, refresh token dilewati'];
        }

        if (blank($store->credential('refresh_token'))) {
            // Toko belum terkonfigurasi (tanpa refresh_token). Jangan panggil API auth.
            return ['error' => 'not_configured', 'message' => 'Kredensial toko belum lengkap, refresh dilewati'];
        }

        $authUrl   = rtrim(config('tiktok_shop.auth_url', 'https://auth.tiktok-shops.com'), '/');
        $appKey    = $this->appKey($store);
        $appSecret = $this->appSecret($store);

        $response = Http::connectTimeout(10)->timeout(30)
            ->retry(2, 1000, throw: false)
            ->get($authUrl . '/api/v2/token/refresh', [
            'app_key'       => $appKey,
            'app_secret'    => $appSecret,
            'refresh_token' => $store->credential('refresh_token'),
            'grant_type'    => 'refresh_token',
        ]);

        $data = $response->json() ?? [];
        $body = $data['data'] ?? $data;

        if (! $response->successful() || (isset($data['code']) && (int) $data['code'] !== 0)) {
            Log::warning('TikTok token refresh failed', [
                'store'       => $store->code,
                'http_status' => $response->status(),
                'code'        => $data['code'] ?? null,
                'request_id'  => $data['request_id'] ?? null,
                'message'     => $data['message'] ?? null,
            ]);

            return $data;
        }

        if (! empty($body['access_token'])) {
            $credentials = $store->credentials ?? [];
            $credentials['access_token']  = $body['access_token'];
            $credentials['refresh_token'] = $body['refresh_token'] ?? $credentials['refresh_token'] ?? null;
            $accessTokenExpiresIn = $body['access_token_expire_in'] ?? $body['expires_in'] ?? null;
            $refreshTokenExpiresIn = $body['refresh_token_expire_in'] ?? $body['refresh_expires_in'] ?? null;
            $credentials['access_token_expire_in'] = $accessTokenExpiresIn
                ?? $credentials['access_token_expire_in']
                ?? null;
            $credentials['refresh_token_expire_in'] = $refreshTokenExpiresIn
                ?? $credentials['refresh_token_expire_in']
                ?? null;

            foreach (['open_id', 'user_type'] as $field) {
                if (array_key_exists($field, $body)) {
                    $credentials[$field] = $body[$field];
                }
            }

            $meta = $store->meta ?? [];
            if (array_key_exists('open_id', $body)) {
                $meta['open_id'] = $body['open_id'];
            }
            if (array_key_exists('user_type', $body)) {
                $meta['user_type'] = $body['user_type'];
            }
            if (array_key_exists('granted_scopes', $body)) {
                $meta['granted_scopes'] = array_values((array) $body['granted_scopes']);
            } elseif (array_key_exists('scopes', $body)) {
                $meta['granted_scopes'] = array_values((array) $body['scopes']);
            }

            $store->update([
                'credentials'      => $credentials,
                'meta'             => $meta,
                'token_expires_at' => $accessTokenExpiresIn !== null
                    ? $this->tokenExpiresAt($accessTokenExpiresIn)
                    : $store->token_expires_at,
            ]);
        }

        return $data;
    }

    protected function tokenExpiresAt(mixed $value): ?\Illuminate\Support\Carbon
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            return null;
        }

        $value = (int) $value;

        return $value >= 1_000_000_000
            ? now()->setTimestamp($value)
            : now()->addSeconds($value);
    }
}
