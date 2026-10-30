<?php

namespace Tests\Unit\Services;

use App\Models\Channel;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductModel;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\TikTokShop\TikTokShopChannel;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\MarketplaceProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MarketplaceProductServiceTikTokTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiktok_diagnostics_checks_shop_search_and_product_without_exposing_secrets(): void
    {
        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-DIAGNOSTIC-TEST',
            'name' => 'TikTok Diagnostic Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'app_key' => 'app-key-secret',
                'app_secret' => 'app-secret-sensitive',
                'access_token' => 'TTP_access-token-sensitive',
                'refresh_token' => 'refresh-token-sensitive',
                'shop_cipher' => 'shop-cipher-sensitive',
            ],
        ]);

        $gateway = Mockery::mock(MarketplaceApiGateway::class);
        $gateway->shouldReceive('getShopInfo')->once()->andReturn([
            'code' => 0,
            'request_id' => 'shop-request',
            'data' => ['shops' => [['id' => 'shop-1']]],
        ]);
        $gateway->shouldReceive('searchProducts')->once()->with(
            Mockery::type(Store::class),
            'ALL',
            '',
            1,
        )->andReturn([
            'code' => 0,
            'request_id' => 'search-request',
            'data' => ['products' => [['id' => 'product-1', 'title' => 'Jaket']], 'next_page_token' => 'next'],
        ]);
        $gateway->shouldReceive('getProduct')->once()->with(
            Mockery::type(Store::class),
            'product-1',
        )->andReturn([
            'code' => 0,
            'request_id' => 'detail-request',
            'data' => ['id' => 'product-1', 'title' => 'Jaket', 'status' => 'ACTIVATE', 'skus' => [['id' => 'sku-1']]],
        ]);

        $result = (new MarketplaceProductService($gateway))->diagnoseTikTokConnection($store);
        $encoded = json_encode($result);

        $this->assertSame('success', $result['status']);
        $this->assertFalse($result['reconnect_required']);
        $this->assertCount(4, $result['steps']);
        $this->assertSame('product-1', data_get($result, 'steps.3.details.product_id'));
        $this->assertStringNotContainsString('app-secret-sensitive', $encoded);
        $this->assertStringNotContainsString('TTP_access-token-sensitive', $encoded);
        $this->assertStringNotContainsString('refresh-token-sensitive', $encoded);
        $this->assertStringNotContainsString('shop-cipher-sensitive', $encoded);
    }

    public function test_tiktok_catalog_sync_persists_product_skus_prices_stock_and_status(): void
    {
        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-CATALOG-TEST',
            'name' => 'TikTok Catalog Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $gateway = Mockery::mock(MarketplaceApiGateway::class);
        $gateway->shouldReceive('searchProducts')->once()->andReturn([
            'code' => 0,
            'data' => [
                'products' => [[
                    'id' => '1729592969712207008',
                    'title' => 'Track Jacket',
                    'status' => 'ACTIVATE',
                ]],
                'next_page_token' => null,
            ],
        ]);
        $gateway->shouldReceive('getProduct')->once()->with(
            Mockery::type(Store::class),
            '1729592969712207008'
        )->andReturn([
            'code' => 0,
            'data' => [
                'id' => '1729592969712207008',
                'title' => 'Track Jacket',
                'status' => 'ACTIVATE',
                'categories' => [['id' => '12345']],
                'main_images' => [['uri' => 'tos://cover', 'urls' => ['https://img.test/cover.jpg']]],
                'skus' => [
                    [
                        'id' => '1729592969712207012',
                        'seller_sku' => 'TTJ-BLK-M',
                        'price' => ['currency' => 'IDR', 'tax_exclusive_price' => '125000'],
                        'inventory' => [['warehouse_id' => 'warehouse-1', 'quantity' => 7]],
                        'sales_attributes' => [['name' => 'Warna', 'value_name' => 'Hitam']],
                    ],
                    [
                        'id' => '1729592969712207013',
                        'seller_sku' => 'TTJ-BLK-L',
                        'price' => ['currency' => 'IDR', 'tax_exclusive_price' => '130000'],
                        'inventory' => [['warehouse_id' => 'warehouse-1', 'quantity' => 4]],
                        'sales_attributes' => [['name' => 'Warna', 'value_name' => 'Hitam'], ['name' => 'Ukuran', 'value_name' => 'L']],
                    ],
                ],
            ],
        ]);

        $result = (new MarketplaceProductService($gateway))->syncProducts($store);

        $this->assertSame(1, $result['synced']);
        $this->assertSame([], $result['errors']);
        $product = MarketplaceProduct::where('store_id', $store->id)->firstOrFail();
        $this->assertSame('NORMAL', $product->item_status);
        $this->assertSame('12345', $product->category_id);
        $this->assertSame(11, $product->stock_total);
        $this->assertSame(125000.0, (float) $product->price_min);
        $this->assertSame(130000.0, (float) $product->price_max);
        $this->assertSame(2, $product->models()->count());
        $this->assertSame('warehouse-1', data_get($product->models()->first()->raw_json, 'inventory.0.warehouse_id'));
        $this->assertSame('https://img.test/cover.jpg', $product->image_url);
    }

    public function test_tiktok_detail_uses_last_snapshot_when_live_endpoint_returns_502(): void
    {
        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-DETAIL-TEST',
            'name' => 'TikTok Detail Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $product = MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => '1729592969712207008',
            'item_name' => 'Track Jacket',
            'item_status' => 'NORMAL',
            'raw_json' => [
                'id' => '1729592969712207008',
                'title' => 'Track Jacket',
                'status' => 'ACTIVATE',
                'main_images' => [['uri' => 'tos://cover', 'urls' => ['https://img.test/cover.jpg']]],
            ],
        ]);
        MarketplaceProductModel::create([
            'marketplace_product_id' => $product->id,
            'model_id' => '1729592969712207012',
            'model_name' => 'Hitam',
            'model_sku' => 'TTJ-BLK-M',
            'price' => 125000,
            'stock' => 7,
            'raw_json' => ['price' => ['tax_exclusive_price' => '125000']],
        ]);

        $gateway = Mockery::mock(MarketplaceApiGateway::class);
        $gateway->shouldReceive('getProduct')->once()->with(
            Mockery::type(Store::class),
            '1729592969712207008'
        )->andReturn([
            'error' => true,
            'message' => 'HTTP 502 Bad Gateway',
        ]);

        $result = (new MarketplaceProductService($gateway))->getEditData($product);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertTrue($result['source']['stale_snapshot']);
        $this->assertSame('HTTP 502 Bad Gateway', $result['source']['warning']);
        $this->assertSame('Track Jacket', $result['product']['item_name']);
        $this->assertCount(1, $result['models']);
        $this->assertSame(7, $result['models'][0]['stock']);
    }

    public function test_tiktok_create_checks_listing_persists_publication_and_is_idempotent(): void
    {
        $channel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $store = Store::create([
            'code' => 'TTK-CREATE-TEST',
            'name' => 'TikTok Create Test',
            'channel_id' => $channel->id,
            'status' => 'active',
            'is_active' => true,
        ]);
        $payload = [
            'save_mode' => 'AS_DRAFT',
            'idempotency_key' => '7bb1da2b-58e7-4ef7-a6b0-bcb36b31d53e',
            'title' => 'Jaket Track Baru',
            'description' => '<p>Deskripsi</p>',
            'category_id' => '12345',
            'category_version' => 'v2',
            'listing_platforms' => ['TIKTOK_SHOP'],
            'main_images' => [['uri' => 'tos://main-image']],
            'package_weight' => ['value' => '0.5', 'unit' => 'KILOGRAM'],
            'package_dimensions' => ['length' => '20', 'width' => '15', 'height' => '5', 'unit' => 'CENTIMETER'],
            'skus' => [[
                'seller_sku' => 'JACKET-BLK-M',
                'price' => ['amount' => '125000', 'currency' => 'IDR'],
                'inventory' => [['warehouse_id' => 'warehouse-1', 'quantity' => 8]],
            ]],
        ];

        $driver = Mockery::mock(TikTokShopChannel::class);
        $driver->shouldReceive('checkProductListing')->once()->with(Mockery::type(Store::class), $payload)->andReturn([
            'code' => 0,
            'data' => ['check_result' => 'PASS'],
            'request_id' => 'check-request',
        ]);
        $driver->shouldReceive('addProduct')->once()->with(Mockery::type(Store::class), $payload)->andReturn([
            'code' => 0,
            'data' => ['product_id' => '1729592969712207999', 'skus' => [['id' => '1729592969712208000']]],
            'request_id' => 'create-request',
        ]);
        $driver->shouldReceive('getProduct')->once()->andReturn([
            'code' => 0,
            'data' => [
                'id' => '1729592969712207999',
                'title' => 'Jaket Track Baru',
                'status' => 'DRAFT',
                'categories' => [['id' => '12345']],
                'main_images' => [['uri' => 'tos://main-image', 'url' => 'https://img.test/main.jpg']],
                'skus' => [[
                    'id' => '1729592969712208000',
                    'seller_sku' => 'JACKET-BLK-M',
                    'price' => ['amount' => '125000', 'currency' => 'IDR'],
                    'inventory' => [['warehouse_id' => 'warehouse-1', 'quantity' => 8]],
                ]],
            ],
        ]);
        $manager = Mockery::mock(ChannelManager::class);
        $manager->shouldReceive('driver')->twice()->with(Mockery::type(Store::class))->andReturn($driver);
        $service = new MarketplaceProductService(Mockery::mock(MarketplaceApiGateway::class), $manager);

        $result = $service->createProduct($store, $payload);
        $replay = $service->createProduct($store, $payload);

        $this->assertTrue($result['success']);
        $this->assertTrue($replay['idempotent_replay']);
        $this->assertSame('1729592969712207999', $result['item_id']);
        $this->assertDatabaseHas('marketplace_product_publications', [
            'store_id' => $store->id,
            'idempotency_key' => $payload['idempotency_key'],
            'status' => 'success',
            'platform_product_id' => '1729592969712207999',
        ]);
        $this->assertDatabaseHas('marketplace_products', [
            'store_id' => $store->id,
            'item_id' => '1729592969712207999',
            'item_name' => 'Jaket Track Baru',
            'item_status' => 'UNLIST',
            'stock_total' => 8,
        ]);
    }
}
