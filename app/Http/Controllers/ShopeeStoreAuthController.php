<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ShopeeStoreAuthController extends Controller
{
    public function redirect()
    {
        $partnerId = trim((string) config('shopee.partner_id'));
        $partnerKey = trim((string) config('shopee.partner_key'));
        $baseUrl = rtrim(trim((string) config('shopee.base_url', 'https://partner.shopeemobile.com')), '/');

        if (!$partnerId || !$partnerKey) {
            return response('SHOPEE_PARTNER_ID dan SHOPEE_PARTNER_KEY belum diisi di .env', 422);
        }

        $redirectUrl = rtrim(env('APP_URL', request()->getSchemeAndHttpHost()), '/') . '/owner/omnichannel/shopee/callback';

        $path = '/api/v2/shop/auth_partner';
        $timestamp = time();
        $sign = hash_hmac('sha256', $partnerId . $path . $timestamp, $partnerKey);

        $url = $baseUrl . $path . '?' . http_build_query([
            'partner_id' => (int) $partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'redirect' => $redirectUrl,
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        $shopId = $request->query('shop_id');

        if (!$code || !$shopId) {
            return response()->json([
                'error' => 'Shopee callback gagal. code atau shop_id tidak ditemukan.',
                'raw' => $request->query(),
            ], 422);
        }

        $partnerId = trim((string) config('shopee.partner_id'));
        $partnerKey = trim((string) config('shopee.partner_key'));
        $baseUrl = rtrim(trim((string) config('shopee.base_url', 'https://partner.shopeemobile.com')), '/');

        $path = '/api/v2/auth/token/get';
        $timestamp = time();
        $sign = hash_hmac('sha256', $partnerId . $path . $timestamp, $partnerKey);

        $url = $baseUrl . $path . '?' . http_build_query([
            'partner_id' => (int) $partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ]);

        $response = Http::timeout(30)->post($url, [
            'code' => $code,
            'shop_id' => (int) $shopId,
            'partner_id' => (int) $partnerId,
        ]);

        $token = $response->json() ?? [];

        if (!empty($token['error'])) {
            return response()->json([
                'error' => 'Gagal tukar code ke token Shopee.',
                'token_response' => $token,
            ], 422);
        }

        $channel = Channel::firstOrCreate(
            ['code' => 'shopee'],
            ['name' => 'Shopee', 'status' => 'active']
        );

        $credentials = [
            'partner_id' => $partnerId,
            'partner_key' => $partnerKey,
            'shop_id' => (string) $shopId,
            'access_token' => $token['access_token'] ?? null,
            'refresh_token' => $token['refresh_token'] ?? null,
            'base_url' => $baseUrl,
        ];

        Store::updateOrCreate(
            [
                'code' => 'shopee_' . $shopId,
            ],
            [
                'channel_id' => $channel->id,
                'external_shop_id' => (string) $shopId,
                'name' => 'Shopee Store ' . $shopId,
                'region' => 'ID',
                'status' => 'active',
                'is_active' => true,
                'credentials' => $credentials,
                'token_expires_at' => now()->addSeconds((int) ($token['expire_in'] ?? 0)),
                'meta' => [
                    'auth_source' => 'shopee_oauth',
                    'raw_token_response' => $token,
                ],
            ]
        );

        return redirect('/owner/omnichannel?connected=1');
    }
}
