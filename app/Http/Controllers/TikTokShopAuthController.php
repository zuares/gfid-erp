<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTikTokShopOAuthJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TikTokShopAuthController extends Controller
{
    public function redirect()
    {
        $serviceId = trim((string) config('tiktok_shop.service_id'));
        $authUrl = rtrim(trim((string) config(
            'tiktok_shop.seller_auth_url',
            'https://services.tiktokshop.com/open/authorize'
        )), '/');

        if (! $serviceId) {
            return response('TIKTOK_SHOP_SERVICE_ID belum diisi di .env', 422);
        }

        if (request()->has('store_id')) {
            session(['tiktok_connect_store_id' => (int) request('store_id')]);
        }

        if (request()->query('integration') === 'ads_read_only') {
            session(['marketplace_integration' => 'ads_read_only']);
        } else {
            session()->forget('marketplace_integration');
        }

        $state = Str::random(64);
        session(['tiktok_oauth_state' => $state]);
        Cache::put('tiktok_oauth_state:' . $state, [
            'store_id' => session('tiktok_connect_store_id'),
            'integration' => session('marketplace_integration'),
        ], now()->addMinutes(10));

        return redirect()->away($authUrl . '?' . http_build_query([
            'service_id' => $serviceId,
            'state' => $state,
        ]));
    }

    public function callback(Request $request)
    {
        $expectedState = session('tiktok_oauth_state');
        $providedState = (string) $request->query('state', '');
        $cachedState = $providedState !== ''
            ? Cache::pull('tiktok_oauth_state:' . $providedState)
            : null;
        $sessionStateMatches = filled($expectedState)
            && filled($providedState)
            && hash_equals((string) $expectedState, $providedState);
        // TikTok Seller OAuth treats state as optional and some sandbox
        // callbacks omit it even when we sent it in the authorization URL.
        // In that case the intact browser session is still our binding to
        // the authorization request. Never accept a stateless callback.
        $sellerCallbackWithoutState = blank($providedState) && filled($expectedState);

        if (! $sessionStateMatches && ! $sellerCallbackWithoutState && ! is_array($cachedState)) {
            Log::warning('TikTok OAuth state mismatch', [
                'has_expected_state' => filled($expectedState),
                'expected_state_length' => strlen((string) $expectedState),
                'provided_state_length' => strlen($providedState),
                'session_id' => session()->getId(),
                'callback_host' => $request->getHost(),
            ]);
            session()->forget('tiktok_oauth_state');

            return redirect('/marketplace/toko')->with(
                'error',
                'TikTok callback ditolak karena state OAuth tidak valid.'
            );
        }

        if (! $sessionStateMatches && ! $sellerCallbackWithoutState) {
            // Bind the callback to the one-time server-side state when the
            // browser returns with a different session cookie.
            if (filled($cachedState['store_id'] ?? null)) {
                session(['tiktok_connect_store_id' => (int) $cachedState['store_id']]);
            }
            if (filled($cachedState['integration'] ?? null)) {
                session(['marketplace_integration' => $cachedState['integration']]);
            }
        }
        session()->forget('tiktok_oauth_state');

        if ($request->filled('error')) {
            return redirect('/marketplace/toko')->with(
                'error',
                'Otorisasi TikTok Shop dibatalkan: ' . (string) $request->query(
                    'error_description',
                    $request->query('error')
                )
            );
        }

        $authCode = $request->query('code') ?? $request->query('auth_code');
        if (! $authCode) {
            return redirect('/marketplace/toko')->with(
                'error',
                'TikTok Shop callback gagal. Code tidak ditemukan.'
            );
        }

        $job = new ProcessTikTokShopOAuthJob(
            (string) $authCode,
            session()->pull('tiktok_connect_store_id'),
            session()->pull('marketplace_integration') === 'ads_read_only',
        );

        // Do not make outbound DNS/HTTPS calls from PHP-FPM. On macOS Herd,
        // both cURL and PHP streams can abort inside getaddrinfo after FPM
        // forks. The queue CLI process handles the TikTok calls safely.
        if (app()->environment('testing')) {
            app(\App\Services\TikTokShop\TikTokShopOAuthService::class)
                ->process($job->authCode, $job->targetStoreId, $job->readOnlyIntegration);
        } else {
            dispatch($job);
        }

        return redirect('/marketplace/toko?connecting=1')->with(
            'success',
            'Permintaan koneksi TikTok Shop sedang diproses. Muat ulang halaman dalam beberapa detik.'
        );
    }
}
