<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta / Instagram Platform
    |--------------------------------------------------------------------------
    |
    | The Instagram Login flow uses the Instagram App ID and App Secret from
    | Meta App Dashboard > Instagram > API setup with Instagram login.
    | Keep the secret server-side and never expose it to browser code.
    |
    */
    'instagram' => [
        'client_id' => env('META_INSTAGRAM_APP_ID'),
        'client_secret' => env('META_INSTAGRAM_APP_SECRET'),
        'redirect_uri' => env('META_INSTAGRAM_REDIRECT_URI'),
        'api_version' => env('META_INSTAGRAM_API_VERSION', 'v25.0'),
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'META_INSTAGRAM_SCOPES',
                'instagram_business_basic'
            ))
        ))),
        'authorization_url' => 'https://www.instagram.com/oauth/authorize',
        'short_lived_token_url' => 'https://api.instagram.com/oauth/access_token',
        'graph_url' => 'https://graph.instagram.com',
        'timeout' => (int) env('META_INSTAGRAM_HTTP_TIMEOUT', 30),
    ],

];
