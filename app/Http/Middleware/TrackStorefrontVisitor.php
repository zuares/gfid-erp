<?php

namespace App\Http\Middleware;

use App\Models\StorefrontEvent;
use App\Models\StorefrontVisitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackStorefrontVisitor
{
    /**
     * Lookup kota & provinsi dari IP menggunakan ip-api.com (gratis, tanpa key).
     * Timeout 2 detik — kalau gagal, return null (tidak memblokir request).
     * IP lokal/private di-skip langsung.
     *
     * @return array{0: string|null, 1: string|null}  [city, province]
     */
    private function geolocateIp(string $ip): array
    {
        // Skip IP lokal dan private (127.x, 192.168.x, 10.x, dll)
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return [null, null];
        }

        try {
            $ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,city,regionName");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);
            $body   = curl_exec($ch);
            $errNo  = curl_errno($ch);
            curl_close($ch);

            if ($errNo || ! $body) return [null, null];

            $data = json_decode($body, true);
            if (($data['status'] ?? '') !== 'success') return [null, null];

            return [
                $data['city']       ?? null,  // contoh: "Bandung"
                $data['regionName'] ?? null,  // contoh: "West Java"
            ];
        } catch (\Throwable) {
            return [null, null];
        }
    }

    // Route names yang tidak perlu di-track sebagai page_view
    private const SKIP_PAGE_VIEW = [
        'storefront.cart.add',
        'storefront.cart.update',
        'storefront.cart.remove',
        'storefront.checkout.address.save',
        'storefront.checkout.ongkir',
        'storefront.checkout.upload_bukti',
        'storefront.checkout.place_order',
        'storefront.order.wa_click',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Ambil atau buat visitor token
        // Prioritas: cookie → session fallback → buat baru
        $fromCookie  = $request->cookie('gf_vid');
        $fromSession = $request->session()->get('_gf_vid');
        $token       = $fromCookie ?: $fromSession ?: Str::random(48);

        // Simpan/update visitor record
        $now = now();
        $visitor = StorefrontVisitor::firstOrNew(['visitor_token' => $token]);

        if (! $visitor->exists) {
            $visitor->first_seen_at = $now;
            $visitor->ip_address    = $request->ip();
            $visitor->user_agent    = substr($request->userAgent() ?? '', 0, 300);
            $visitor->referrer      = substr($request->headers->get('referer', ''), 0, 500);
            $visitor->utm_source    = $request->query('utm_source');
            $visitor->utm_medium    = $request->query('utm_medium');
            $visitor->utm_campaign  = $request->query('utm_campaign');
            // city/province diisi di terminate() setelah response dikirim — tidak menambah latency
        }

        $visitor->last_seen_at = $now;
        $visitor->save();

        // Simpan ke session sebagai fallback (berguna saat cookie belum dikirim browser)
        $request->session()->put('_gf_vid', $token);

        // Share ke view supaya bisa dimasukkan sebagai hidden field di form
        view()->share('_sfToken', $token);

        // Share token ke seluruh request lifecycle
        $request->attributes->set('visitor_token', $token);

        // Log page_view / product_view hanya untuk GET requests yang bukan API/action
        $routeName = $request->route()?->getName() ?? '';
        if ($request->isMethod('GET') && ! in_array($routeName, self::SKIP_PAGE_VIEW)) {
            // product_detail mendapat event type tersendiri agar funnel bisa membedakannya
            $eventType = $routeName === 'storefront.product_detail' ? 'product_view' : 'page_view';
            StorefrontEvent::create([
                'visitor_token' => $token,
                'event_type'    => $eventType,
                'payload'       => [
                    'url'   => $request->url(),
                    'route' => $routeName,
                    'slug'  => $request->route('slug'),
                ],
                'created_at'    => $now,
            ]);
        }

        $response = $next($request);

        // Set cookie 1 tahun jika belum ada
        if (! $request->hasCookie('gf_vid')) {
            $response->cookie('gf_vid', $token, 60 * 24 * 365, '/', null, false, true);
        }

        return $response;
    }

    /**
     * Dijalankan SETELAH response dikirim ke browser (PHP-FPM: setelah fastcgi_finish_request).
     * Dipakai untuk geolocation IP — tidak menambah latency ke user sama sekali.
     */
    public function terminate(Request $request, $response): void
    {
        $token = $request->attributes->get('visitor_token')
            ?? $request->cookie('gf_vid')
            ?? $request->session()->get('_gf_vid');

        if (! $token) return;

        // Hanya proses visitor yang belum ada city-nya
        $visitor = StorefrontVisitor::where('visitor_token', $token)
            ->whereNull('city')
            ->first();

        if (! $visitor || ! $visitor->ip_address) return;

        // Dev mode: IP lokal/private → pakai kota simulasi supaya UI bisa dites
        $isPrivate = ! filter_var(
            $visitor->ip_address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPrivate && env('APP_DB_MODE') === 'dev') {
            static $devCities = [
                ['city' => 'Bandung',    'province' => 'Jawa Barat'],
                ['city' => 'Jakarta',    'province' => 'DKI Jakarta'],
                ['city' => 'Surabaya',   'province' => 'Jawa Timur'],
                ['city' => 'Yogyakarta', 'province' => 'DI Yogyakarta'],
                ['city' => 'Semarang',   'province' => 'Jawa Tengah'],
                ['city' => 'Medan',      'province' => 'Sumatera Utara'],
                ['city' => 'Makassar',   'province' => 'Sulawesi Selatan'],
                ['city' => 'Denpasar',   'province' => 'Bali'],
            ];
            $pick = $devCities[array_rand($devCities)];
            $visitor->update(['city' => $pick['city'], 'province' => $pick['province']]);
            return;
        }

        [$city, $province] = $this->geolocateIp($visitor->ip_address);

        if ($city) {
            $visitor->update(['city' => $city, 'province' => $province]);
        }
    }
}
