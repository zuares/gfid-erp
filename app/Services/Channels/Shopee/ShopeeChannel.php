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

        // Token kedaluwarsa → auto-refresh sekali lalu retry
        if (isset($result['error']) && str_contains((string) ($result['error'] ?? ''), 'access_token')) {
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

        $error = $result['error'] ?? null;
        if ($error === 'error_auth' || str_contains(strtolower((string)($result['message'] ?? '')), 'expired')) {
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
        return $this->doGet($store, '/api/v2/logistics/get_shipping_parameter', [
            'order_sn' => $orderSn
        ]);
    }

    public function getBookingDetail(Store $store, string $orderSn): array
    {
        return $this->doGet($store, '/api/v2/order/get_booking_detail', [
            'order_sn' => $orderSn
        ]);
    }

    public function getBookingList(Store $store, int $timeFrom, int $timeTo, int $pageSize = 20, string $cursor = ''): array
    {
        return $this->doGet($store, '/api/v2/order/get_booking_list', [
            'time_range_field' => 'create_time',
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'page_size' => $pageSize,
            'cursor' => $cursor
        ]);
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
}
