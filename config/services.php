<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'shopee' => [
        'partner_id' => env('SHOPEE_PARTNER_ID'),
        'partner_key' => env('SHOPEE_PARTNER_KEY'),
        'environment' => env('SHOPEE_ENVIRONMENT', 'sandbox'), // 'sandbox' atau 'production'
        'ads_scheduler_enabled' => env('SHOPEE_ADS_SCHEDULER_ENABLED', false),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5.6-terra'),
    ],

    'ffmpeg' => [
        'binary' => env('FFMPEG_BINARY'),
        'ffprobe_binary' => env('FFPROBE_BINARY'),
    ],

    'oauth' => [
        'default_role' => env('OAUTH_DEFAULT_ROLE', env('GOOGLE_OAUTH_DEFAULT_ROLE', 'operating')),
        'providers' => [
            'google' => [
                'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
                'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
                'redirect' => env('GOOGLE_OAUTH_REDIRECT_URI'),
                'scopes' => env('GOOGLE_OAUTH_SCOPES', 'openid email profile'),
                'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url' => 'https://oauth2.googleapis.com/token',
                'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
                'profile_id_key' => 'sub',
                'email_key' => 'email',
                'name_key' => 'name',
                'avatar_key' => 'picture',
                'prompt' => 'select_account',
                'access_type' => 'online',
            ],
            'github' => [
                'client_id' => env('GITHUB_OAUTH_CLIENT_ID'),
                'client_secret' => env('GITHUB_OAUTH_CLIENT_SECRET'),
                'redirect' => env('GITHUB_OAUTH_REDIRECT_URI'),
                'scopes' => env('GITHUB_OAUTH_SCOPES', 'read:user user:email'),
                'auth_url' => 'https://github.com/login/oauth/authorize',
                'token_url' => 'https://github.com/login/oauth/access_token',
                'userinfo_url' => 'https://api.github.com/user',
                'email_url' => 'https://api.github.com/user/emails',
                'profile_id_key' => 'id',
                'email_key' => 'email',
                'name_key' => 'name',
                'avatar_key' => 'avatar_url',
                'primary_email' => true,
            ],
        ],
    ],

    'google_oauth' => [
        'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_OAUTH_REDIRECT_URI'),
        'scopes' => env('GOOGLE_OAUTH_SCOPES', 'openid email profile'),
        'default_role' => env('GOOGLE_OAUTH_DEFAULT_ROLE', 'operating'),
    ],

];
