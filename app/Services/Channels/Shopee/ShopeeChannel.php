<?php

namespace App\Services\Channels\Shopee;

use App\Models\Store;
use App\Services\Channels\Contracts\MarketplaceChannel;
use Illuminate\Support\Facades\Http;

class ShopeeChannel implements MarketplaceChannel
{
    protected function baseUrl(Store $store): string
    {
        return rtrim($store->credential('base_url', config('shopee.base_url', 'https://partner.shopeemobile.com')), '/');
    }

    protected function partnerId(Store $store): string
    {
        return trim((string) $store->credential('partner_id', config('shopee.partner_id')));
    }

    protected function partnerKey(Store $store): string
    {
        return trim((string) $store->credential('partner_key', config('shopee.partner_key')));
    }

    protected function shopId(Store $store): string
    {
        return trim((string) $store->credential('shop_id', $store->external_shop_id));
    }

    protected function accessToken(Store $store): string
    {
        return trim((string) $store->credential('access_token'));
    }

    protected function sign(Store $store, string $path, int $timestamp, bool $shopApi = true): string
    {
        $baseString = $this->partnerId($store) . $path . $timestamp;

        if ($shopApi) {
            $baseString .= $this->accessToken($store) . $this->shopId($store);
        }

        return hash_hmac('sha256', $baseString, $this->partnerKey($store));
    }

    protected function get(Store $store, string $path, array $params = []): array
    {
        $result = $this->doGet($store, $path, $params);

        $error = $result['error'] ?? '';
        $msg = strtolower((string)($result['message'] ?? ''));

        // Token kedaluwarsa → auto-refresh sekali lalu retry
        if ($error === 'error_auth' || str_contains($msg, 'expired') || str_contains($msg, 'access_token') || str_contains((string)$error, 'access_token')) {
            $refreshed = $this->refreshToken($store);
            if (empty($refreshed['error'])) {
                $store->refresh(); // reload credentials dari DB
                $result = $this->doGet($store, $path, $params);
            }
        }

        return $result;
    }

    protected function post(Store $store, string $path, array $body = []): array
    {
        $result = $this->doPost($store, $path, $body);

        $error = $result['error'] ?? '';
        $msg = strtolower((string)($result['message'] ?? ''));

        if ($error === 'error_auth' || str_contains($msg, 'expired') || str_contains($msg, 'access_token') || str_contains((string)$error, 'access_token')) {
            $refreshed = $this->refreshToken($store);
            if (empty($refreshed['error'])) {
                $store->refresh();
                $result = $this->doPost($store, $path, $body);
            }
        }

        return $result;
    }

    protected function doGet(Store $store, string $path, array $params = []): array
    {
        $timestamp = time();

        $query = array_merge([
            'partner_id'   => (int) $this->partnerId($store),
            'timestamp'    => $timestamp,
            'access_token' => $this->accessToken($store),
            'shop_id'      => (int) $this->shopId($store),
            'sign'         => $this->sign($store, $path, $timestamp),
        ], $params);

        $response = Http::timeout(30)->get($this->baseUrl($store) . $path, $query);

        return $response->json() ?? [
            'error'   => 'invalid_response',
            'message' => $response->body(),
            'status'  => $response->status(),
        ];
    }

    protected function doPost(Store $store, string $path, array $body = []): array
    {
        $timestamp = time();

        $query = [
            'partner_id'   => (int) $this->partnerId($store),
            'timestamp'    => $timestamp,
            'access_token' => $this->accessToken($store),
            'shop_id'      => (int) $this->shopId($store),
            'sign'         => $this->sign($store, $path, $timestamp),
        ];

        $response = Http::timeout(30)->post($this->baseUrl($store) . $path . '?' . http_build_query($query), $body);

        return $response->json() ?? [
            'error'   => 'invalid_response',
            'message' => $response->body(),
            'status'  => $response->status(),
        ];
    }

    public function getShopInfo(Store $store): array
    {
        return $this->get($store, '/api/v2/shop/get_shop_info');
    }

