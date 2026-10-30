<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Store;
use App\Models\User;
use App\Services\MarketplaceProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MarketplaceProductTikTokCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_options_include_active_tiktok_stores(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-TTK-OPTIONS']);
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'TTK-OPTIONS',
            'name' => 'Toko TikTok Aktif',
            'status' => 'active',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner)
            ->getJson('/api/marketplace/products/create/options')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $store->id,
                'name' => 'Toko TikTok Aktif',
                'channel' => 'tiktok',
                'channel_name' => 'TikTok Shop',
            ]);

        $storeOption = collect($response->json('stores'))->firstWhere('id', $store->id);
        $this->assertSame('tiktok', $storeOption['channel'] ?? null);
    }

    public function test_publish_tiktok_create_builds_the_official_single_sku_payload(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-TTK-PUBLISH']);
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'TTK-PUBLISH',
            'name' => 'Toko TikTok Publish',
            'status' => 'active',
            'is_active' => true,
        ]);
        $service = Mockery::mock(MarketplaceProductService::class);
        $service->shouldReceive('createProduct')->once()->with(
            Mockery::on(fn (Store $candidate): bool => $candidate->is($store)),
            Mockery::on(function (array $payload): bool {
                return $payload['save_mode'] === 'AS_DRAFT'
                    && $payload['category_version'] === 'v2'
                    && $payload['listing_platforms'] === ['TIKTOK_SHOP']
                    && $payload['main_images'] === [['uri' => 'tos://main-image']]
                    && data_get($payload, 'skus.0.seller_sku') === 'JACKET-BLK-M'
                    && data_get($payload, 'skus.0.inventory.0.warehouse_id') === 'warehouse-1'
                    && data_get($payload, 'product_attributes.0.id') === '10001'
                    && data_get($payload, 'product_attributes.0.values.0.id') === '20001';
            })
        )->andReturn(['success' => true, 'item_id' => 'product-new', 'product_id' => 99]);
        $this->app->instance(MarketplaceProductService::class, $service);

        $this->actingAs($owner)
            ->postJson('/api/marketplace/products/create/publish', [
                'store_id' => $store->id,
                'item_name' => 'Jaket Track Baru',
                'item_sku' => 'JACKET-BLK-M',
                'description' => "Bahan nyaman\nWarna hitam",
                'category_id' => '12345',
                'image_ids' => ['tos://main-image'],
                'original_price' => 125000,
                'stock' => 8,
                'weight' => 0.5,
                'dimension' => ['package_length' => 20, 'package_width' => 15, 'package_height' => 5],
                'warehouse_id' => 'warehouse-1',
                'attribute_list' => [[
                    'attribute_id' => '10001',
                    'attribute_value_list' => [[
                        'value_id' => '20001',
                        'original_value_name' => 'Hitam',
                    ]],
                ]],
                'save_mode' => 'AS_DRAFT',
                'idempotency_key' => '7bb1da2b-58e7-4ef7-a6b0-bcb36b31d53e',
            ])
            ->assertOk()
            ->assertJsonPath('item_id', 'product-new');
    }

    public function test_owner_can_run_tiktok_product_diagnostics(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWNER-TTK-DIAGNOSTICS']);
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'TTK-DIAGNOSTICS',
            'name' => 'Toko TikTok Diagnostics',
            'status' => 'active',
            'is_active' => true,
        ]);
        $service = Mockery::mock(MarketplaceProductService::class);
        $service->shouldReceive('diagnoseTikTokConnection')->once()->with(
            Mockery::on(fn (Store $candidate): bool => $candidate->is($store))
        )->andReturn([
            'status' => 'success',
            'message' => 'Semua endpoint utama TikTok Shop merespons dengan baik.',
            'reconnect_required' => false,
            'token_refreshed' => false,
            'steps' => [],
        ]);
        $this->app->instance(MarketplaceProductService::class, $service);

        $this->actingAs($owner)
            ->postJson('/marketplace/products/tiktok-diagnostics', ['store_id' => $store->id])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('reconnect_required', false);
    }

    public function test_non_owner_cannot_run_tiktok_product_diagnostics(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADMIN-TTK-DIAGNOSTICS']);
        $channel = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'TTK-DIAGNOSTICS-FORBIDDEN',
            'name' => 'Toko TikTok Diagnostics Forbidden',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->postJson('/marketplace/products/tiktok-diagnostics', ['store_id' => $store->id])
            ->assertForbidden();
    }
}
