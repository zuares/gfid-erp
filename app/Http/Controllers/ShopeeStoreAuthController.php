<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessShopeeOAuthJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopeeStoreAuthController extends Controller
{
    public function redirect()
    {
        $partnerId = trim((string) config('shopee.partner_id'));
        $partnerKey = trim((string) config('shopee.partner_key'));
        $baseUrl = rtrim(trim((string) config('shopee.base_url', 'https://partner.shopeemobile.com')), '/');

        if (! $partnerId || ! $partnerKey) {
            return response('SHOPEE_PARTNER_ID dan SHOPEE_PARTNER_KEY belum diisi di .env', 422);
        }

        if (request()->has('store_id')) {
            session(['shopee_connect_store_id' => request('store_id')]);
        }

        if (request()->query('integration') === 'ads_read_only') {
            session(['marketplace_integration' => 'ads_read_only']);
        } else {
            session()->forget('marketplace_integration');
        }

        $state = Str::random(64);
        session(['shopee_oauth_state' => $state]);

        $redirectUrl = rtrim(env('APP_URL', request()->getSchemeAndHttpHost()), '/') . '/marketplace/shopee/callback';
        $path = '/api/v2/shop/auth_partner';
        $timestamp = time();
        $sign = hash_hmac('sha256', $partnerId . $path . $timestamp, $partnerKey);

        return redirect()->away($baseUrl . $path . '?' . http_build_query([
            'partner_id' => (int) $partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'redirect' => $redirectUrl,
            'state' => $state,
        ]));
    }

    public function callback(Request $request)
    {
        $expectedState = session()->pull('shopee_oauth_state');
        if ($expectedState && $request->query('state') !== null
            && ! hash_equals($expectedState, (string) $request->query('state'))) {
            return redirect('/marketplace/toko')->with('error', 'Shopee callback ditolak karena state OAuth tidak valid.');
        }

        $code = $request->query('code');
        $shopId = $request->query('shop_id');

        if (! $code || ! $shopId) {
            return redirect('/marketplace/toko')->with('error', 'Shopee callback gagal. code atau shop_id tidak ditemukan.');
        }

        $storeId = session()->pull('shopee_connect_store_id');
        $readOnlyIntegration = session()->pull('marketplace_integration') === 'ads_read_only';

        dispatch(new ProcessShopeeOAuthJob(
            (string) $code,
            (int) $shopId,
            $storeId ? (int) $storeId : null,
            $readOnlyIntegration,
        ));

        return redirect('/marketplace/toko?connecting=1')->with(
            'success',
            'Permintaan koneksi Shopee sedang diproses. Muat ulang halaman dalam beberapa detik.'
        );
    }
}