    public function getOrders(Store $store, int $timeFrom, int $timeTo, int $pageSize = 20, string $cursor = '', string $orderStatus = ''): array
    {
        $params = [
            'time_range_field' => 'update_time',
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'page_size' => $pageSize,
            'response_optional_fields' => 'order_status',
        ];
        if ($cursor) {
            $params['cursor'] = $cursor;
        }
        if ($orderStatus) {
            $params['order_status'] = $orderStatus;
        }
        return $this->get($store, '/api/v2/order/get_order_list', $params);
    }

    public function getOrderDetail(Store $store, array $orderSnList): array
    {
        return $this->get($store, '/api/v2/order/get_order_detail', [
            'order_sn_list' => implode(',', $orderSnList),
            'response_optional_fields' => implode(',', [
                'buyer_user_id',
                'buyer_username',
                'recipient_address',
                'actual_shipping_fee',
                'item_list',
                'pay_time',
                'package_list',
                'shipping_carrier',
                'payment_method',
                'total_amount',
                'checkout_shipping_carrier',
            ]),
        ]);
    }

    public function getPackageDetail(Store $store, string $packageNumber): array
    {
        return $this->get($store, '/api/v2/order/get_package_detail', [
            'package_number_list' => $packageNumber,
        ]);
    }

    public function getReturnList(Store $store, int $pageNo = 0, int $pageSize = 40, ?int $createTimeFrom = null, ?int $createTimeTo = null): array
    {
        $params = [
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
        
        if ($createTimeFrom !== null && $createTimeTo !== null) {
            $params['create_time_from'] = $createTimeFrom;
            $params['create_time_to'] = $createTimeTo;
        }
        
        return $this->get($store, '/api/v2/returns/get_return_list', $params);
    }

    public function getReturnDetail(Store $store, string $returnSn): array
    {
        return $this->get($store, '/api/v2/returns/get_return_detail', [
            'return_sn' => $returnSn,
        ]);
    }

    public function getReverseTrackingInfo(Store $store, string $returnSn): array
    {
        return $this->get($store, '/api/v2/returns/get_reverse_tracking_info', [
            'return_sn' => $returnSn,
        ]);
    }

    public function confirmReturn(Store $store, string $returnSn): array
    {
        return $this->post($store, '/api/v2/returns/confirm', [
            'return_sn' => $returnSn,
        ]);
    }

    public function getEscrowDetail(Store $store, string $orderSn): array
    {
        return $this->get($store, '/api/v2/payment/get_escrow_detail', [
            'order_sn' => $orderSn,
        ]);
    }

    // ─── Ads API ──────────────────────────────────────────────────────────────

    /**
     * Cek apakah iklan aktif di toko ini.
     * Endpoint: GET /api/v2/ads/get_shop_toggle_info
     */
    public function getShopToggleInfo(Store $store): array
    {
        return $this->get($store, '/api/v2/ads/get_shop_toggle_info', []);
    }

    /**
     * Ambil daftar ID campaign.
     * Endpoint: GET /api/v2/ads/get_product_level_campaign_id_list
     */
    public function getCampaignIdList(Store $store, int $pageNo = 1, int $pageSize = 100): array
    {
        return $this->get($store, '/api/v2/ads/get_product_level_campaign_id_list', [
            'page_no'   => $pageNo,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * Ambil info pengaturan campaign (nama, tipe, status, budget).
     * Endpoint: GET /api/v2/ads/get_product_level_campaign_setting_info
     */
    public function getCampaignSettingInfo(Store $store, array $campaignIds): array
    {
        return $this->get($store, '/api/v2/ads/get_product_level_campaign_setting_info', [
            'campaign_id_list' => implode(',', $campaignIds),
        ]);
    }

    /**
     * Ambil performa harian per campaign untuk rentang tanggal.
     * Endpoint: GET /api/v2/ads/get_product_campaign_daily_performance
     * Date format: DD-MM-YYYY
     */
    public function getCampaignDailyPerformance(
        Store  $store,
        array  $campaignIds,
        string $startDate,
        string $endDate
    ): array {
        return $this->get($store, '/api/v2/ads/get_product_campaign_daily_performance', [
            'campaign_id_list' => implode(',', $campaignIds),
            'start_date'       => $startDate,
            'end_date'         => $endDate,
        ]);
    }

    /**
     * Saldo iklan (ads credit) toko.
     * Endpoint: GET /api/v2/ads/get_total_balance
     */
    public function getAdsTotalBalance(Store $store): array
    {
        return $this->get($store, '/api/v2/ads/get_total_balance', []);
    }

    /**
     * Performa iklan harian level TOKO (semua campaign CPC digabung).
     * Endpoint: GET /api/v2/ads/get_all_cpc_ads_daily_performance
     * Date format: DD-MM-YYYY
     */
    public function getAdsShopDailyPerformance(Store $store, string $startDate, string $endDate): array
    {
        return $this->get($store, '/api/v2/ads/get_all_cpc_ads_daily_performance', [
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);
    }

    public function refreshToken(Store $store): array
    {
        $path = '/api/v2/auth/access_token/get';
        $timestamp = time();

        $url = $this->baseUrl($store) . $path . '?' . http_build_query([
            'partner_id' => (int) $this->partnerId($store),
            'timestamp' => $timestamp,
            'sign' => $this->sign($store, $path, $timestamp, false),
        ]);

        $response = Http::timeout(30)->post($url, [
            'refresh_token' => $store->credential('refresh_token'),
            'partner_id' => (int) $this->partnerId($store),
            'shop_id' => (int) $this->shopId($store),
        ]);

        $data = $response->json() ?? [];

        if (empty($data['error']) && !empty($data['access_token'])) {
            $credentials = $store->credentials ?? [];
            $credentials['access_token'] = $data['access_token'];
            $credentials['refresh_token'] = $data['refresh_token'] ?? $credentials['refresh_token'] ?? null;

            $store->update([
                'credentials' => $credentials,
                'token_expires_at' => now()->addSeconds((int) ($data['expire_in'] ?? 0)),
            ]);
        }

        return $data;
    }

    // ─── Logistics / Fulfillment ──────────────────────────────────────────────

    public function getShippingParameter(Store $store, string $orderSn): array
    {
        return $this->get($store, '/api/v2/logistics/get_shipping_parameter', [
            'order_sn' => $orderSn
        ]);
    }

    public function getBookingDetail(Store $store, string $orderSn): array
    {
        return $this->get($store, '/api/v2/order/get_booking_detail', [
            'order_sn' => $orderSn
        ]);
    }

    public function getBookingList(Store $store, int $timeFrom, int $timeTo, int $pageSize = 20, string $cursor = '', string $bookingStatus = ''): array
    {
        $params = [
            'time_range_field' => 'create_time',
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'page_size' => $pageSize,
            'response_optional_fields' => 'booking_status',
        ];
        if ($cursor) {
            $params['cursor'] = $cursor;
        }
        if ($bookingStatus) {
            $params['booking_status'] = $bookingStatus;
        }
        return $this->get($store, '/api/v2/order/get_booking_list', $params);
    }

    public function shipOrder(Store $store, string $orderSn, array $params = []): array
    {
        // Parameter utama ship_order: order_sn, dan salah satu dari pickup/dropoff
        $body = array_merge(['order_sn' => $orderSn], $params);
        return $this->post($store, '/api/v2/logistics/ship_order', $body);
    }

    public function getTrackingNumber(Store $store, string $orderSn): array
    {
        return $this->get($store, '/api/v2/logistics/get_tracking_number', ['order_sn' => $orderSn]);
    }

    public function createShippingDocument(Store $store, array $orderSnList): array
    {
        $defaultFormat = \App\Models\SystemSetting::get('marketplace_print_default_format', 'THERMAL_AIR_WAYBILL');
        $docType = $store->meta['shipping_document_type'] ?? $defaultFormat;
        $body = [
            'order_list' => array_map(function($item) {
                return is_array($item) ? $item : ['order_sn' => $item];
            }, $orderSnList),
            'shipping_document_type' => $docType
        ];
        return $this->post($store, '/api/v2/logistics/create_shipping_document', $body);
    }

    public function getShippingDocument(Store $store, array $orderSnList): array
    {
        $defaultFormat = \App\Models\SystemSetting::get('marketplace_print_default_format', 'THERMAL_AIR_WAYBILL');
        $docType = $store->meta['shipping_document_type'] ?? $defaultFormat;
        $body = [
            'order_list' => array_map(function($item) {
                return is_array($item) ? $item : ['order_sn' => $item];
            }, $orderSnList),
            'shipping_document_type' => $docType
        ];

        // Poll get_shipping_document_result up to 5 times
        for ($i = 0; $i < 5; $i++) {
            $result = $this->post($store, '/api/v2/logistics/get_shipping_document_result', $body);
            
            $allReady = true;
            if (isset($result['response']['result_list']) && is_array($result['response']['result_list'])) {
                foreach ($result['response']['result_list'] as $resItem) {
                    if (isset($resItem['status']) && $resItem['status'] !== 'READY') {
                        $allReady = false;
                        break;
                    }
                }
            } else {
                $allReady = false; // Invalid response format, try again
            }

            if ($allReady) {
                break;
            }

            sleep(1);
        }

        if (!$allReady) {
            return [
                'error' => 'timeout',
                'message' => 'Dokumen resi sedang diproses oleh Shopee dan belum siap. Silakan coba cetak lagi beberapa saat lagi.'
            ];
        }

        return $this->post($store, '/api/v2/logistics/download_shipping_document', $body);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Seller Chat (butuh permission Chat API di Shopee Open Platform Console)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Daftar percakapan. type: all | pinned | unread
     */
    public function getConversationList(Store $store, int $pageSize = 25, string $nextTimestampNano = '', string $type = 'all'): array
    {
        $params = [
            'direction' => 'latest',
            'type'      => $type,
            'page_size' => $pageSize,
        ];
        if ($nextTimestampNano !== '') {
            $params['next_timestamp_nano'] = $nextTimestampNano;
        }

        return $this->get($store, '/api/v2/sellerchat/get_conversation_list', $params);
    }

    public function getOneConversation(Store $store, string $conversationId): array
    {
        return $this->get($store, '/api/v2/sellerchat/get_one_conversation', [
            'conversation_id' => $conversationId,
        ]);
    }

    /**
     * Riwayat pesan sebuah percakapan (terbaru dulu, offset untuk paging).
     */
    public function getChatMessages(Store $store, string $conversationId, int $pageSize = 25, string $offset = ''): array
    {
        $params = [
            'conversation_id' => $conversationId,
            'page_size'       => $pageSize,
        ];
        if ($offset !== '') {
            $params['offset'] = $offset;
        }

        return $this->get($store, '/api/v2/sellerchat/get_message', $params);
    }

    /**
     * Kirim pesan teks ke buyer. $toId = buyer user id (to_id).
     */
    public function sendChatMessage(Store $store, $toId, string $text): array
    {
        return $this->post($store, '/api/v2/sellerchat/send_message', [
            'to_id'        => (int) $toId,
            'message_type' => 'text',
            'content'      => ['text' => $text],
        ]);
    }

    public function readConversation(Store $store, string $conversationId, string $lastReadMessageId): array
    {
        return $this->post($store, '/api/v2/sellerchat/read_conversation', [
            'conversation_id'      => $conversationId,
            'last_read_message_id' => $lastReadMessageId,
        ]);
    }

    public function getUnreadConversationCount(Store $store): array
    {
        return $this->get($store, '/api/v2/sellerchat/get_unread_conversation_count');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Product (v2.product.*)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Daftar item per status. item_status: NORMAL | BANNED | UNLIST | SELLER_DELETE
     * Catatan: API hanya menerima 1 status per panggilan yang aman lintas region,
     * jadi sync dilakukan per status.
     */
    public function getItemList(Store $store, string $itemStatus = 'NORMAL', int $offset = 0, int $pageSize = 100, ?int $updateTimeFrom = null, ?int $updateTimeTo = null): array
    {
        $params = [
            'offset'      => $offset,
            'page_size'   => $pageSize,
            'item_status' => $itemStatus,
        ];
        if ($updateTimeFrom) $params['update_time_from'] = $updateTimeFrom;
        if ($updateTimeTo)   $params['update_time_to']   = $updateTimeTo;

        return $this->get($store, '/api/v2/product/get_item_list', $params);
    }

    /**
     * Detail item (max 50 item_id per panggilan).
     */
    public function getItemBaseInfo(Store $store, array $itemIds): array
    {
        return $this->get($store, '/api/v2/product/get_item_base_info', [
            'item_id_list'          => implode(',', $itemIds),
            'need_tax_info'         => 'false',
            'need_complaint_policy' => 'false',
        ]);
    }

    /**
     * Statistik item: sales, views, likes, rating (max 50 item_id).
     */
    public function getItemExtraInfo(Store $store, array $itemIds): array
    {
        return $this->get($store, '/api/v2/product/get_item_extra_info', [
            'item_id_list' => implode(',', $itemIds),
        ]);
    }

    /**
     * Daftar model/varian sebuah item.
     */
    public function getProductModelList(Store $store, $itemId): array
    {
        return $this->get($store, '/api/v2/product/get_model_list', [
            'item_id' => (int) $itemId,
        ]);
    }

    /**
     * Update stok. $stockList: [['model_id' => 0, 'stock' => 10], ...]
     * model_id 0 untuk item tanpa varian.
     */
    public function updateProductStock(Store $store, $itemId, array $stockList): array
    {
        return $this->post($store, '/api/v2/product/update_stock', [
            'item_id'    => (int) $itemId,
            'stock_list' => array_map(fn ($s) => [
                'model_id'     => (int) ($s['model_id'] ?? 0),
                'seller_stock' => [['stock' => (int) $s['stock']]],
            ], $stockList),
        ]);
    }

    /**
     * Update harga. $priceList: [['model_id' => 0, 'original_price' => 125000], ...]
     */
    public function updateProductPrice(Store $store, $itemId, array $priceList): array
    {
        return $this->post($store, '/api/v2/product/update_price', [
            'item_id'    => (int) $itemId,
            'price_list' => array_map(fn ($p) => [
                'model_id'       => (int) ($p['model_id'] ?? 0),
                'original_price' => (float) $p['original_price'],
            ], $priceList),
        ]);
    }

    /**
     * Unlist / list kembali item. $items: [['item_id' => x, 'unlist' => true], ...]
     */
    public function unlistItems(Store $store, array $items): array
    {
        return $this->post($store, '/api/v2/product/unlist_item', [
            'item_list' => array_map(fn ($i) => [
                'item_id' => (int) $i['item_id'],
                'unlist'  => (bool) $i['unlist'],
            ], $items),
        ]);
    }

    /**
     * Pohon kategori Shopee.
     */
    public function getProductCategory(Store $store, string $language = 'id'): array
    {
        return $this->get($store, '/api/v2/product/get_category', [
            'language' => $language,
        ]);
    }

    // ─── Boost / Naikkan Produk ───────────────────────────────────────────────

    /**
     * Naikkan (boost) produk ke urutan teratas toko.
     * Endpoint: POST /api/v2/product/boost_item
     * Batas Shopee: maksimal 5 item per panggilan, durasi boost 4 jam,
     * item baru bisa di-boost lagi setelah 4 jam.
     *
     * @param  array<int|string>  $itemIds  daftar item_id (maks 5)
     */
    public function boostItems(Store $store, array $itemIds): array
    {
        return $this->post($store, '/api/v2/product/boost_item', [
            'item_id_list' => array_values(array_map('intval', $itemIds)),
        ]);
    }

    /**
     * Daftar produk yang sedang di-boost (maks 5).
     * Endpoint: GET /api/v2/product/get_boosted_list
     * Response: response.item_list = [item_id, ...]
     */
    public function getBoostedList(Store $store): array
    {
        return $this->get($store, '/api/v2/product/get_boosted_list', []);
    }
}
