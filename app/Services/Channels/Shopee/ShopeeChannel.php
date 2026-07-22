<?php

namespace App\Services\Channels\Shopee;

use App\Models\Store;
use App\Services\Channels\Contracts\MarketplaceChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $this->ensureFreshToken($store); // proaktif: refresh sebelum kedaluwarsa
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
        $this->ensureFreshToken($store); // proaktif: refresh sebelum kedaluwarsa
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

        return $this->withHttpMeta($response);
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

        return $this->withHttpMeta($response);
    }

    /**
     * Bungkus response HTTP mentah menjadi array payload Shopee, ditambah metadata
     * HTTP internal aplikasi di key `_meta` (http_status, retry_after).
     *
     * PENTING: `_meta` BUKAN bagian dari response asli Shopee — ini murni metadata
     * internal supaya lapisan retry (lihat MarketplaceSyncService::getEscrowDetailWithRetry())
     * bisa membaca kode status HTTP asli. Sebelumnya status HTTP hilang setiap kali
     * Shopee mengembalikan body JSON valid (kasus umum untuk error 429/5xx yang tetap
     * berbentuk JSON) — hanya tersimpan saat body BUKAN JSON valid.
     *
     * Perubahan ini aditif murni: field 'error'/'message'/'response'/dll milik Shopee
     * tidak diubah/dihapus, hanya ditambah satu key baru di root. Sudah diaudit: tidak
     * ada caller lain di project yang mengiterasi seluruh root response atau menyimpan
     * root response mentah-mentah ke kolom raw_json (semua pemanggil mengambil sub-key
     * spesifik seperti 'response'/'order_income'). Caller yang menyimpan raw_json HARUS
     * memisahkan `_meta` dulu sebelum menyimpan (lihat mapEscrowSettlement()) supaya
     * raw_json tetap murni response asli Shopee.
     */
    protected function withHttpMeta(\Illuminate\Http\Client\Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            $payload = [
                'error'   => 'invalid_response',
                'message' => $response->body(),
                'status'  => $response->status(), // dipertahankan untuk backward-compat
            ];
        }

        $payload['_meta'] = array_merge(
            is_array($payload['_meta'] ?? null) ? $payload['_meta'] : [],
            [
                'http_status' => $response->status(),
                'retry_after' => $response->header('Retry-After'),
            ]
        );

        return $payload;
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

    /**
     * Refresh proaktif: kalau token tinggal ≤2 menit lagi (atau tidak diketahui
     * kapan kedaluwarsa), segarkan dulu sebelum memanggil API. Mencegah error
     * "Invalid access_token" yang muncul saat token pas kedaluwarsa.
     */
    protected function ensureFreshToken(Store $store): void
    {
        if (! $store->is_active) {
            return; // toko nonaktif: jangan ambil/refresh token
        }
        if ($store->token_expires_at && $store->token_expires_at->isAfter(now()->addMinutes(2))) {
            return; // masih segar
        }
        if (blank($store->credential('refresh_token'))) {
            return; // tak ada refresh_token — biarkan API menjawab dengan error jelas
        }
        $this->refreshToken($store);
    }

    /**
     * Tukar refresh_token → access_token baru.
     *
     * PENTING: refresh_token Shopee sekali-pakai dan BEROTASI. Kalau beberapa
     * request menabrak bersamaan saat token kedaluwarsa (mis. halaman chat yang
     * polling + sync + kirim sekaligus), refresh paralel akan saling membatalkan
     * dan menghasilkan "Invalid access_token". Karena itu refresh dikunci: hanya
     * SATU proses yang benar-benar menukar token; sisanya menunggu lalu memakai
     * token baru yang sudah tersimpan.
     */
    public function refreshToken(Store $store): array
    {
        if (! $store->is_active) {
            // Toko nonaktif: jangan hubungi API auth Shopee sama sekali.
            return ['error' => 'store_inactive', 'message' => 'Toko nonaktif, refresh token dilewati'];
        }

        if (blank($store->credential('refresh_token')) || blank($this->shopId($store))) {
            // Toko belum terkonfigurasi (record placeholder tanpa shop_id/refresh_token).
            // Memanggil API auth dengan kredensial kosong hanya menghasilkan warning
            // "refresh token or shop_id is wrong" yang membanjiri log. Lewati saja.
            return ['error' => 'not_configured', 'message' => 'Kredensial toko belum lengkap, refresh dilewati'];
        }

        $lock    = Cache::lock("shopee:refresh:{$store->id}", 35); // > HTTP timeout (30s)
        $gotLock = false;
        try { $gotLock = $lock->block(15); } catch (\Throwable $e) { $gotLock = false; }

        try {
            // Proses lain mungkin sudah menyegarkan token selagi kita menunggu.
            $store->refresh();
            if ($store->token_expires_at
                && $store->token_expires_at->isAfter(now()->addMinutes(2))
                && filled($store->credential('access_token'))) {
                return ['access_token' => $store->credential('access_token'), 'reused' => true];
            }

            $path      = '/api/v2/auth/access_token/get';
            $timestamp = time();

            $url = $this->baseUrl($store) . $path . '?' . http_build_query([
                'partner_id' => (int) $this->partnerId($store),
                'timestamp'  => $timestamp,
                'sign'       => $this->sign($store, $path, $timestamp, false),
            ]);

            $response = Http::timeout(30)->post($url, [
                'refresh_token' => $store->credential('refresh_token'),
                'partner_id'    => (int) $this->partnerId($store),
                'shop_id'       => (int) $this->shopId($store),
            ]);

            $data = $response->json() ?? [];

            if (empty($data['error']) && !empty($data['access_token'])) {
                $credentials = $store->credentials ?? [];
                $credentials['access_token']  = $data['access_token'];
                $credentials['refresh_token'] = $data['refresh_token'] ?? $credentials['refresh_token'] ?? null;

                $store->update([
                    'credentials'      => $credentials,
                    'token_expires_at' => now()->addSeconds((int) ($data['expire_in'] ?? 0)),
                ]);
            } else {
                Log::warning("[shopee] refresh token store #{$store->id} gagal: "
                    . ($data['message'] ?? $data['error'] ?? 'unknown'));
            }

            return $data;
        } finally {
            if ($gotLock) { optional($lock)->release(); }
        }
    }

    // ─── Logistics / Fulfillment ──────────────────────────────────────────────

    public function getShippingParameter(Store $store, string $orderSn): array
    {
        return $this->get($store, '/api/v2/logistics/get_shipping_parameter', [
            'order_sn' => $orderSn
        ]);
    }

    public function getBookingDetail(Store $store, string $bookingSn): array
    {
        // Shopee get_booking_detail memakai booking_sn_list (bukan order_sn).
        return $this->get($store, '/api/v2/order/get_booking_detail', [
            'booking_sn_list' => $bookingSn,
            'response_optional_fields' => 'item_list,cancel_by,cancel_reason,fulfillment_flag,pickup_done_time,shipping_carrier,recipient_address,dropshipper,dropshipper_phone',
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

    // ─── Booking / Pesanan Kilat: pengiriman ──────────────────────────────────

    /** Parameter pengiriman untuk booking (pickup/dropoff yang tersedia). */
    public function getBookingShippingParameter(Store $store, string $bookingSn): array
    {
        return $this->get($store, '/api/v2/logistics/get_booking_shipping_parameter', [
            'booking_sn' => $bookingSn,
        ]);
    }

    /** Atur pengiriman booking. $params berisi salah satu dari pickup / dropoff. */
    public function shipBooking(Store $store, string $bookingSn, array $params = []): array
    {
        $body = array_merge(['booking_sn' => $bookingSn], $params);
        return $this->post($store, '/api/v2/logistics/ship_booking', $body);
    }

    /** Ambil nomor resi (tracking number) sebuah booking. */
    public function getBookingTrackingNumber(Store $store, string $bookingSn): array
    {
        return $this->get($store, '/api/v2/logistics/get_booking_tracking_number', [
            'booking_sn' => $bookingSn,
        ]);
    }

    /** Timeline pelacakan sebuah booking (get_booking_tracking_info, pakai booking_sn). */
    public function getBookingTrackingInfo(Store $store, string $bookingSn): array
    {
        return $this->get($store, '/api/v2/logistics/get_booking_tracking_info', [
            'booking_sn' => $bookingSn,
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

    /** Timeline pelacakan pengiriman (get_tracking_info). */
    public function getTrackingInfo(Store $store, string $orderSn, ?string $packageNumber = null): array
    {
        $params = ['order_sn' => $orderSn];
        if ($packageNumber) {
            $params['package_number'] = $packageNumber;
        }
        return $this->get($store, '/api/v2/logistics/get_tracking_info', $params);
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

    // ─── Booking / Pesanan Kilat: cetak dokumen resi ─────────────────────────

    /**
     * Ambil parameter dokumen resi booking (tipe dokumen yang tersedia).
     * Shopee endpoint: /api/v2/logistics/get_booking_shipping_document_parameter
     */
    public function getBookingShippingDocumentParameter(Store $store, array $bookingList): array
    {
        $body = [
            'booking_list' => array_map(function ($item) {
                return is_array($item) ? $item : ['booking_sn' => $item];
            }, $bookingList),
        ];
        return $this->post($store, '/api/v2/logistics/get_booking_shipping_document_parameter', $body);
    }

    /**
     * Minta Shopee membuat dokumen resi booking.
     * Shopee endpoint: /api/v2/logistics/create_booking_shipping_document
     *
     * PENTING: booking_list harus berisi booking_sn + tracking_number.
     */
    public function createBookingShippingDocument(Store $store, array $bookingList, ?string $docType = null): array
    {
        if (!$docType) {
            $defaultFormat = \App\Models\SystemSetting::get('marketplace_print_default_format', 'THERMAL_AIR_WAYBILL');
            $docType = $store->meta['shipping_document_type'] ?? $defaultFormat;
        }
        $body = [
            'booking_list' => $bookingList,
            'shipping_document_type' => $docType,
        ];
        return $this->post($store, '/api/v2/logistics/create_booking_shipping_document', $body);
    }

    /**
     * Download dokumen resi booking (PDF).
     * Shopee endpoint: /api/v2/logistics/download_booking_shipping_document
     *
     * Alur: create → poll result → download.
     * Method ini menggabungkan poll + download.
     */
    public function downloadBookingShippingDocument(Store $store, array $bookingList, ?string $docType = null): mixed
    {
        if (!$docType) {
            $defaultFormat = \App\Models\SystemSetting::get('marketplace_print_default_format', 'THERMAL_AIR_WAYBILL');
            $docType = $store->meta['shipping_document_type'] ?? $defaultFormat;
        }
        $body = [
            'booking_list' => $bookingList,
            'shipping_document_type' => $docType,
        ];

        // Poll get_booking_shipping_document_result up to 5 times
        $allReady = false;
        for ($i = 0; $i < 5; $i++) {
            $result = $this->post($store, '/api/v2/logistics/get_booking_shipping_document_result', $body);

            $allReady = true;
            if (isset($result['response']['result_list']) && is_array($result['response']['result_list'])) {
                foreach ($result['response']['result_list'] as $resItem) {
                    if (isset($resItem['status']) && $resItem['status'] !== 'READY') {
                        $allReady = false;
                        break;
                    }
                }
            } else {
                $allReady = false;
            }

            if ($allReady) {
                break;
            }

            sleep(1);
        }

        if (!$allReady) {
            return [
                'error' => 'timeout',
                'message' => 'Dokumen resi booking sedang diproses oleh Shopee dan belum siap. Silakan coba cetak lagi beberapa saat lagi.',
            ];
        }

        // Download returns raw PDF bytes, not JSON
        $timestamp = time();
        $path = '/api/v2/logistics/download_booking_shipping_document';
        $query = [
            'partner_id'   => (int) $this->partnerId($store),
            'timestamp'    => $timestamp,
            'access_token' => $this->accessToken($store),
            'shop_id'      => (int) $this->shopId($store),
            'sign'         => $this->sign($store, $path, $timestamp),
        ];

        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->post($this->baseUrl($store) . $path . '?' . http_build_query($query), $body);

        // If response is JSON, it's an error
        $contentType = $response->header('Content-Type');
        if (str_contains($contentType ?? '', 'application/json')) {
            return $response->json() ?? [
                'error'   => 'download_failed',
                'message' => 'Gagal download dokumen resi booking.',
            ];
        }

        // Return raw PDF bytes
        return $response->body();
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
     * Endpoint: POST /api/v2/product/update_item_base_info
     */
    public function updateItemBaseInfo(Store $store, int $itemId, array $data): array
    {
        return $this->post($store, '/api/v2/product/update_item_base_info', array_merge([
            'item_id' => $itemId,
        ], $data));
    }

    /**
     * Endpoint: POST /api/v2/product/update_model
     */
    public function updateModel(Store $store, int $itemId, array $models): array
    {
        return $this->post($store, '/api/v2/product/update_model', [
            'item_id' => $itemId,
            'model' => $models,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Discount APIs (Shopee v2.discount)
    // ─────────────────────────────────────────────────────────────────────────

    public function getDiscountList(Store $store, string $status = 'ongoing', int $offset = 0, int $limit = 100): array
    {
        return $this->get($store, '/api/v2/discount/get_discount_list', [
            'discount_status' => $status,
            'pagination_offset' => $offset,
            'pagination_entries_per_page' => $limit,
        ]);
    }

    public function addDiscount(Store $store, string $discountName, int $startTime, int $endTime): array
    {
        return $this->post($store, '/api/v2/discount/add_discount', [
            'discount_name' => $discountName,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    public function addDiscountItem(Store $store, int $discountId, array $itemList): array
    {
        return $this->post($store, '/api/v2/discount/add_discount_item', [
            'discount_id' => $discountId,
            'item_list' => $itemList,
        ]);
    }

    public function updateDiscountItem(Store $store, int $discountId, array $itemList): array
    {
        return $this->post($store, '/api/v2/discount/update_discount_item', [
            'discount_id' => $discountId,
            'item_list' => $itemList,
        ]);
    }

    public function endDiscount(Store $store, int $discountId): array
    {
        return $this->post($store, '/api/v2/discount/end_discount', [
            'discount_id' => $discountId,
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
