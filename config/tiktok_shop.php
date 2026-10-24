<?php

return [
    'app_key' => env('TIKTOK_SHOP_APP_KEY'),
    'app_secret' => env('TIKTOK_SHOP_APP_SECRET'),
    'service_id' => env('TIKTOK_SHOP_SERVICE_ID'),
    'seller_auth_url' => env('TIKTOK_SHOP_SELLER_AUTH_URL', 'https://services.tiktokshop.com/open/authorize'),
    'redirect_uri' => env('TIKTOK_SHOP_REDIRECT_URI'),
    'base_url' => env('TIKTOK_SHOP_BASE_URL', 'https://open-api.tiktokglobalshop.com'),
    'auth_url' => env('TIKTOK_SHOP_AUTH_URL', 'https://auth.tiktok-shops.com'),
    // Finance API memiliki batas request yang lebih ketat karena halaman
    // settlement melakukan satu request daftar statement lalu request detail
    // untuk setiap statement. Pacing dan retry dibuat dapat dikonfigurasi
    // agar aman untuk proses web maupun sinkronisasi terjadwal.
    'finance_min_gap_ms' => (int) env('TIKTOK_FINANCE_MIN_GAP_MS', 250),
    'rate_limit_max_attempts' => (int) env('TIKTOK_RATE_LIMIT_MAX_ATTEMPTS', 3),
    'rate_limit_backoff_seconds' => (int) env('TIKTOK_RATE_LIMIT_BACKOFF_SECONDS', 1),
    'rate_limit_max_sleep_seconds' => (int) env('TIKTOK_RATE_LIMIT_MAX_SLEEP_SECONDS', 4),
    'logistics' => [
        // TikTok needs package attributes before it can return eligible
        // shipping services. These are fallbacks only; values from the
        // synced package/order payload or the UI take precedence.
        'weight_gram' => (float) env('TIKTOK_SHIPPING_WEIGHT_GRAM', 1000),
        'length_cm' => (float) env('TIKTOK_SHIPPING_LENGTH_CM', 20),
        'width_cm' => (float) env('TIKTOK_SHIPPING_WIDTH_CM', 15),
        'height_cm' => (float) env('TIKTOK_SHIPPING_HEIGHT_CM', 5),
    ],
];
