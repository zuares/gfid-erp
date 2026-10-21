<?php

return [
    'app_key'    => env('TIKTOK_SHOP_APP_KEY'),
    'app_secret' => env('TIKTOK_SHOP_APP_SECRET'),
    'service_id' => env('TIKTOK_SHOP_SERVICE_ID'),
    'seller_auth_url' => env('TIKTOK_SHOP_SELLER_AUTH_URL', 'https://services.tiktokshop.com/open/authorize'),
    'redirect_uri' => env('TIKTOK_SHOP_REDIRECT_URI'),
    'base_url'   => env('TIKTOK_SHOP_BASE_URL', 'https://open-api.tiktokglobalshop.com'),
    'auth_url'   => env('TIKTOK_SHOP_AUTH_URL', 'https://auth.tiktok-shops.com'),
];
