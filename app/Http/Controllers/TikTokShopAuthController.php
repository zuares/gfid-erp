<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokShopAuthController extends Controller
{
    // ─── Step 1: Redirect ke TikTok authorization page ───────────────────────

    public function redirect()
    {
        $appKey = trim((string) config('tiktok_shop.app_key'));
        $authUrl = rtrim(trim((string) config('tiktok_shop.auth_url', 'https://auth.tiktok-shops.com')), '/');

        if (! $appKey) {
            return response('TIKTOK_SHOP_APP_KEY belum diisi di .env', 422);
        }

        $callbackUrl = rtrim(env('APP_URL', request()->getSchemeAndHttpHost()), '/')
            . '/marketplace/tiktok/callback';

        $url = $authUrl . '/oauth/authorize?' . http_build_query([
            'app_key'      => $appKey,
            'redirect_uri' => $callbackUrl,
        ]);

        return redirect()->away($url);
    }

    // ─── Step 2: TikTok redirect kembali dengan auth_code ────────────────────

    public function callback(Request $request)
    {
        $authCode = $request->query('code') ?? $request->query('auth_code');
        $state    = $request->query('state');

        if (! $authCode) {
            return response()->json([
                'error' => 'TikTok Shop callback gagal. code tidak ditemukan.',
                'raw'   => $request->query(),
            ], 422);
        }

        $appKey    = trim((string) config('tiktok_shop.app_key'));
        $appSecret = trim((string) config('tiktok_shop.app_secret'));
        $authUrl   = rtrim(trim((string) config('tiktok_shop.auth_url', 'https://auth.tiktok-shops.com')), '/');

        // ─── Tukar auth_code → access_token ──────────────────────────────────
        $tokenResponse = Http::timeout(30)->post($authUrl . '/api/v2/token/get', [
            'app_key'    => $appKey,
            'app_secret' => $appSecret,
            'auth_code'  => $authCode,
            'grant_type' => 'authorized_code',
        ]);

        $tokenData = $tokenResponse->json() ?? [];

        if (! empty($tokenData['code']) && $tokenData['code'] !== 0) {
            return response()->json([
                'error'          => 'Gagal tukar code ke token TikTok Shop.',
                'token_response' => $tokenData,
            ], 422);
        }

        $data         = $tokenData['data'] ?? $tokenData;
        $accessToken  = $data['access_token']  ?? null;
        $refreshToken = $data['refresh_token'] ?? null;

        if (! $accessToken) {
            return response()->json([
                'error'          => 'access_token tidak ditemukan di response TikTok.',
                'token_response' => $tokenData,
            ], 422);
        }

        // ─── Ambil daftar shops yang diauthorize ──────────────────────────────
        $shops = $this->fetchAuthorizedShops($appKey, $appSecret, $accessToken);

        if (empty($shops)) {
            // Fallback: simpan satu store tanpa shop detail (shop_id dari token data)
            $shops = [[
                'shop_id'     => $data['seller_base_region'] ?? null, // best-effort fallback
                'shop_cipher' => $data['seller_base_region'] ?? null,
                'shop_name'   => 'TikTok Shop',
                'region'      => $data['seller_base_region'] ?? 'ID',
            ]];
        }

        $channel = Channel::firstOrCreate(
            ['code' => 'tiktok'],
            ['name' => 'TikTok Shop', 'status' => 'active']
        );

        // ─── Simpan satu Store per shop ───────────────────────────────────────
        foreach ($shops as $shop) {
            $shopId     = (string) ($shop['shop_id']     ?? '');
            $shopCipher = (string) ($shop['shop_cipher'] ?? '');
            $shopName   = (string) ($shop['shop_name']   ?? 'TikTok Shop');
            $region     = (string) ($shop['region']      ?? 'ID');

            if (! $shopId) {
                continue;
            }

            $credentials = [
                'app_key'                  => $appKey,
                'app_secret'               => $appSecret,
                'shop_id'                  => $shopId,
                'shop_cipher'              => $shopCipher,
                'access_token'             => $accessToken,
                'refresh_token'            => $refreshToken,
                'access_token_expire_in'   => $data['access_token_expire_in']  ?? null,
                'refresh_token_expire_in'  => $data['refresh_token_expire_in'] ?? null,
            ];

            Store::updateOrCreate(
                ['code' => 'tiktok_' . $shopId],
                [
                    'channel_id'       => $channel->id,
                    'external_shop_id' => $shopId,
                    'name'             => $shopName,
                    'region'           => $region,
                    'status'           => 'active',
                    'is_active'        => true,
                    'credentials'      => $credentials,
                    'token_expires_at' => isset($data['access_token_expire_in'])
                        ? now()->addSeconds((int) $data['access_token_expire_in'])
                        : null,
                    'meta' => [
                        'auth_source'        => 'tiktok_oauth',
                        'shop_cipher'        => $shopCipher,
                        'raw_token_response' => $tokenData,
                    ],
                ]
            );
        }

        return redirect('/marketplace/toko?connected=1');
    }

    // ─── Helper: GET /authorization/202309/shops ──────────────────────────────

    protected function fetchAuthorizedShops(string $appKey, string $appSecret, string $accessToken): array
    {
        try {
            $baseUrl   = rtrim(trim((string) config('tiktok_shop.base_url', 'https://open-api.tiktok-shops.com')), '/');
            $path      = '/authorization/202309/shops';
            $timestamp = (string) time();
            $sign      = $this->sign($appSecret, $path, [], $timestamp);

            $response = Http::timeout(30)
                ->withHeaders([
                    'x-tts-access-token' => $accessToken,
                    'content-type'       => 'application/json',
                ])
                ->get($baseUrl . $path, [
                    'app_key'   => $appKey,
                    'timestamp' => $timestamp,
                    'sign'      => $sign,
                    'version'   => '202309',
                ]);

            $json  = $response->json() ?? [];
            $shops = $json['data']['shops'] ?? $json['shops'] ?? [];

            return array_map(fn ($s) => [
                'shop_id'     => (string) ($s['shop_id']     ?? ''),
                'shop_cipher' => (string) ($s['cipher']      ?? $s['shop_cipher'] ?? ''),
                'shop_name'   => (string) ($s['shop_name']   ?? 'TikTok Shop'),
                'region'      => (string) ($s['region']      ?? 'ID'),
            ], $shops);
        } catch (\Throwable $e) {
            Log::warning('TikTok fetchAuthorizedShops failed: ' . $e->getMessage());
            return [];
        }
    }

    // ─── TikTok Shop signature (sorted params, HMAC-SHA256) ──────────────────

    protected function sign(string $appSecret, string $path, array $params, string $timestamp): string
    {
        // Remove sign, timestamp, app_key from params before signing
        unset($params['sign'], $params['timestamp'], $params['app_key']);

        ksort($params);

        $paramStr = '';
        foreach ($params as $k => $v) {
            $paramStr .= $k . $v;
        }

        $toSign = $appSecret . $path . $paramStr . $timestamp . $appSecret;

        return hash_hmac('sha256', $toSign, $appSecret);
    }
}
