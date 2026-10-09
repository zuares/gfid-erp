<?php

use App\Services\Instagram\InstagramOAuthService;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

it('builds the Instagram authorization URL with the configured state and scopes', function () {
    config([
        'meta.instagram.client_id' => 'instagram-app-123',
        'meta.instagram.client_secret' => 'instagram-secret',
        'meta.instagram.redirect_uri' => 'https://app.test/social-media/instagram/callback',
        'meta.instagram.scopes' => [
            'instagram_business_basic',
            'instagram_business_content_publish',
        ],
    ]);

    $url = app(InstagramOAuthService::class)->authorizationUrl('state-abc');
    $query = parse_url($url, PHP_URL_QUERY);
    parse_str((string) $query, $params);

    expect($url)->toStartWith('https://www.instagram.com/oauth/authorize?')
        ->and($params['client_id'])->toBe('instagram-app-123')
        ->and($params['redirect_uri'])->toBe('https://app.test/social-media/instagram/callback')
        ->and($params['response_type'])->toBe('code')
        ->and($params['scope'])->toBe('instagram_business_basic,instagram_business_content_publish')
        ->and($params['state'])->toBe('state-abc');
});

it('exchanges an authorization code and loads the Instagram profile', function () {
    config([
        'meta.instagram.client_id' => 'instagram-app-123',
        'meta.instagram.client_secret' => 'instagram-secret',
        'meta.instagram.redirect_uri' => 'https://app.test/social-media/instagram/callback',
    ]);

    Http::fake([
        'https://api.instagram.com/oauth/access_token' => Http::response([
            'access_token' => 'short-lived-token',
            'user_id' => '17840000000000001',
            'permissions' => 'instagram_business_basic',
        ], 200),
        'https://graph.instagram.com/access_token*' => Http::response([
            'access_token' => 'long-lived-token',
            'token_type' => 'bearer',
            'expires_in' => 5183944,
        ], 200),
        'https://graph.instagram.com/me*' => Http::response([
            'id' => '17840000000000001',
            'username' => 'greatfit.id',
            'account_type' => 'BUSINESS',
        ], 200),
    ]);

    $service = app(InstagramOAuthService::class);
    $token = $service->exchangeCodeForLongLivedToken('auth-code');
    $profile = $service->fetchProfile($token['access_token']);

    expect($token['access_token'])->toBe('long-lived-token')
        ->and($token['expires_in'])->toBe(5183944)
        ->and($token['scopes'])->toBe(['instagram_business_basic'])
        ->and($profile['username'])->toBe('greatfit.id');

    Http::assertSentCount(3);
});
