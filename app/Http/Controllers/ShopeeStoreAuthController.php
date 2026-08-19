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

        if (request()->has('store_id')) {
            session(['shopee_connect_store_id' => request('store_id')]);
        }

        $redirectUrl = rtrim(env('APP_URL', request()->getSchemeAndHttpHost()), '/') . '/marketplace/shopee/callback';

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
            return redirect('/marketplace/toko')->with('error', 'Shopee callback gagal. code atau shop_id tidak ditemukan.');
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
            return redirect('/marketplace/toko')->with('error', 'Gagal tukar code ke token Shopee. ' . ($token['message'] ?? ''));
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

        $storeId = session()->pull('shopee_connect_store_id');
        $storeModel = null;
        if ($storeId) {
            $storeModel = Store::find($storeId);
        }

        try {
            if ($storeModel) {
                $storeModel->update([
                    'channel_id' => $channel->id,
                    'external_shop_id' => (string) $shopId,
                    'credentials' => $credentials,
                    'status' => 'active',
                    'is_active' => true,
                    'token_expires_at' => now()->addSeconds(max(0, ($token['expire_in'] ?? 86400) - 300)),
                ]);
            } else {
                $storeModel = Store::updateOrCreate(
                    ['code' => 'shopee_' . $shopId],
                    [
                        'name' => 'Shopee ' . $shopId,
                        'channel_id' => $channel->id,
                        'external_shop_id' => (string) $shopId,
                        'credentials' => $credentials,
                        'status' => 'active',
                        'is_active' => true,
                        'token_expires_at' => now()->addSeconds(max(0, ($token['expire_in'] ?? 86400) - 300)),
                    ]
                );
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Jika APP_KEY berubah, Eloquent tidak bisa mendeskripsi token lama.
            // Solusi: Kita timpa (force update) data credentials secara langsung menggunakan DB Query Builder (Bypass Eloquent Casts).
            $updateData = [
                'channel_id' => $channel->id,
                'external_shop_id' => (string) $shopId,
                'credentials' => encrypt($credentials),
                'status' => 'active',
                'is_active' => true,
                'token_expires_at' => now()->addSeconds(max(0, ($token['expire_in'] ?? 86400) - 300)),
                'updated_at' => now(),
            ];

            if ($storeModel) {
                \Illuminate\Support\Facades\DB::table('stores')->where('id', $storeModel->id)->update($updateData);
                $storeModel->refresh();
            } else {
                \Illuminate\Support\Facades\DB::table('stores')->where('code', 'shopee_' . $shopId)->update($updateData);
                $storeModel = Store::where('external_shop_id', (string) $shopId)->first()
                    ?? Store::where('code', 'shopee_' . $shopId)->first();
            }
        } catch (\Throwable $e) {
            return redirect('/marketplace/toko')->with('error', 'Terjadi kesalahan sistem saat menyimpan otentikasi toko: ' . $e->getMessage());
        }

        // Try to fetch real shop name from Shopee API
        try {
            if ($storeModel) {
                /** @var \App\Services\Channels\Shopee\ShopeeChannel $shopee */
                $shopee   = app(\App\Services\Channels\Shopee\ShopeeChannel::class);
                $info     = $shopee->getShopInfo($storeModel);
                $realName = $info['response']['shop_name']
                    ?? $info['shop_name']
                    ?? null;
                if ($realName) {
                    $storeModel->update(['name' => $realName]);
                }
            }
        } catch (\Throwable $e) {
            // silent — name stays as fallback
        }

        // Jalankan sinkronisasi awal secara otomatis di background
        if (isset($storeModel) && $storeModel) {
            \Illuminate\Support\Facades\Artisan::queue('marketplace:sync-orders', ['--store' => $storeModel->id]);
            \Illuminate\Support\Facades\Artisan::queue('marketplace:sync-settlements', ['--store' => $storeModel->id]);
            \App\Jobs\SyncMarketplaceReturns::dispatch($storeModel, null, null, true);

            // Ads: antrekan backfill awal 90 hari lewat chain queue (chunk 30
            // hari, dedupe + progress bar ditangani command). Jangan jalankan
            // command ini inline karena callback OAuth harus segera merespons;
            // backfill dapat memakan waktu lebih lama dari timeout nginx/ngrok.
            try {
                $queuedAdsSync = \Illuminate\Support\Facades\Artisan::queue('marketplace:sync-ads', [
                    '--store'    => $storeModel->id,
                    '--backfill' => true,
                    '--from'     => now()->subDays(90)->toDateString(),
                    '--to'       => now()->toDateString(),
                ]);
                $queuedAdsSync->onQueue('ads');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[ShopeeAuth] Gagal antre backfill ads awal: ' . $e->getMessage());
            }
        }

        return redirect('/marketplace/toko?connected=1')
            ->with('success', 'Shopee berhasil terhubung.');
    }
}
