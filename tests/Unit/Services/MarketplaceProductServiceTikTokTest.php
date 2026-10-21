<?php

namespace Tests\Unit\Services;

use App\Models\Channel;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductModel;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\MarketplaceProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MarketplaceProductServiceTikTokTest extends TestCase
{
    use RefreshDatabase;

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
}
