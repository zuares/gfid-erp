<?php

namespace App\Services\Shopee;

use App\Jobs\SyncMarketplaceReturns;
use App\Models\Channel;
use App\Models\Store;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopeeOAuthService
{
    public function process(
        string $code,
        int $shopId,
        ?int $targetStoreId = null,
        bool $readOnlyIntegration = false,
    ): void {
        $partnerId = trim((string) config('shopee.partner_id'));
        $partnerKey = trim((string) config('shopee.partner_key'));
        $baseUrl = rtrim(trim((string) config('shopee.base_url', 'https://partner.shopeemobile.com')), '/');

        $path = '/api/v2/auth/token/get';
        $timestamp = time();
        $sign = hash_hmac('sha256', $partnerId . $path . $timestamp, $partnerKey);

        try {
            $response = Http::timeout(30)->post($baseUrl . $path . '?' . http_build_query([
                'partner_id' => (int) $partnerId,
                'timestamp' => $timestamp,
                'sign' => $sign,
            ]), [
                'code' => $code,
                'shop_id' => $shopId,
                'partner_id' => (int) $partnerId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ShopeeAuth] Token exchange failed', ['message' => $e->getMessage()]);
            return;
        }

        $token = $response->json() ?? [];
        if (! $response->successful() || ! empty($token['error'])) {
            Log::warning('[ShopeeAuth] Token exchange rejected', [
                'http_status' => $response->status(),
                'error' => $token['error'] ?? null,
                'message' => $token['message'] ?? null,
            ]);
            return;
        }

        $channel = Channel::firstOrCreate(
            ['code' => 'shopee'],
            ['name' => 'Shopee', 'status' => 'active']
        );

        $credentials = [
            'partner_id' => $partnerId,
            'partner_key' => $partnerKey,
            'shop_id' => (string) $shopId,
            'principal_id' => $token['principal_id']
                ?? data_get($token, 'principal_id_list.0')
                ?? config('shopee.principal_id'),
            'access_token' => $token['access_token'] ?? null,
            'refresh_token' => $token['refresh_token'] ?? null,
            'base_url' => $baseUrl,
        ];

        $storeModel = $targetStoreId ? Store::find($targetStoreId) : null;

        try {
            $meta = is_array($storeModel?->meta) ? $storeModel->meta : [];
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            $meta = [];
        }

        if ($readOnlyIntegration) {
            $meta = array_merge($meta, [
                'api_access_mode' => 'read_only',
                'api_scopes' => config('marketplace.read_only_api_scopes', ['ads.read', 'shop.read']),
                'api_auth_source' => 'official_oauth',
                'api_connected_at' => now()->toISOString(),
                'api_revoked_at' => null,
                'api_revoked_by' => null,
            ]);
        }

        $storeData = [
            'channel_id' => $channel->id,
            'external_shop_id' => (string) $shopId,
            'credentials' => $credentials,
            'status' => 'active',
            'is_active' => true,
            'token_expires_at' => now()->addSeconds(max(0, ($token['expire_in'] ?? 86400) - 300)),
            'meta' => $meta,
        ];

        try {
            if ($storeModel) {
                $storeModel->update($storeData);
            } else {
                $storeModel = Store::updateOrCreate(
                    ['code' => 'shopee_' . $shopId],
                    array_merge(['name' => 'Shopee ' . $shopId], $storeData)
                );
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            $updateData = array_merge($storeData, [
                'credentials' => encrypt($credentials),
                'updated_at' => now(),
            ]);

            if ($storeModel) {
                \Illuminate\Support\Facades\DB::table('stores')
                    ->where('id', $storeModel->id)
                    ->update($updateData);
                $storeModel->refresh();
            } else {
                \Illuminate\Support\Facades\DB::table('stores')
                    ->where('code', 'shopee_' . $shopId)
                    ->update($updateData);
                $storeModel = Store::where('external_shop_id', (string) $shopId)->first()
                    ?? Store::where('code', 'shopee_' . $shopId)->first();
            }
        } catch (\Throwable $e) {
            Log::error('[ShopeeAuth] Store credentials could not be saved', ['message' => $e->getMessage()]);
            return;
        }

        try {
            if ($storeModel) {
                $info = app(\App\Services\Channels\Shopee\ShopeeChannel::class)->getShopInfo($storeModel);
                $realName = $info['response']['shop_name'] ?? $info['shop_name'] ?? null;
                if ($realName) {
                    $storeModel->update(['name' => $realName]);
                }
            }
        } catch (\Throwable) {
            // Keep the fallback name when shop-info is temporarily unavailable.
        }

        if (! $storeModel) {
            return;
        }

        Artisan::queue('marketplace:sync-orders', ['--store' => $storeModel->id]);
        Artisan::queue('marketplace:sync-settlements', ['--store' => $storeModel->id]);
        SyncMarketplaceReturns::dispatch($storeModel, null, null, true);

        try {
            $queuedAdsSync = Artisan::queue('marketplace:sync-ads', [
                '--store' => $storeModel->id,
                '--backfill' => true,
                '--from' => now()->subDays(90)->toDateString(),
                '--to' => now()->toDateString(),
            ]);
            $queuedAdsSync->onQueue('ads');
        } catch (\Throwable $e) {
            Log::warning('[ShopeeAuth] Gagal antre backfill ads awal: ' . $e->getMessage());
        }
    }
}
