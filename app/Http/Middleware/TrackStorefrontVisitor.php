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

    private function isInternalIp(string $ip, string $rawSetting): bool
    {
        $entries = array_filter(array_map('trim', explode("\n", str_replace(',', "\n", $rawSetting))));
        foreach ($entries as $entry) {
            if (str_ends_with($entry, '.*')) {
                $prefix = rtrim($entry, '.*');
                if (str_starts_with($ip, $prefix . '.')) return true;
                continue;
            }
            if (str_contains($entry, '/')) {
                if ($this->ipMatchesCidr($ip, $entry)) return true;
                continue;
            }
            if ($ip === $entry) return true;
        }
        return false;
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) return false;
        $ip     = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask   = -1 << (32 - (int) $bits);
        return ($ip & $mask) === ($subnet & $mask);
    }

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

            // Auto-mark as internal if IP matches office IP rules
            $internalIpSetting = \App\Models\SystemSetting::get('crm_internal_ips', '');
            $isInternalIp = false;
            if (! empty(trim($internalIpSetting))) {
                $isInternalIp = $this->isInternalIp($request->ip(), $internalIpSetting);
            }

            // Auto-mark as internal if they are logged in as staff (admin, owner, operating, dll)
            $isStaff = false;
            if (auth()->check() && auth()->user()) {
                // Semua user yang login ke dashboard admin (apapun rolenya) dianggap internal
                $isStaff = true;
            }

            if ($isInternalIp) {
                $visitor->is_internal     = true;
                $visitor->internal_reason = 'ip';
            } elseif ($isStaff) {
                $visitor->is_internal     = true;
                $visitor->internal_reason = 'staff';
            }
        }

        // Always ensure staff are marked as internal even if they were originally tracked as external
        // (Misalnya mereka buka website dulu sbg Guest, lalu login ke admin dashboard. Kita update statusnya jadi internal)
        if (! $visitor->is_internal && auth()->check() && auth()->user()) {
            $visitor->is_internal     = true;
            $visitor->internal_reason = 'staff';
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
