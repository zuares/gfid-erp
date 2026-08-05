<?php

return [
    'partner_id' => env('SHOPEE_PARTNER_ID'),
    'partner_key' => env('SHOPEE_PARTNER_KEY'),
    'shop_id' => env('SHOPEE_SHOP_ID'),
    'access_token' => env('SHOPEE_ACCESS_TOKEN'),
    'refresh_token' => env('SHOPEE_REFRESH_TOKEN'),
    'base_url' => env('SHOPEE_BASE_URL', 'https://partner.shopeemobile.com'),

    /*
    |--------------------------------------------------------------------------
    | Resiliensi API (anti rate-limit) — dipakai ShopeeChannel::resilientRequest()
    |--------------------------------------------------------------------------
    | min_gap_ms          : jarak minimum antar panggilan API per toko (lintas
    |                       proses). 150 ms ≈ 6-7 req/detik, aman di bawah batas
    |                       ±10 QPS per toko. Set 0 untuk mematikan pacing.
    | retry_max_attempts  : jumlah percobaan maksimal per request. Default:
    |                       3 di console/cron/queue, 2 di request web.
    | retry_max_sleep     : batas atas detik tidur backoff per attempt (console).
    |                       Request web selalu dibatasi 3 detik.
    | rate_limit_cooldown : fallback detik cooldown saat Shopee menjawab 429
    |                       tanpa header Retry-After.
    */
    'min_gap_ms'          => (int) env('SHOPEE_MIN_GAP_MS', 150),
    'retry_max_attempts'  => env('SHOPEE_RETRY_MAX_ATTEMPTS'),
    'retry_max_sleep'     => (int) env('SHOPEE_RETRY_MAX_SLEEP', 15),
    'rate_limit_cooldown' => (int) env('SHOPEE_RATE_LIMIT_COOLDOWN', 30),

    // Rentang default enrichment escrow_release_time. Dibuat terbatas agar
    // scheduler tidak mengulang seluruh histori pada setiap siklus.
    'release_time_lookback_days' => (int) env('SHOPEE_RELEASE_TIME_LOOKBACK_DAYS', 45),
];
