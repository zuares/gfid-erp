<?php

namespace Tests\Unit\Services;

use App\Models\Channel;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductImage;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\Contracts\MarketplaceChannel;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\MarketplaceProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MarketplaceProductServiceImagesTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'IMG-' . rand(1000, 9999),
            'name' => 'Toko Gambar Uji',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function createProduct(Store $store): MarketplaceProduct
    {
        return MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => '9001',
            'item_name' => 'Produk gambar uji',
            'image_url' => 'https://img.test/old-a.jpg',
            'item_status' => 'NORMAL',
        ]);
    }

    public function test_update_images_persists_verified_remote_order_and_audit(): void
    {
        $store = $this->createStore();
        $product = $this->createProduct($store);
        $gateway = Mockery::mock(MarketplaceApiGateway::class);
        $driver = Mockery::mock(MarketplaceChannel::class);
        $manager = Mockery::mock(ChannelManager::class);

        $manager->shouldReceive('driver')->once()->with(Mockery::type(Store::class))->andReturn($driver);
        $driver->shouldReceive('getItemBaseInfo')->twice()->andReturn(
            [
                'response' => ['item_list' => [[
                    'item_id' => '9001',
                    'item_name' => 'Produk gambar uji',
                    'image' => [
                        'image_id_list' => ['img-a', 'img-b'],
                        'image_url_list' => ['https://img.test/a.jpg', 'https://img.test/b.jpg'],
                    ],
                ]]],
            ],
            [
                'response' => ['item_list' => [[
                    'item_id' => '9001',
                    'item_name' => 'Produk gambar uji',
                    'item_status' => 'NORMAL',
                    'has_model' => false,
                    'image' => [
                        'image_id_list' => ['img-b', 'img-a'],
                        'image_url_list' => ['https://img.test/b.jpg', 'https://img.test/a.jpg'],
                    ],
                ]]],
            ],
        );
        $driver->shouldReceive('updateProductImages')
            ->once()
            ->with(Mockery::type(Store::class), 9001, ['img-b', 'img-a'])
            ->andReturn(['success' => true]);

        $service = new MarketplaceProductService($gateway, $manager);
        $result = $service->updateImages($product, ['existing:img-b', 'existing:img-a']);

        $this->assertTrue($result['success']);
        $this->assertSame(['img-b', 'img-a'], $result['image_ids']);
        $this->assertSame(
            ['img-b', 'img-a'],
            MarketplaceProductImage::where('marketplace_product_id', $product->id)
                ->orderBy('sort_order')
                ->pluck('shopee_image_id')
                ->all()
        );
        $this->assertDatabaseHas('marketplace_product_change_logs', [
            'marketplace_product_id' => $product->id,
            'action' => 'images',
            'status' => 'success',
        ]);
    }

    public function test_refresh_failure_does_not_overwrite_local_snapshot_and_returns_sync_warning(): void
    {
        $store = $this->createStore();
        $product = $this->createProduct($store);
        $gateway = Mockery::mock(MarketplaceApiGateway::class);
        $driver = Mockery::mock(MarketplaceChannel::class);
        $manager = Mockery::mock(ChannelManager::class);

        $manager->shouldReceive('driver')->once()->with(Mockery::type(Store::class))->andReturn($driver);
        $driver->shouldReceive('getItemBaseInfo')->twice()->andReturn(
            [
                'response' => ['item_list' => [[
                    'item_id' => '9001',
                    'image' => ['image_id_list' => ['img-a']],
                ]]],
            ],
            ['error' => 'timeout', 'message' => 'Shopee timeout'],
        );
        $driver->shouldReceive('updateProductImages')
            ->once()
            ->with(Mockery::type(Store::class), 9001, ['img-a'])
            ->andReturn(['success' => true]);

        $service = new MarketplaceProductService($gateway, $manager);
        $result = $service->updateImages($product, ['existing:img-a']);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['sync_required']);
        $this->assertSame('https://img.test/old-a.jpg', $product->fresh()->image_url);
        $this->assertDatabaseHas('marketplace_product_change_logs', [
            'marketplace_product_id' => $product->id,
            'action' => 'images',
            'status' => 'warning',
        ]);
        $this->assertDatabaseCount('marketplace_product_images', 0);
    }

    public function test_create_product_publishes_and_persists_returned_item_id(): void
    {
        $store = $this->createStore();
        $gateway = Mockery::mock(MarketplaceApiGateway::class);
        $driver = Mockery::mock(MarketplaceChannel::class);
        $manager = Mockery::mock(ChannelManager::class);
        $manager->shouldReceive('driver')->once()->with(Mockery::type(Store::class))->andReturn($driver);

        $payload = [
            'item_name' => 'Produk Baru Uji',
            'description' => 'Deskripsi produk baru',
            'category_id' => 123,
            'original_price' => 125000,
            'weight' => 0.5,
            'image' => ['image_id_list' => ['img-new']],
            'seller_stock' => [['stock' => 12]],
            'logistic_info' => [['logistic_id' => 80101, 'enabled' => true]],
            'item_status' => 'UNLIST',
        ];

        $driver->shouldReceive('addProduct')->once()->with(Mockery::type(Store::class), $payload)->andReturn([
            'response' => ['item_id' => 99001],
        ]);
        $driver->shouldReceive('getItemBaseInfo')->once()->andReturn([
            'response' => ['item_list' => [[
                'item_id' => '99001',
                'item_name' => 'Produk Baru Uji',
                'item_status' => 'UNLIST',
                'category_id' => 123,
                'image' => [
                    'image_id_list' => ['img-new'],
                    'image_url_list' => ['https://img.test/new.jpg'],
                ],
                'price_info' => [['original_price' => 125000]],
                'stock_info_v2' => ['seller_stock' => [['stock' => 12]]],
            ]]],
        ]);

        $service = new MarketplaceProductService($gateway, $manager);
        $result = $service->createProduct($store, $payload);

        $this->assertTrue($result['success']);
        $this->assertSame('99001', $result['item_id']);
        $this->assertDatabaseHas('marketplace_products', [
            'store_id' => $store->id,
            'item_id' => '99001',
            'item_name' => 'Produk Baru Uji',
            'item_status' => 'UNLIST',
        ]);
        $this->assertDatabaseHas('marketplace_product_change_logs', [
            'marketplace_product_id' => $result['product_id'],
            'action' => 'create',
            'status' => 'success',
        ]);
    }
}
