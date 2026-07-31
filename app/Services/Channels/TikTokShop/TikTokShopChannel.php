<?php

namespace App\Services\Channels\TikTokShop;

use App\Models\Store;
use App\Services\Channels\Contracts\MarketplaceChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokShopChannel implements MarketplaceChannel
{
    // ─── Credential helpers ───────────────────────────────────────────────────

    protected function baseUrl(Store $store): string
    {
        return rtrim(config('tiktok_shop.base_url', 'https://open-api.tiktok-shops.com'), '/');
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

    // ─── Signature (TikTok Shop v2 style) ────────────────────────────────────
    // Format: HMAC-SHA256( appSecret + path + sortedParamStr + timestamp + appSecret )

    protected function sign(Store $store, string $path, array $params, string $timestamp): string
    {
        $appSecret = $this->appSecret($store);

        unset($params['sign'], $params['timestamp'], $params['app_key']);

        ksort($params);
        $paramStr = '';
        foreach ($params as $k => $v) {
            $paramStr .= $k . $v;
        }

        $toSign = $appSecret . $path . $paramStr . $timestamp . $appSecret;

        return hash_hmac('sha256', $toSign, $appSecret);
    }

    // ─── HTTP helpers ─────────────────────────────────────────────────────────

    protected function get(Store $store, string $path, array $params = []): array
    {
        $result = $this->doGet($store, $path, $params);

        // Token expired → auto-refresh sekali lalu retry
        $code = $result['code'] ?? null;
        if ($code === 105 || $code === 'invalid_access_token') {
            $refreshed = $this->refreshToken($store);
            if (empty($refreshed['error'])) {
                $store->refresh();
                $result = $this->doGet($store, $path, $params);
            }
        }

        return $result;
    }

    protected function doGet(Store $store, string $path, array $params = []): array
    {
        $timestamp = (string) time();

        $query = array_merge($params, [
            'app_key'      => $this->appKey($store),
            'shop_cipher'  => $this->shopCipher($store),
            'timestamp'    => $timestamp,
            'version'      => '202309',
        ]);

        $query['sign'] = $this->sign($store, $path, $query, $timestamp);

        $response = Http::timeout(30)
            ->withHeaders([
                'x-tts-access-token' => $this->accessToken($store),
                'content-type'       => 'application/json',
            ])
            ->get($this->baseUrl($store) . $path, $query);

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
        if (isset($json['code']) && $json['code'] !== 0) {
            $json['error'] = true;
        }

        return $json;
    }

    // ─── MarketplaceChannel interface ─────────────────────────────────────────

    public function getShopInfo(Store $store): array
    {
        return $this->get($store, '/seller/202309/shops');
    }

    // ─── Logistics / Fulfillment ──────────────────────────────────────────────

    public function getShippingParameter(Store $store, string $orderSn): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur Atur Pengiriman untuk TikTok Shop belum tersedia.'];
    }

    public function shipOrder(Store $store, string $orderSn, array $params = []): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur Atur Pengiriman untuk TikTok Shop belum tersedia.'];
    }

    public function createShippingDocument(Store $store, array $orderSnList): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur Cetak Resi untuk TikTok Shop belum tersedia.'];
    }

    public function getShippingDocument(Store $store, array $orderSnList): array
    {
        return ['error' => 'not_implemented', 'message' => 'Fitur Cetak Resi untuk TikTok Shop belum tersedia.'];
    }

    public function getOrders(Store $store, int $timeFrom, int $timeTo, int $pageSize = 20, string $cursor = '', string $orderStatus = '', string $timeRangeField = 'update_time'): array
    {
        // TikTok Shop: pakai create_time_* saat backfill histori, selain itu update_time_*.
        $prefix = $timeRangeField === 'create_time' ? 'create_time' : 'update_time';
        $params = [
            $prefix . '_ge' => $timeFrom,
            $prefix . '_lt' => $timeTo,
            'page_size'      => $pageSize,
        ];
        if ($cursor) {
            $params['cursor'] = $cursor;
        }
        return $this->get($store, '/order/202309/orders', $params);
    }

    public function getOrderDetail(Store $store, array $orderSnList): array
    {
        return $this->get($store, '/order/202309/orders', [
            'ids' => implode(',', $orderSnList),
        ]);
    }

    public function getEscrowDetail(Store $store, string $orderSn): array
    {
        // TikTok Shop belum support escrow detail via open API — return stub
        return ['code' => -1, 'message' => 'Not supported for TikTok Shop'];
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

        $response = Http::timeout(30)->post($authUrl . '/api/v2/token/refresh', [
            'app_key'       => $appKey,
            'app_secret'    => $appSecret,
            'refresh_token' => $store->credential('refresh_token'),
            'grant_type'    => 'refresh_token',
        ]);

        $data = $response->json() ?? [];
        $body = $data['data'] ?? $data;

        if (! empty($body['access_token'])) {
            $credentials = $store->credentials ?? [];
            $credentials['access_token']  = $body['access_token'];
            $credentials['refresh_token'] = $body['refresh_token'] ?? $credentials['refresh_token'] ?? null;
            $credentials['access_token_expire_in']  = $body['access_token_expire_in']  ?? null;
            $credentials['refresh_token_expire_in'] = $body['refresh_token_expire_in'] ?? null;

            $store->update([
                'credentials'      => $credentials,
                'token_expires_at' => isset($body['access_token_expire_in'])
                    ? now()->addSeconds((int) $body['access_token_expire_in'])
                    : null,
            ]);
        } else {
            Log::warning('TikTok token refresh failed', ['store' => $store->code, 'response' => $data]);
        }

        return $data;
    }
}
