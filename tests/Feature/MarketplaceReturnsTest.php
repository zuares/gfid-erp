<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureModuleAccess;
use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceReturn;
use App\Models\MarketplaceReturnItem;
use App\Models\Store;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketplaceReturnsTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            Authenticate::class,
            EnsureModuleAccess::class,
        ]);

        $channel = Channel::create([
            'code' => 'tiktok',
            'name' => 'TikTok Shop',
        ]);

        $this->store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'TTK-RETURNS',
            'name' => 'TikTok Returns Test',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'test-token'],
            'token_expires_at' => now()->addDay(),
        ]);
    }

    private function createOrder(string $orderSn, string $orderStatus, string $legacyStatus): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $this->store->id,
            'external_order_id' => $orderSn,
            'channel_order_id' => $orderSn,
            'order_date' => now(),
            'ordered_at' => now(),
            'order_status' => $orderStatus,
            'status' => $legacyStatus,
            'total_amount' => 99073,
            'meta' => [
                'has_return_request' => true,
                'buyer_dispute_reason' => 'Pengiriman paket gagal (SYSTEM)',
            ],
        ]);
    }

    public function test_cancelled_tiktok_order_is_only_listed_in_cancelled_results(): void
    {
        $order = $this->createOrder('TTK-CANCELLED-001', 'CANCELLED', 'cancelled');

        $this->getJson('/api/marketplace/returns/stored?type=return&search='.$order->channel_order_id)
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/marketplace/returns/stored?type=cancel&search='.$order->channel_order_id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kind', 'cancel')
            ->assertJsonPath('data.0.status', 'CANCELLED');
    }

    public function test_non_cancelled_tiktok_dispute_can_still_appear_as_return(): void
    {
        $order = $this->createOrder('TTK-DISPUTE-001', 'TO_CONFIRM_RECEIVE', 'shipped');

        $this->getJson('/api/marketplace/returns/stored?type=return&search='.$order->channel_order_id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kind', 'return')
            ->assertJsonPath('data.0.status', 'DISPUTE_REQUESTED');
    }

    public function test_tiktok_return_detail_is_available_from_the_return_tab(): void
    {
        $this->store->update([
            'credentials' => [
                'app_key' => 'app-key-test',
                'app_secret' => 'app-secret-test',
                'access_token' => 'access-token-test',
                'shop_cipher' => 'shop-cipher-test',
            ],
        ]);

        MarketplaceReturn::create([
            'store_id' => $this->store->id,
            'return_sn' => 'TTK-RETURN-001',
            'order_sn' => 'TTK-ORDER-001',
            'status' => 'RETURN_REQUEST_PENDING',
            'reason' => 'Barang tidak sesuai',
            'return_solution' => 0,
            'amount_before_discount' => 125000,
            'needs_logistics' => true,
            'tracking_number' => 'JNE-RETURN-001',
            'create_time' => 1700000000,
            'update_time' => 1700000100,
        ]);

        Http::fake([
            'https://open-api.tiktokglobalshop.com/return_refund/202309/returns/TTK-RETURN-001*' => Http::response([
                'code' => 0,
                'data' => [
                    'return_id' => 'TTK-RETURN-001',
                    'order_id' => 'TTK-ORDER-001',
                    'return_status' => 'RETURN_REQUEST_PENDING',
                    'return_reason_text' => 'Barang tidak sesuai',
                    'refund_total' => '125000',
                    'tracking_number' => 'JNE-RETURN-001',
                    'sku_list' => [[
                        'seller_sku' => 'SKU-001',
                        'sku_id' => 'MODEL-001',
                        'product_name' => 'Produk TikTok',
                        'sku_name' => 'Merah',
                        'return_quantity' => 1,
                    ]],
                ],
            ]),
        ]);

        $this->getJson('/api/marketplace/stores/'.$this->store->id.'/returns/TTK-RETURN-001/detail')
            ->assertOk()
            ->assertJsonPath('channel_code', 'TIKTOK')
            ->assertJsonPath('return_sn', 'TTK-RETURN-001')
            ->assertJsonPath('order_sn', 'TTK-ORDER-001')
            ->assertJsonPath('item.0.item_name', 'Produk TikTok')
            ->assertJsonPath('item.0.return_item_quantity', 1);
    }

    public function test_shopee_return_detail_is_available_from_the_return_tab(): void
    {
        $channel = Channel::create([
            'code' => 'shopee',
            'name' => 'Shopee',
        ]);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'SHP-RETURNS',
            'name' => 'Shopee Returns Test',
            'status' => 'active',
            'is_active' => true,
            'credentials' => [
                'partner_id' => '1',
                'partner_key' => 'partner-key-test',
                'shop_id' => '1001',
                'access_token' => 'access-token-test',
            ],
            'token_expires_at' => now()->addDay(),
        ]);

        MarketplaceReturn::create([
            'store_id' => $store->id,
            'return_sn' => 'SHP-RETURN-001',
            'order_sn' => 'SHP-ORDER-001',
            'status' => '返送中',
            'reason' => 'Produk rusak',
            'return_solution' => 0,
            'amount_before_discount' => 88000,
            'needs_logistics' => true,
            'tracking_number' => 'SPX-RETURN-001',
            'create_time' => 1700000000,
            'update_time' => 1700000100,
        ]);

        Http::fake([
            'https://partner.shopeemobile.com/api/v2/returns/get_return_detail*' => Http::response([
                'error' => '',
                'message' => '',
                'response' => [
                    'return_sn' => 'SHP-RETURN-001',
                    'order_sn' => 'SHP-ORDER-001',
                    'return_status' => '返送中',
                    'return_reason' => 'DAMAGED',
                    'return_reason_text' => 'Produk rusak',
                    'return_solution' => 0,
                    'refund_amount' => 88000,
                    'needs_logistics' => true,
                    'tracking_number' => 'SPX-RETURN-001',
                    'item' => [[
                        'item_sku' => 'SHP-SKU-001',
                        'model_sku' => 'SHP-MODEL-001',
                        'item_name' => 'Produk Shopee',
                        'model_name' => 'Hitam',
                        'return_item_quantity' => 2,
                    ]],
                ],
            ]),
        ]);

        $this->getJson('/api/marketplace/stores/'.$store->id.'/returns/SHP-RETURN-001/detail')
            ->assertOk()
            ->assertJsonPath('channel_code', 'SHOPEE')
            ->assertJsonPath('return_sn', 'SHP-RETURN-001')
            ->assertJsonPath('status', '返送中')
            ->assertJsonPath('reason', 'Produk rusak')
            ->assertJsonPath('amount_before_discount', 88000)
            ->assertJsonPath('item.0.item_name', 'Produk Shopee');
    }

    public function test_synthetic_tiktok_dispute_detail_uses_the_store_order(): void
    {
        $order = $this->createOrder('TTK-DISPUTE-DETAIL-001', 'TO_CONFIRM_RECEIVE', 'shipped');

        $this->getJson('/api/marketplace/stores/'.$this->store->id.'/returns/DISPUTE-'.$order->channel_order_id.'/detail')
            ->assertOk()
            ->assertJsonPath('detail_source', 'database')
            ->assertJsonPath('status', 'DISPUTE_REQUESTED')
            ->assertJsonPath('order_sn', $order->channel_order_id)
            ->assertJsonPath('reason', 'Pengiriman paket gagal (SYSTEM)');
    }

    public function test_return_detail_cannot_be_requested_for_an_unknown_return(): void
    {
        $this->getJson('/api/marketplace/stores/'.$this->store->id.'/returns/UNKNOWN-RETURN/detail')
            ->assertNotFound()
            ->assertJsonPath('error', 'Detail retur tidak ditemukan untuk toko ini.');
    }
}
