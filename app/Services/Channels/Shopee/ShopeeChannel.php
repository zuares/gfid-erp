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
        $timestamp = time();

        $query = array_merge([
            'partner_id' => (int) $this->partnerId($store),
            'timestamp' => $timestamp,
            'access_token' => $this->accessToken($store),
            'shop_id' => (int) $this->shopId($store),
            'sign' => $this->sign($store, $path, $timestamp),
        ], $params);

        $response = Http::timeout(30)->get($this->baseUrl($store) . $path, $query);

        return $response->json() ?? [
            'error' => 'invalid_response',
            'message' => $response->body(),
            'status' => $response->status(),
        ];
    }

    public function getShopInfo(Store $store): array
    {
        return $this->get($store, '/api/v2/shop/get_shop_info');
    }

    public function getOrders(Store $store, int $timeFrom, int $timeTo, int $pageSize = 20): array
    {
        return $this->get($store, '/api/v2/order/get_order_list', [
            'time_range_field' => 'create_time',
            'time_from' => $timeFrom,
            'time_to' => $timeTo,
            'page_size' => $pageSize,
        ]);
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
}
