<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Channels\Shopee\ShopeeChannel;
use Tests\TestCase;

class MarketplaceAdsApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_only_integration_status_never_exposes_credentials(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'employee_code' => 'RO-STATUS-EMP']);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee', 'status' => 'active']);

        $store = Store::create([
            'code' => 'RO-STATUS',
            'name' => 'Toko Read Only',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'access_token' => 'must-not-leak',
                'refresh_token' => 'must-not-leak-either',
            ],
            'token_expires_at' => now()->addHour(),
            'meta' => [
                'api_access_mode' => 'read_only',
                'api_scopes' => ['ads.read', 'shop.read'],
            ],
        ]);

        $response = $this->actingAs($user)->getJson(route('marketplace.ads.integration.status'));

        $response->assertOk()
            ->assertJsonPath('data.0.id', $store->id)
            ->assertJsonPath('data.0.status', 'connected')
            ->assertJsonMissing(['access_token' => 'must-not-leak'])
            ->assertJsonMissing(['refresh_token' => 'must-not-leak-either']);
    }

    public function test_revoke_clears_tokens_and_marks_integration_revoked(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'employee_code' => 'RO-REVOKE-EMP']);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee', 'status' => 'active']);

        $store = Store::create([
            'code' => 'RO-REVOKE',
            'name' => 'Toko Revoke',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'partner_id' => '123',
                'access_token' => 'secret-token',
                'refresh_token' => 'secret-refresh',
            ],
            'token_expires_at' => now()->addHour(),
            'meta' => ['api_access_mode' => 'read_only'],
        ]);

        $response = $this->actingAs($user)->postJson(
            route('marketplace.ads.integration.revoke', ['store' => $store])
        );

        $response->assertOk()
            ->assertJsonPath('status', 'revoked')
            ->assertJsonMissing(['access_token' => 'secret-token']);

        $store->refresh();

        $this->assertSame('revoked', $store->status);
        $this->assertNull($store->token_expires_at);
        $this->assertSame('123', $store->credential('partner_id'));
        $this->assertNull($store->credential('access_token'));
        $this->assertNull($store->credential('refresh_token'));
        $this->assertNotEmpty(data_get($store->meta, 'api_revoked_at'));
        $this->assertSame('revoked', $store->readOnlyAdsIntegrationStatus());
    }

    public function test_read_only_store_blocks_provider_mutation_endpoint(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee', 'status' => 'active']);
        $store = Store::create([
            'code' => 'RO-BLOCK',
            'name' => 'Toko Block',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'partner_id' => '123',
                'partner_key' => 'secret',
                'shop_id' => '456',
                'access_token' => 'token',
            ],
            'token_expires_at' => now()->addHour(),
            'meta' => ['api_access_mode' => 'read_only'],
        ]);

        $response = app(ShopeeChannel::class)->editManualProductAds(
            $store,
            99,
            'change_budget',
            ['budget' => 1000],
        );

        $this->assertSame('read_only_integration', $response['error']);
        $this->assertSame(403, data_get($response, '_meta.http_status'));
    }
}
