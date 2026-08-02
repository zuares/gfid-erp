<?php

namespace Tests\Feature\Console;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\OrderFulfillment;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceRepairStuckOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    private Channel $shopee;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $this->store = Store::create([
            'channel_id' => $this->shopee->id,
            'code' => 'REPAIR-STORE',
            'name' => 'Toko Repair',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'dummy'],
            'token_expires_at' => now()->addDay(),
        ]);

        $legacyChannelId = DB::table('marketplace_channels')->insertGetId([
            'code' => 'shopee',
            'name' => 'Shopee',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_stores')->insert([
            'id' => $this->store->id,
            'channel_id' => $legacyChannelId,
            'name' => $this->store->name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrder(string $orderSn, string $status = 'PROCESSED'): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $this->store->id,
            'external_order_id' => $orderSn,
            'channel_order_id' => $orderSn,
            'order_date' => now(),
            'ordered_at' => now(),
            'order_status' => $status,
        ]);
    }

    public function test_preview_tidak_mengubah_data(): void
    {
        $order = $this->createOrder('REPAIR-PREVIEW');

        $this->artisan('marketplace:repair-stuck-orders')
            ->expectsOutputToContain('[MISSING_FULFILLMENT]')
            ->expectsOutputToContain('Preview selesai')
            ->assertSuccessful();

        $this->assertDatabaseMissing('order_fulfillments', [
            'marketplace_order_id' => $order->id,
        ]);
    }

    public function test_apply_membuat_fulfillment_yang_hilang_dan_memulihkan_handover(): void
    {
        $missing = $this->createOrder('REPAIR-MISSING');
        $handover = $this->createOrder('REPAIR-HANDOVER', 'PROCESSED');

        $fulfillment = OrderFulfillment::create([
            'marketplace_order_id' => $handover->id,
            'status' => OrderFulfillment::STATUS_CONFIRMED,
        ]);

        $this->artisan('marketplace:repair-stuck-orders', ['--apply' => true])
            ->expectsOutputToContain('Repair selesai')
            ->assertSuccessful();

        $this->assertDatabaseHas('order_fulfillments', [
            'marketplace_order_id' => $missing->id,
            'status' => OrderFulfillment::STATUS_PENDING_REVIEW,
        ]);
        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $handover->id,
            'order_status' => 'READY_TO_HANDOVER',
        ]);
        $this->assertDatabaseHas('order_fulfillments', [
            'id' => $fulfillment->id,
            'status' => OrderFulfillment::STATUS_CONFIRMED,
        ]);
    }
}
