<?php

namespace App\Services\TikTokShop;

use App\Jobs\SyncMarketplaceReturns;
use App\Models\Channel;
use App\Models\Store;
use GuzzleHttp\Handler\StreamHandler;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TikTokShopOAuthService
{
    public function process(string $authCode, ?int $targetStoreId = null, bool $readOnlyIntegration = false): void
    {
        try {
            $appKey = trim((string) config('tiktok_shop.app_key'));
            $appSecret = trim((string) config('tiktok_shop.app_secret'));
            $authUrl = rtrim(trim((string) config('tiktok_shop.auth_url', 'https://auth.tiktok-shops.com')), '/');

            if (! $appKey || ! $appSecret) {
                throw new \RuntimeException('TIKTOK_SHOP_APP_KEY dan TIKTOK_SHOP_APP_SECRET belum lengkap di .env.');
            }

            // This service runs in the queue CLI process, not the forked FPM
            // worker that handles the browser callback.
            $tokenResponse = $this->tiktokHttp()->connectTimeout(5)->timeout(10)->get($authUrl . '/api/v2/token/get', [
                'app_key'    => $appKey,
                'app_secret' => $appSecret,
                'auth_code'  => $authCode,
                'grant_type' => 'authorized_code',
            ]);

            $tokenData = $tokenResponse->json() ?? [];
            if (! $tokenResponse->successful() || (isset($tokenData['code']) && (int) $tokenData['code'] !== 0)) {
                Log::warning('TikTok token exchange failed', [
                    'http_status' => $tokenResponse->status(),
                    'code' => $tokenData['code'] ?? null,
                    'request_id' => $tokenData['request_id'] ?? null,
                    'message' => $tokenData['message'] ?? null,
                ]);
                return;
            }

            $data = $tokenData['data'] ?? $tokenData;
            $accessToken = $data['access_token'] ?? null;
            $refreshToken = $data['refresh_token'] ?? null;
            $openId = $data['open_id'] ?? null;
            $userType = array_key_exists('user_type', $data) ? (int) $data['user_type'] : null;

            if (! $accessToken) {
                Log::warning('TikTok token exchange returned no access token', [
                    'request_id' => $tokenData['request_id'] ?? null,
                    'message' => $tokenData['message'] ?? null,
                ]);
                return;
            }

            if ($userType !== null && $userType !== 0) {
                Log::warning('TikTok authorization returned a non-seller token', [
                    'user_type' => $userType,
                    'request_id' => $tokenData['request_id'] ?? null,
                ]);
                return;
            }

            $shops = array_values(array_filter(
                $this->fetchAuthorizedShops($appKey, $appSecret, $accessToken),
                static fn (array $shop): bool => filled($shop['shop_id'] ?? null) && filled($shop['shop_cipher'] ?? null)
            ));

            if (empty($shops)) {
                Log::warning('TikTok authorization returned no shops');
                return;
            }

            $accessTokenExpiresIn = $data['access_token_expire_in'] ?? $data['expires_in'] ?? null;
            $refreshTokenExpiresIn = $data['refresh_token_expire_in'] ?? $data['refresh_expires_in'] ?? null;
            $grantedScopes = array_values((array) ($data['granted_scopes'] ?? $data['scopes'] ?? []));
            $channel = Channel::firstOrCreate(
                ['code' => 'tiktok'],
                ['name' => 'TikTok Shop', 'status' => 'active']
            );

            $targetStore = $targetStoreId ? Store::find((int) $targetStoreId) : null;

            foreach ($shops as $shop) {
                $shopId = (string) ($shop['shop_id'] ?? '');
                $shopCipher = (string) ($shop['shop_cipher'] ?? '');
                if (! $shopId || ! $shopCipher) {
                    continue;
                }

                $storePayload = [
                    'name' => (string) ($shop['shop_name'] ?? 'TikTok Shop'),
                    'channel_id' => $channel->id,
                    'external_shop_id' => $shopId,
                    'region' => (string) ($shop['region'] ?? 'ID'),
                    'status' => 'active',
                    'is_active' => true,
                    'credentials' => [
                        'app_key' => $appKey,
                        'app_secret' => $appSecret,
                        'shop_id' => $shopId,
                        'shop_cipher' => $shopCipher,
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken,
                        'access_token_expire_in' => $accessTokenExpiresIn,
                        'refresh_token_expire_in' => $refreshTokenExpiresIn,
                        ...($openId !== null ? ['open_id' => $openId] : []),
                        ...($userType !== null ? ['user_type' => $userType] : []),
                    ],
                    'token_expires_at' => $accessTokenExpiresIn !== null
                        ? $this->tokenExpiresAt($accessTokenExpiresIn)
                        : null,
                    'meta' => [
                        'auth_source' => 'tiktok_oauth',
                        'shop_cipher' => $shopCipher,
                        'granted_scopes' => $grantedScopes,
                        'open_id' => $openId,
                        'user_type' => $userType,
                    ],
                ];

                if ($readOnlyIntegration) {
                    $storePayload['meta'] = array_merge($targetStore?->meta ?? [], $storePayload['meta'], [
                        'api_access_mode' => 'read_only',
                        'api_scopes' => config('marketplace.read_only_api_scopes', ['ads.read', 'shop.read']),
                        'api_auth_source' => 'official_oauth',
                        'api_connected_at' => now()->toISOString(),
                        'api_revoked_at' => null,
                        'api_revoked_by' => null,
                    ]);
                }

                if ($targetStore) {
                    $targetStore->update($storePayload);
                    $storeModel = $targetStore;
                    $targetStore = null;
                } else {
                    $storeModel = Store::updateOrCreate(
                        ['code' => 'tiktok_' . $shopCipher],
                        $storePayload
                    );
                }

                Artisan::queue('marketplace:sync-orders', ['--store' => $storeModel->id]);
                SyncMarketplaceReturns::dispatch($storeModel, null, null, true);
            }

            Log::info('TikTok Shop OAuth processed successfully', [
                'shop_count' => count($shops),
            ]);

        } catch (\Throwable $e) {
            Log::error('TikTok Shop OAuth job failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

        }
    }

    protected function tiktokHttp()
    {
        return app()->environment('testing')
            ? Http::withOptions([])
            : Http::withOptions(['handler' => new StreamHandler()]);
    }

    protected function fetchAuthorizedShops(string $appKey, string $appSecret, string $accessToken): array
    {
        // Get Active Shops returns the currently active shop IDs and regions.
        // It does not reliably include shop_cipher, which is required by most
        // order/product APIs. Complete the result with the authorization map.
        $activeShops = $this->fetchShopList(
            $appKey,
            $appSecret,
            $accessToken,
            '/seller/202309/shops',
            'TikTok Get Active Shops'
        );

        $authorizedShops = [];
        foreach ($activeShops as $shop) {
            if (filled($shop['shop_cipher'] ?? null)) {
                continue;
            }

            $authorizedShops = $this->fetchShopList(
                $appKey,
                $appSecret,
                $accessToken,
                '/authorization/202309/shops',
                'TikTok Get Authorized Shops'
            );
            break;
        }

        $merged = [];
        foreach ($activeShops as $shop) {
            $shopId = (string) ($shop['shop_id'] ?? '');
            if ($shopId !== '') {
                $merged[$shopId] = $shop;
            }
        }

        foreach ($authorizedShops as $shop) {
            $shopId = (string) ($shop['shop_id'] ?? '');
            if ($shopId === '') {
                continue;
            }

            $merged[$shopId] = array_merge($merged[$shopId] ?? [], array_filter([
                'shop_id' => $shopId,
                'shop_cipher' => $shop['shop_cipher'] ?? null,
                'shop_name' => $shop['shop_name'] ?? null,
                'region' => $shop['region'] ?? null,
            ], static fn ($value): bool => filled($value)));
        }

        return array_values($merged);
    }

    protected function fetchShopList(
        string $appKey,
        string $appSecret,
        string $accessToken,
        string $path,
        string $label
    ): array {
        try {
            $baseUrl = rtrim(trim((string) config('tiktok_shop.base_url', 'https://open-api.tiktokglobalshop.com')), '/');
            $query = ['app_key' => $appKey, 'timestamp' => (string) time()];
            $query['sign'] = $this->sign($appSecret, $path, $query);

            $response = $this->tiktokHttp()->connectTimeout(5)->timeout(10)
                ->retry(1, 500, throw: false)
                ->withHeaders([
                    'x-tts-access-token' => $accessToken,
                    'content-type' => 'application/json',
                ])
                ->get($baseUrl . $path, $query);

            $json = $response->json() ?? [];
            if (! $response->successful() || (isset($json['code']) && (int) $json['code'] !== 0)) {
                Log::warning($label . ' failed', [
                    'path' => $path,
                    'http_status' => $response->status(),
                    'code' => $json['code'] ?? null,
                    'request_id' => $json['request_id'] ?? null,
                    'message' => $json['message'] ?? null,
                ]);
                return [];
            }

            $data = $json['data'] ?? [];
            $shops = $data['shops'] ?? $data['shop_list'] ?? $json['shops'] ?? $json['shop_list'] ?? [];

            return array_map(static fn (array $shop): array => [
                'shop_id' => (string) ($shop['shop_id'] ?? $shop['id'] ?? ''),
                'shop_cipher' => (string) ($shop['cipher'] ?? $shop['shop_cipher'] ?? ''),
                'shop_name' => (string) ($shop['shop_name'] ?? $shop['name'] ?? 'TikTok Shop'),
                'region' => (string) ($shop['region'] ?? $shop['shop_region'] ?? 'ID'),
            ], array_values(array_filter($shops, static fn ($shop): bool => is_array($shop))));
        } catch (\Throwable $e) {
            Log::warning($label . ' exception', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    protected function sign(string $appSecret, string $path, array $params, string $body = ''): string
    {
        unset($params['sign'], $params['access_token']);
        ksort($params);

        $paramStr = '';
        foreach ($params as $key => $value) {
            $paramStr .= $key . $value;
        }

        return hash_hmac('sha256', $appSecret . $path . $paramStr . $body . $appSecret, $appSecret);
    }

    protected function tokenExpiresAt(mixed $value): ?\Illuminate\Support\Carbon
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            return null;
        }

        $value = (int) $value;

        // TikTok's *_expire_in fields are Unix timestamps for seller tokens.
        // Keep support for duration-style values for compatibility with older
        // responses and test fixtures.
        return $value >= 1_000_000_000
            ? now()->setTimestamp($value)
            : now()->addSeconds($value);
    }
}
