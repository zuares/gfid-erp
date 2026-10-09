<?php

use App\Models\InstagramConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('starts Instagram OAuth with a user-bound state', function () {
    config([
        'meta.instagram.client_id' => 'instagram-app-123',
        'meta.instagram.client_secret' => 'instagram-secret',
        'meta.instagram.redirect_uri' => 'http://localhost/social-media/instagram/callback',
    ]);

    $user = User::factory()->create([
        'employee_code' => 'IGTEST-0',
        'role' => 'owner',
    ]);

    $response = $this->actingAs($user)
        ->get(route('social-media.instagram.connect'));

    $response->assertRedirect();
    $response->assertRedirectContains('https://www.instagram.com/oauth/authorize');
    $response->assertSessionHas('instagram.oauth_state');

    $state = session('instagram.oauth_state');
    expect($state['user_id'])->toBe($user->id)
        ->and($state['value'])->toHaveLength(64);
});

it('stores a long-lived Instagram connection for the authenticated user', function () {
    config([
        'meta.instagram.client_id' => 'instagram-app-123',
        'meta.instagram.client_secret' => 'instagram-secret',
        'meta.instagram.redirect_uri' => 'http://localhost/social-media/instagram/callback',
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

    $user = User::factory()->create([
        'employee_code' => 'IGTEST-1',
        'role' => 'owner',
    ]);

    $response = $this->actingAs($user)
        ->withSession([
            'instagram.oauth_state' => [
                'value' => 'state-abc',
                'user_id' => $user->id,
                'created_at' => now()->timestamp,
            ],
        ])
        ->get(route('social-media.instagram.callback', [
            'state' => 'state-abc',
            'code' => 'auth-code#_',
        ]));

    $response->assertRedirect(route('social-media.instagram'));
    $response->assertSessionHas('success', 'Akun Instagram berhasil dihubungkan.');

    $connection = InstagramConnection::query()->firstOrFail();

    expect($connection->user_id)->toBe($user->id)
        ->and($connection->instagram_user_id)->toBe('17840000000000001')
        ->and($connection->username)->toBe('greatfit.id')
        ->and($connection->access_token)->toBe('long-lived-token')
        ->and($connection->scopes)->toBe(['instagram_business_basic'])
        ->and($connection->isActive())->toBeTrue();
});

it('rejects a missing or expired OAuth state without calling Meta', function () {
    config([
        'meta.instagram.client_id' => 'instagram-app-123',
        'meta.instagram.client_secret' => 'instagram-secret',
        'meta.instagram.redirect_uri' => 'http://localhost/social-media/instagram/callback',
    ]);

    $user = User::factory()->create([
        'employee_code' => 'IGTEST-2',
        'role' => 'owner',
    ]);

    $response = $this->actingAs($user)
        ->withSession([
            'instagram.oauth_state' => [
                'value' => 'state-abc',
                'user_id' => $user->id,
                'created_at' => now()->subMinutes(11)->timestamp,
            ],
        ])
        ->get(route('social-media.instagram.callback', [
            'state' => 'state-abc',
            'code' => 'auth-code',
        ]));

    $response->assertRedirect(route('social-media.instagram'));
    $response->assertSessionHas('error');
    Http::assertNothingSent();
    expect(InstagramConnection::query()->count())->toBe(0);
});
