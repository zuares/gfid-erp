<?php

namespace Tests\Feature;

use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\MarketplaceFinanceController;
use App\Models\Channel;
use App\Models\MarketplaceChannel;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceSyncLog;
use App\Models\Store;
use App\Models\User;
use App\Services\Channels\Shopee\ShopeeChannel;
use App\Services\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceSettlementSyncControllerTest extends TestCase
{
    use RefreshDatabase;

    private Channel $shopee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $this->withoutMiddleware();
    }

    private function createStore(): Store
    {
        $store = Store::create([
            'channel_id' => $this->shopee->id,
            'code' => 'S-' . rand(1000, 9999),
            'name' => 'Test Store',
            'status' => 'active',
            'is_active' => true,
            'external_shop_id' => 'EXT-' . rand(100000, 999999),
            'credentials' => ['access_token' => 'dummy-token'],
            'token_expires_at' => now()->addDay(),
        ]);

        $this->mirrorLegacyMarketplaceStore($store);

        return $store;
    }

    private function mirrorLegacyMarketplaceStore(Store $store): void
    {
        $channelId = DB::table('marketplace_channels')->where('code', $this->shopee->code)->value('id');

        if (! $channelId) {
            $channelId = DB::table('marketplace_channels')->insertGetId([
                'code'       => $this->shopee->code,
                'name'       => $this->shopee->name,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('marketplace_stores')->updateOrInsert(
            ['id' => $store->id],
            [
                'channel_id'        => $channelId,
                'external_store_id' => $store->external_shop_id,
                'name'              => $store->name,
                'short_code'        => 'MKT-' . $store->id,
                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]
        );
    }

    public function test_backfill_tiga_bulan_meneruskan_range_ke_service()
    {
        Carbon::setTestNow(Carbon::parse('2026-07-28 10:00:00', config('app.timezone')));
        $store = $this->createStore();

        $expectedFrom = now()->subMonthsNoOverflow(3)->startOfDay()->timestamp;
        $expectedTo   = now()->endOfDay()->timestamp;

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) use ($store, $expectedFrom, $expectedTo) {
            $mock->shouldReceive('syncSettlementsBackfill')
                ->once()
                ->withArgs(function ($argStore, $timeFrom, $timeTo) use ($store, $expectedFrom, $expectedTo) {
                    return $argStore->id === $store->id
                        && $timeFrom === $expectedFrom
                        && $timeTo === $expectedTo;
                })
                ->andReturn([
                    'found' => 12,
                    'processed' => 12,
                    'synced' => 12,
                    'new' => 12,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => 0,
                    'batches' => 1,
                    'last_processed_id' => 12,
                    'failed_order_ids' => [],
                    'message' => 'ok',
                    'status' => 'success',
                ]);
        });

        $request = Request::create('/api/marketplace/stores/' . $store->id . '/sync-settlements', 'POST', [
            'backfill_months' => 3,
        ]);

        $response = app(MarketplaceController::class)->syncSettlements($request, $store);

        $this->assertSame(200, $response->getStatusCode());

        $payload = $response->getData(true);

        $this->assertSame('backfill', $payload['mode']);
        $this->assertSame(3, $payload['backfill_months']);
        $this->assertSame(12, $payload['synced']);
        $this->assertSame(1, $payload['batches']);
    }

    public function test_backfill_months_di_luar_batas_ditolak()
    {
        $store = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('syncSettlementsBackfill');
            $mock->shouldNotReceive('syncSettlements');
        });

        $request = Request::create('/api/marketplace/stores/' . $store->id . '/sync-settlements', 'POST', [
            'backfill_months' => 4,
        ]);

        $response = app(MarketplaceController::class)->syncSettlements($request, $store);

        $this->assertSame(422, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertSame('backfill_months hanya boleh 1, 2, atau 3.', $payload['message']);
    }

    public function test_owner_bisa_menghapus_semua_data_settlement()
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWN']);
        $store = $this->createStore();
        MarketplaceOrderSettlement::create(['channel_order_id' => 'SN-001']);
        MarketplaceOrderSettlement::create(['channel_order_id' => 'SN-002']);
        MarketplaceSyncLog::create([
            'store_id' => $store->id,
            'action' => 'sync_settlements',
            'status' => 'success',
            'message' => 'Settlement log',
            'payload' => ['found' => 2],
        ]);
        MarketplaceSyncLog::create([
            'store_id' => $store->id,
            'action' => 'sync_orders',
            'status' => 'success',
            'message' => 'Order log',
            'payload' => ['found' => 2],
        ]);
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => 'ORD-001',
            'external_order_id' => 'ORD-001',
            'order_date' => now(),
            'status' => 'completed',
            'order_status' => 'COMPLETED',
            'buyer_username' => 'test-buyer',
            'payment_method' => 'COD',
            'shipping_carrier' => 'J&T',
            'total_amount' => 10000,
            'currency' => 'IDR',
            'ordered_at' => now(),
            'synced_at' => now(),
            'raw_json' => [],
            'settlement_sync_error_code' => 'order_not_found',
            'settlement_sync_failed_at' => now(),
        ]);
        Cache::put('marketplace:settlement_sync_progress:' . $store->id, [
            'status' => 'queued',
            'percent' => 2,
            'label' => 'Settlement sync untuk Greatfit.id sedang antre…',
            'store_id' => $store->id,
            'store_name' => $store->name,
        ], 1800);

        $this->actingAs($owner)
            ->postJson('/api/marketplace/settlements/purge', [
                'confirm' => 'HAPUS SEMUA SETTLEMENT',
            ])
            ->assertOk()
            ->assertJsonPath('deleted', 2);

        $this->assertSame(0, MarketplaceOrderSettlement::count());
        $this->assertNull(Cache::get('marketplace:settlement_sync_progress:' . $store->id));
        $this->assertSame(0, MarketplaceSyncLog::where('action', 'sync_settlements')->count());
        $this->assertSame(1, MarketplaceSyncLog::where('action', 'sync_orders')->count());
        $this->assertNull(\DB::table('marketplace_orders')->where('channel_order_id', 'ORD-001')->value('settlement_sync_error_code'));
        $this->assertNull(\DB::table('marketplace_orders')->where('channel_order_id', 'ORD-001')->value('settlement_sync_failed_at'));
    }

    public function test_owner_bisa_menghapus_semua_data_marketplace_untuk_toko_ini_tanpa_menghapus_order_utama()
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWN']);
        $store = $this->createStore();
        $otherStore = $this->createStore();

        MarketplaceSyncLog::create([
            'store_id' => $store->id,
            'action' => 'sync_orders',
            'status' => 'success',
            'message' => 'Protected order log',
            'payload' => ['keep' => true],
        ]);
        MarketplaceSyncLog::create([
            'store_id' => $store->id,
            'action' => 'sync_finance',
            'status' => 'success',
            'message' => 'Protected finance log',
            'payload' => ['keep' => true],
        ]);
        MarketplaceSyncLog::create([
            'store_id' => $store->id,
            'action' => 'sync_settlements',
            'status' => 'success',
            'message' => 'Delete me',
            'payload' => ['keep' => false],
        ]);
        MarketplaceSyncLog::create([
            'store_id' => $otherStore->id,
            'action' => 'sync_ads',
            'status' => 'success',
            'message' => 'Other store must stay',
            'payload' => [],
        ]);

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-ORDER-001',
            'channel_order_id' => 'ORDER-001',
            'order_date' => now(),
            'order_status' => 'COMPLETED',
            'ordered_at' => now(),
            'buyer_username' => 'buyer-a',
            'payment_method' => 'COD',
            'shipping_carrier' => 'J&T',
            'total_amount' => 150000,
            'currency' => 'IDR',
            'synced_at' => now(),
            'raw_json' => [],
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $otherStore->id,
            'channel_order_id' => 'ORDER-OTHER',
        ]);

        DB::table('marketplace_order_items')->insert([
            'order_id' => $order->id,
            'line_no' => 1,
            'external_item_id' => 'ITEM-1',
            'external_sku' => 'SKU-1',
            'qty' => 2,
            'price_original' => 50000,
            'price_after_discount' => 45000,
            'line_discount' => 5000,
            'line_gross_amount' => 100000,
            'line_net_amount' => 90000,
            'hpp_unit_snapshot' => 20000,
            'hpp_total_snapshot' => 40000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_fulfillments')->insert([
            'marketplace_order_id' => $order->id,
            'status' => 'confirmed',
            'notes' => 'Keep order, delete fulfillment',
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $conversationId = DB::table('marketplace_conversations')->insertGetId([
            'store_id' => $store->id,
            'conversation_id' => 'CONV-001',
            'buyer_username' => 'buyer-a',
            'last_message_type' => 'text',
            'last_message_text' => 'Halo',
            'last_message_at' => now(),
            'unread_count' => 1,
            'is_answered' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_chat_messages')->insert([
            'marketplace_conversation_id' => $conversationId,
            'store_id' => $store->id,
            'external_message_id' => 'MSG-001',
            'from_role' => 'buyer',
            'message_type' => 'text',
            'text' => 'Halo',
            'sent_at' => now(),
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $returnId = DB::table('marketplace_returns')->insertGetId([
            'store_id' => $store->id,
            'return_sn' => 'RET-001',
            'order_sn' => 'ORDER-001',
            'status' => 'RETURNED',
            'reason' => 'Damaged',
            'amount_before_discount' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_return_items')->insert([
            'marketplace_return_id' => $returnId,
            'item_name' => 'Item A',
            'variation_name' => 'Default',
            'return_item_quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_bookings')->insert([
            'store_id' => $store->id,
            'booking_sn' => 'BOOK-001',
            'order_sn' => 'ORDER-001',
            'booking_status' => 'PENDING',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('marketplace_products')->insertGetId([
            'store_id' => $store->id,
            'item_id' => 'ITEM-001',
            'item_name' => 'Product A',
            'item_sku' => 'SKU-001',
            'item_status' => 'NORMAL',
            'stock_total' => 10,
            'has_model' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_product_models')->insert([
            'marketplace_product_id' => $productId,
            'model_id' => 'MODEL-001',
            'model_name' => 'Default',
            'stock' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_product_dailies')->insert([
            'store_id' => $store->id,
            'marketplace_product_id' => $productId,
            'date' => now()->toDateString(),
            'item_status' => 'NORMAL',
            'stock_total' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $campaignId = DB::table('marketplace_ad_campaigns')->insertGetId([
            'store_id' => $store->id,
            'channel_campaign_id' => 'CAMP-001',
            'campaign_name' => 'Campaign A',
            'campaign_type' => 'SEARCH_ADS',
            'status' => 'ONGOING',
            'spend' => 15000,
            'impressions' => 100,
            'clicks' => 5,
            'gmv' => 50000,
            'synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_ads_campaign_items')->insert([
            'campaign_id' => $campaignId,
            'channel_item_id' => 'ITEM-001',
            'product_name' => 'Product A',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_ad_campaign_dailies')->insert([
            'store_id' => $store->id,
            'channel_campaign_id' => 'CAMP-001',
            'date' => now()->toDateString(),
            'ad_type' => 'product',
            'impressions' => 100,
            'clicks' => 5,
            'expense' => 15000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_ads_dailies')->insert([
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'impressions' => 100,
            'clicks' => 5,
            'spend' => 15000,
            'orders' => 1,
            'gmv' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_ads_balance_logs')->insert([
            'store_id' => $store->id,
            'balance' => 250000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_ads_item_dailies')->insert([
            'store_id' => $store->id,
            'channel_campaign_id' => 'CAMP-001',
            'channel_item_id' => 'ITEM-001',
            'date' => now()->toDateString(),
            'impressions' => 50,
            'clicks' => 2,
            'expense' => 7000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_ads_hourly_performances')->insert([
            'store_id' => $store->id,
            'campaign_id' => $campaignId,
            'channel_campaign_id' => 'CAMP-001',
            'performance_date' => now()->toDateString(),
            'performance_hour' => 10,
            'impression' => 50,
            'clicks' => 2,
            'expense' => 7000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_ads_sync_runs')->insert([
            'store_id' => $store->id,
            'sync_type' => 'campaign',
            'status' => 'success',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_ads_settings')->insert([
            'store_id' => $store->id,
            'target_roas' => 2.5,
            'notes' => 'Test settings',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_boost_schedules')->insert([
            'store_id' => $store->id,
            'marketplace_product_id' => $productId,
            'boost_time' => '08:00:00',
            'priority' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_boost_pool')->insert([
            'store_id' => $store->id,
            'marketplace_product_id' => $productId,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('marketplace_boost_logs')->insert([
            'store_id' => $store->id,
            'marketplace_product_id' => $productId,
            'source' => 'manual',
            'success' => true,
            'boosted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('mp_incomes')->insert([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'platform_order_id' => 'ORDER-001',
            'net_payout_actual' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cache::put('marketplace:settlement_sync_progress:' . $store->id, [
            'status' => 'queued',
            'percent' => 2,
            'label' => 'Settlement sync untuk ' . $store->name . ' sedang antre…',
            'store_id' => $store->id,
            'store_name' => $store->name,
        ], 1800);

        Cache::put('marketplace:sync_progress:' . $store->id, [
            'status' => 'running',
            'percent' => 42,
            'label' => 'Sync orders berjalan…',
            'store_id' => $store->id,
            'store_name' => $store->name,
        ], 1800);

        $request = Request::create('/api/marketplace/stores/' . $store->id . '/purge-marketplace-data', 'POST', [
            'confirm' => 'HAPUS SEMUA DATA MARKETPLACE',
        ]);
        $request->setUserResolver(fn () => $owner);

        $response = app(MarketplaceController::class)->purgeMarketplaceData($request, $store);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame($store->id, $payload['store_id'] ?? null);
        $this->assertSame(1, $payload['deleted']['settlements'] ?? null);
        $this->assertSame(1, $payload['deleted']['sync_logs'] ?? null);
        $this->assertSame(1, $payload['deleted']['order_items'] ?? null);
        $this->assertSame(1, $payload['deleted']['fulfillments'] ?? null);
        $this->assertSame(1, $payload['deleted']['products'] ?? null);
        $this->assertSame(1, $payload['deleted']['ad_campaigns'] ?? null);
        $this->assertSame(1, $payload['deleted']['boost_logs'] ?? null);
        $this->assertSame(1, $payload['deleted']['mp_incomes'] ?? null);

        $this->assertSame(1, MarketplaceOrder::count(), 'Order utama harus tetap ada.');
        $this->assertSame(0, DB::table('marketplace_order_items')->count());
        $this->assertSame(0, DB::table('order_fulfillments')->count());
        $this->assertSame(0, MarketplaceOrderSettlement::where('store_id', $store->id)->count());
        $this->assertSame(0, MarketplaceSyncLog::where('store_id', $store->id)->whereNotIn('action', ['sync_orders', 'sync_finance'])->count());
        $this->assertSame(2, MarketplaceSyncLog::where('store_id', $store->id)->whereIn('action', ['sync_orders', 'sync_finance'])->count());
        $this->assertSame(1, MarketplaceSyncLog::where('store_id', $otherStore->id)->count());
        $this->assertSame(0, DB::table('marketplace_chat_messages')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_conversations')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_returns')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_return_items')->count());
        $this->assertSame(0, DB::table('marketplace_bookings')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_products')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_product_models')->count());
        $this->assertSame(0, DB::table('marketplace_product_dailies')->count());
        $this->assertSame(0, DB::table('marketplace_ads_dailies')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_ads_balance_logs')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_ads_item_dailies')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_ads_hourly_performances')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_ads_sync_runs')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_ads_settings')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_ad_campaigns')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_ads_campaign_items')->count());
        $this->assertSame(0, DB::table('marketplace_ad_campaign_dailies')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_boost_schedules')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_boost_pool')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('marketplace_boost_logs')->where('store_id', $store->id)->count());
        $this->assertSame(0, DB::table('mp_incomes')->where('store_id', $store->id)->count());
        $this->assertNull(Cache::get('marketplace:settlement_sync_progress:' . $store->id));
        $this->assertNull(Cache::get('marketplace:sync_progress:' . $store->id));
        $this->assertSame(1, MarketplaceOrderSettlement::where('store_id', $otherStore->id)->count());
    }

    public function test_non_owner_tidak_bisa_menghapus_data_settlement()
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM']);

        MarketplaceOrderSettlement::create(['channel_order_id' => 'SN-003']);
        MarketplaceOrderSettlement::create(['channel_order_id' => 'SN-004']);

        $this->actingAs($admin)
            ->postJson('/api/marketplace/settlements/purge', [
                'confirm' => 'HAPUS SEMUA SETTLEMENT',
            ])
            ->assertStatus(403);

        $this->assertSame(2, MarketplaceOrderSettlement::count());
    }

    public function test_halaman_rincian_penghasilan_bisa_dibuka()
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM-DETAIL']);

        $this->actingAs($admin)
            ->get(route('marketplace.income-detail'))
            ->assertStatus(200)
            ->assertSee('Rincian Penghasilan')
            ->assertSee('Tgl Order')
            ->assertSee('Tgl Cair')
            ->assertSee('Komisi Affiliate')
            ->assertSee('Rincian');
    }

    public function test_detail_order_menampilkan_estimasi_bila_income_belum_ada()
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM-ORDER']);
        $store = $this->createStore();

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => 'ORDER-EST-001',
            'external_order_id' => 'ORDER-EST-001',
            'order_status' => 'READY_TO_SHIP',
            'status' => 'new',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 100000,
            'total_paid_customer' => 112000,
            'voucher_discount' => 5000,
            'other_discount' => 0,
            'platform_fee_total' => 0,
            'net_payout_estimated' => 0,
            'raw_json' => [],
        ]);

        $this->actingAs($admin)
            ->get(route('marketplace.orders.show', $order))
            ->assertStatus(200)
            ->assertSee('Estimasi Penghasilan Akhir')
            ->assertSee('Berdasarkan rata-rata fee historis toko ini')
            ->assertSee('Estimasi Fee Marketplace');
    }

    public function test_detail_order_menampilkan_rincian_fee_platform_dinamis()
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM-FEE']);
        $store = $this->createStore();

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => 'ORDER-FEE-001',
            'external_order_id' => 'ORDER-FEE-001',
            'order_status' => 'COMPLETED',
            'status' => 'completed',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 100000,
            'total_paid_customer' => 112000,
            'voucher_discount' => 5000,
            'other_discount' => 0,
            'platform_fee_total' => 0,
            'net_payout_estimated' => 0,
            'payment_method' => 'COD',
            'shipping_carrier' => 'J&T',
            'total_amount' => 112000,
            'currency' => 'IDR',
            'synced_at' => now(),
            'raw_json' => [],
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'commission_fee' => 8081,
            'service_fee' => 9306,
            'transaction_fee' => 0,
            'affiliate_fee' => 0,
            'seller_voucher' => 0,
            'seller_coin_cash_back' => 0,
            'actual_shipping_fee' => 0,
            'shipping_fee_subsidy' => 0,
            'reverse_shipping_fee' => 0,
            'shipping_insurance_fee' => 0,
            'activity_fee' => 4754,
            'drc_adjustable_refund' => 0,
            'escrow_tax' => 0,
            'ad_cost' => 0,
            'final_income' => 0,
            'raw_json' => [
                'commission_fee' => 8081,
                'service_fee' => 9306,
                'seller_order_processing_fee' => 1250,
                'order_ams_commission_fee' => 4754,
            ],
        ]);

        $html = view('marketplace.orders.show', [
            'order' => $order->load(['store.channel', 'settlement']),
            'estimatedFeeRatio' => 0.15,
            'estimatedFeePct' => 15,
        ])->render();

        $this->assertStringContainsString('Rincian Biaya Marketplace', $html);
        $this->assertStringContainsString('Biaya Administrasi', $html);
        $this->assertStringContainsString('Biaya Layanan', $html);
        $this->assertStringContainsString('Biaya Proses Pesanan', $html);
        $this->assertStringContainsString('Biaya Komisi AMS', $html);
        $this->assertStringContainsString('Rp8.081', $html);
        $this->assertStringContainsString('Rp9.306', $html);
        $this->assertStringContainsString('Rp1.250', $html);
        $this->assertStringContainsString('Rp4.754', $html);
    }

    public function test_detail_order_menampilkan_voucher_platform_dan_toko_dari_field_spesifik()
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM-VOUCHER']);
        $store = $this->createStore();

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'channel_order_id' => 'ORDER-VOUCHER-001',
            'external_order_id' => 'ORDER-VOUCHER-001',
            'order_status' => 'COMPLETED',
            'status' => 'completed',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 97950,
            'total_paid_customer' => 79872,
            'voucher_discount' => 99999,
            'other_discount' => 99999,
            'platform_fee_total' => 2000,
            'net_payout_estimated' => 0,
            'payment_method' => 'COD',
            'shipping_carrier' => 'J&T',
            'total_amount' => 79872,
            'currency' => 'IDR',
            'synced_at' => now(),
            'raw_json' => [
                'order_sn' => 'ORDER-VOUCHER-001',
                'income_details' => [
                    'buyer_total_amount' => 79872,
                    'voucher_from_shopee' => 4099,
                    'voucher_from_seller' => 15979,
                    'buyer_paid_shipping_fee' => 0,
                    'buyer_transaction_fee' => 2000,
                    'coin' => 0,
                    'order_discounted_price' => 97950,
                ],
            ],
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'seller_voucher' => 15979,
            'buyer_payment_amount' => 79872,
            'commission_fee' => 0,
            'service_fee' => 0,
            'transaction_fee' => 0,
            'affiliate_fee' => 0,
            'seller_coin_cash_back' => 0,
            'actual_shipping_fee' => 0,
            'shipping_fee_subsidy' => 0,
            'reverse_shipping_fee' => 0,
            'shipping_insurance_fee' => 0,
            'activity_fee' => 0,
            'drc_adjustable_refund' => 0,
            'escrow_tax' => 0,
            'ad_cost' => 0,
            'final_income' => 0,
            'raw_json' => [
                'voucher_from_seller' => 15979,
            ],
        ]);

        $html = view('marketplace.orders.show', [
            'order' => $order->load(['store.channel', 'settlement']),
            'estimatedFeeRatio' => 0.15,
            'estimatedFeePct' => 15,
        ])->render();

        $this->assertStringContainsString('Voucher Platform', $html);
        $this->assertStringContainsString('Rp4.099', $html);
        $this->assertStringContainsString('Voucher Toko', $html);
        $this->assertStringContainsString('Rp15.979', $html);
        $this->assertStringContainsString('Total Pembayaran Pembeli', $html);
        $this->assertStringContainsString('Rp79.872', $html);
    }

    public function test_background_sync_seeds_progress_cache_and_returns_queued()
    {
        $store = $this->createStore();

        Artisan::shouldReceive('queue')
            ->once()
            ->andReturn(new class {
                public function onQueue($queue)
                {
                    return $this;
                }
            });

        $request = Request::create('/api/marketplace/stores/' . $store->id . '/sync-settlements-background', 'POST');

        $response = app(MarketplaceController::class)->syncSettlementsBackground($request, $store);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('queued', $payload['status']);
        $this->assertSame('regular', $payload['mode']);

        $progress = Cache::get('marketplace:settlement_sync_progress:' . $store->id);
        $this->assertSame('queued', $progress['status'] ?? null);
        $this->assertSame('regular', $progress['mode'] ?? null);
    }

    public function test_settlement_sync_menolak_channel_non_shopee_sebelum_membuat_progress()
    {
        $tiktok = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = Store::create([
            'channel_id' => $tiktok->id,
            'code' => 'TT-' . rand(1000, 9999),
            'name' => 'TikTok Test Store',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'dummy-token'],
            'token_expires_at' => now()->addDay(),
        ]);

        $request = Request::create('/api/marketplace/stores/' . $store->id . '/sync-settlements-background', 'POST');
        $response = app(MarketplaceController::class)->syncSettlementsBackground($request, $store);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('SETTLEMENT_CHANNEL_UNSUPPORTED', $response->getData(true)['code']);
        $this->assertNull(Cache::get('marketplace:settlement_sync_progress:' . $store->id));
    }

    public function test_settlement_sync_menolak_toko_nonaktif_sebelum_membuat_progress()
    {
        $store = $this->createStore();
        $store->update(['is_active' => false]);

        $request = Request::create('/api/marketplace/stores/' . $store->id . '/sync-settlements-background', 'POST');
        $response = app(MarketplaceController::class)->syncSettlementsBackground($request, $store->fresh());

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('STORE_INACTIVE', $response->getData(true)['code']);
        $this->assertNull(Cache::get('marketplace:settlement_sync_progress:' . $store->id));
    }

    public function test_sync_settlement_reguler_menyimpan_status_terminal_di_progress()
    {
        $store = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('syncSettlements')
                ->once()
                ->andReturn([
                    'found' => 1,
                    'processed' => 1,
                    'synced' => 0,
                    'new' => 0,
                    'updated' => 0,
                    'skipped' => 1,
                    'errors' => 0,
                    'last_processed_id' => 1,
                    'failed_order_ids' => [],
                    'message' => 'skipped',
                ]);
        });

        $request = Request::create('/api/marketplace/stores/' . $store->id . '/sync-settlements', 'POST');
        $response = app(MarketplaceController::class)->syncSettlements($request, $store);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('warn', Cache::get('marketplace:settlement_sync_progress:' . $store->id)['status']);
    }

    public function test_riwayat_settlement_hanya_mengembalikan_log_toko_yang_diminta()
    {
        $store = $this->createStore();
        $otherStore = $this->createStore();

        MarketplaceSyncLog::create([
            'store_id' => $store->id,
            'action' => 'sync_settlements',
            'status' => 'success',
            'message' => 'Store A',
            'payload' => [],
        ]);
        MarketplaceSyncLog::create([
            'store_id' => $otherStore->id,
            'action' => 'sync_settlements',
            'status' => 'success',
            'message' => 'Store B',
            'payload' => [],
        ]);

        $request = Request::create('/api/marketplace/sync-logs', 'GET', [
            'action' => 'sync_settlements',
            'store_id' => $store->id,
        ]);
        $response = app(MarketplaceController::class)->syncLogs($request);

        $payload = $response->getData(true);
        $this->assertCount(1, $payload);
        $this->assertSame('Store A', $payload[0]['message']);

        $emptyRequest = Request::create('/api/marketplace/sync-logs', 'GET', [
            'action' => 'sync_settlements',
        ]);
        $this->assertSame([], app(MarketplaceController::class)->syncLogs($emptyRequest)->getData(true));
    }

    public function test_backfill_settlement_background_menggunakan_mode_backfill()
    {
        $store = $this->createStore();

        Artisan::shouldReceive('queue')
            ->once()
            ->andReturn(new class {
                public function onQueue($queue)
                {
                    return $this;
                }
            });

        $request = Request::create('/api/marketplace/stores/' . $store->id . '/sync-settlements-background', 'POST', [
            'backfill_months' => 2,
        ]);

        $response = app(MarketplaceController::class)->syncSettlementsBackground($request, $store);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('queued', $payload['status']);
        $this->assertSame('backfill', $payload['mode']);
        $this->assertSame('marketplace:settlement_sync_progress:' . $store->id, $payload['progress_key'] ?? null);

        $progress = Cache::get('marketplace:settlement_sync_progress:' . $store->id);
        $this->assertSame('queued', $progress['status'] ?? null);
        $this->assertSame('backfill', $progress['mode'] ?? null);
        $this->assertSame(2, $progress['backfill_months'] ?? null);
    }

    public function test_background_sync_tidak_memaksa_refresh_token_di_request_path()
    {
        $store = $this->createStore();
        $store->update([
            'token_expires_at' => now()->subMinute(),
        ]);

        $shopee = \Mockery::mock(ShopeeChannel::class);
        $shopee->shouldNotReceive('refreshToken');
        app()->instance(ShopeeChannel::class, $shopee);

        Artisan::shouldReceive('queue')
            ->once()
            ->andReturn(new class {
                public function onQueue($queue)
                {
                    return $this;
                }
            });

        $request = Request::create('/api/marketplace/stores/' . $store->id . '/sync-settlements-background', 'POST');

        $response = app(MarketplaceController::class)->syncSettlementsBackground($request, $store);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('queued', $payload['status']);
    }

    public function test_settlement_progress_endpoint_returns_cached_state()
    {
        $store = $this->createStore();

        Cache::put('marketplace:settlement_sync_progress:' . $store->id, [
            'status' => 'running',
            'percent' => 42,
            'label' => 'Memproses settlement 42%',
            'store_id' => $store->id,
            'store_name' => $store->name,
        ], 1800);

        $response = app(MarketplaceController::class)->syncSettlementsProgress($store);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $this->assertSame('running', $payload['status']);
        $this->assertSame(42, $payload['percent']);
    }

    public function test_settlements_endpoint_mengembalikan_persentase_fee()
    {
        $store = $this->createStore();

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-FEE-PCT',
            'channel_order_id' => 'ORDER-FEE-PCT',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 100000,
            'commission_fee' => 5000,
            'service_fee' => 6000,
            'transaction_fee' => 3500,
            'affiliate_fee' => 425,
            'seller_voucher' => 10000,
            'seller_coin_cash_back' => 0,
            'actual_shipping_fee' => 0,
            'shipping_fee_subsidy' => 0,
            'reverse_shipping_fee' => 0,
            'shipping_insurance_fee' => 1200,
            'activity_fee' => 300,
            'drc_adjustable_refund' => 0,
            'escrow_tax' => 0,
            'ad_cost' => 0,
            'final_income' => 83675,
            'synced_at' => now(),
        ]);

        $request = Request::create('/api/marketplace/settlements', 'GET', [
            'store_id' => $store->id,
            'page' => 1,
            'per_page' => 50,
        ]);

        $response = app(MarketplaceController::class)->settlements($request);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);

        $this->assertEquals(16425.0, (float) $payload['paginator']['data'][0]['fee_total']);
        $this->assertEquals(90000.0, (float) $payload['paginator']['data'][0]['gross_after_voucher']);
        $this->assertEquals(18.3, (float) $payload['paginator']['data'][0]['fee_percent']);
        $this->assertEquals(18.3, (float) $payload['meta']['kpi_fee_pct']);
    }

    public function test_settlements_endpoint_mengembalikan_rincian_potongan_dinamis()
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM-BREAKDOWN']);
        $store = $this->createStore();

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'channel_order_id' => 'ORDER-BD-001',
            'buyer_payment_amount' => 112000,
            'commission_fee' => 8081,
            'service_fee' => 9306,
            'transaction_fee' => 1250,
            'affiliate_fee' => 0,
            'seller_voucher' => 5000,
            'seller_coin_cash_back' => 0,
            'actual_shipping_fee' => 0,
            'shipping_fee_subsidy' => 0,
            'reverse_shipping_fee' => 0,
            'shipping_insurance_fee' => 0,
            'activity_fee' => 4754,
            'drc_adjustable_refund' => 0,
            'escrow_tax' => 0,
            'ad_cost' => 0,
            'final_income' => 0,
            'synced_at' => now(),
            'raw_json' => [
                'seller_order_processing_fee' => 1250,
                'order_ams_commission_fee' => 4754,
            ],
        ]);

        $request = Request::create('/api/marketplace/settlements', 'GET', [
            'store_id' => $store->id,
            'page' => 1,
            'per_page' => 50,
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = app(MarketplaceController::class)->settlements($request);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $row = $payload['paginator']['data'][0] ?? [];
        $labels = array_column($row['fee_breakdown'] ?? [], 'label');
        $categories = array_column($row['fee_breakdown'] ?? [], 'category');

        $this->assertSame(1250.0, (float) ($row['seller_order_processing_fee'] ?? -1));
        $this->assertSame(4754.0, (float) ($row['order_ams_commission_fee'] ?? -1));
        $this->assertSame(4754.0, (float) ($row['affiliate_display'] ?? -1));
        $this->assertSame(4.4, (float) ($row['affiliate_percent'] ?? -1));
        $this->assertSame(19887.0, (float) ($row['marketplace_fee_after_affiliate'] ?? -1));
        $this->assertSame(18.6, (float) ($row['marketplace_fee_percent'] ?? -1));
        $this->assertContains('Voucher Toko', $labels);
        $this->assertContains('voucher', $categories);
        $this->assertSame(5000.0, (float) ($row['voucher_total'] ?? -1));
        $this->assertSame(0.0, (float) ($row['platform_burden_total'] ?? -1));
        $this->assertSame(29641.0, (float) ($row['total_burden_total'] ?? -1));
    }

    public function test_settlements_endpoint_mengurangkan_voucher_platform_dan_toko_dari_gross_setelah_voucher()
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM-VOUCHER-GROSS']);
        $store = $this->createStore();

        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-VOUCHER-GROSS',
            'channel_order_id' => 'ORDER-VOUCHER-GROSS',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 100000,
            'total_paid_customer' => 80000,
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'channel_order_id' => 'ORDER-VOUCHER-GROSS',
            'buyer_payment_amount' => 80000,
            'commission_fee' => 0,
            'service_fee' => 0,
            'transaction_fee' => 0,
            'affiliate_fee' => 0,
            'seller_voucher' => 10000,
            'seller_coin_cash_back' => 0,
            'actual_shipping_fee' => 0,
            'shipping_fee_subsidy' => 0,
            'reverse_shipping_fee' => 0,
            'shipping_insurance_fee' => 0,
            'activity_fee' => 0,
            'drc_adjustable_refund' => 0,
            'escrow_tax' => 0,
            'ad_cost' => 0,
            'final_income' => 0,
            'synced_at' => now(),
            'raw_json' => [
                'voucher_from_shopee' => 4099,
                'voucher_from_seller' => 10000,
            ],
        ]);

        $request = Request::create('/api/marketplace/settlements', 'GET', [
            'store_id' => $store->id,
            'page' => 1,
            'per_page' => 50,
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = app(MarketplaceController::class)->settlements($request);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $row = $payload['paginator']['data'][0] ?? [];

        $this->assertSame(4099.0, (float) ($row['voucher_platform_total'] ?? -1));
        $this->assertSame(10000.0, (float) ($row['voucher_toko_total'] ?? -1));
        $this->assertSame(14099.0, (float) ($row['voucher_total'] ?? -1));
        $this->assertSame(65901.0, (float) ($row['gross_after_voucher'] ?? -1));
        $this->assertSame(70000.0, (float) ($row['gross_after_voucher_toko'] ?? -1));
    }

    public function test_owner_bisa_mengisi_data_testing_settlement_di_order_detail()
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWN-TEST-SETTLEMENT']);
        $store = $this->createStore();

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-TEST-SETTLEMENT',
            'channel_order_id' => 'ORDER-TEST-SETTLEMENT',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 97950,
            'total_paid_customer' => 79872,
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 97950,
            'commission_fee' => 8081,
            'service_fee' => 9306,
            'transaction_fee' => 1250,
            'affiliate_fee' => 0,
            'seller_voucher' => 0,
            'seller_coin_cash_back' => 0,
            'actual_shipping_fee' => 0,
            'shipping_fee_subsidy' => 0,
            'reverse_shipping_fee' => 0,
            'shipping_insurance_fee' => 0,
            'activity_fee' => 0,
            'drc_adjustable_refund' => 0,
            'escrow_tax' => 0,
            'ad_cost' => 0,
            'final_income' => 74559,
            'synced_at' => now(),
            'raw_json' => [
                'voucher_from_shopee' => 0,
                'voucher_from_seller' => 0,
            ],
        ]);

        $request = Request::create('/marketplace/orders/' . $order->id . '/test-settlement-fields', 'POST', [
            'order_ams_commission_fee' => 4754,
            'voucher_from_shopee' => 20078,
            'voucher_from_seller' => 0,
        ], [], [], [
            'HTTP_REFERER' => route('marketplace.orders.show', $order, false),
        ]);
        $request->setUserResolver(fn () => $owner);

        $response = app(\App\Http\Controllers\Marketplace\MarketplaceOrderController::class)
            ->updateSettlementTestFields($request, $order);

        $this->assertSame(302, $response->getStatusCode());

        $settlement = MarketplaceOrderSettlement::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('4754.00', (string) $settlement->activity_fee);
        $this->assertSame('0.00', (string) $settlement->seller_voucher);
        $this->assertSame(4754.0, (float) ($settlement->raw_json['order_ams_commission_fee'] ?? -1));
        $this->assertSame(20078.0, (float) ($settlement->raw_json['voucher_from_shopee'] ?? -1));
        $this->assertSame(0.0, (float) ($settlement->raw_json['voucher_from_seller'] ?? -1));
    }

    public function test_settlements_endpoint_mengecualikan_biaya_logistik_dan_buyer_side_dari_potongan_seller()
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM-NOLOGI']);
        $store = $this->createStore();

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'channel_order_id' => 'ORDER-NOLOGI-001',
            'buyer_payment_amount' => 112000,
            'commission_fee' => 8081,
            'service_fee' => 9306,
            'transaction_fee' => 0,
            'affiliate_fee' => 0,
            'seller_voucher' => 5000,
            'seller_coin_cash_back' => 0,
            'actual_shipping_fee' => 11000,
            'shipping_fee_subsidy' => 6000,
            'reverse_shipping_fee' => 0,
            'shipping_insurance_fee' => 0,
            'activity_fee' => 4754,
            'drc_adjustable_refund' => 0,
            'escrow_tax' => 0,
            'ad_cost' => 0,
            'final_income' => 0,
            'synced_at' => now(),
            'raw_json' => [
                'seller_order_processing_fee' => 1250,
                'order_ams_commission_fee' => 4754,
                'actual_shipping_fee' => 11000,
                'estimated_shipping_fee' => 16000,
                'credit_card_transaction_fee' => 1000,
                'shipping_fee_subsidy' => 6000,
            ],
        ]);

        $request = Request::create('/api/marketplace/settlements', 'GET', [
            'store_id' => $store->id,
            'page' => 1,
            'per_page' => 50,
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = app(MarketplaceController::class)->settlements($request);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);
        $row = $payload['paginator']['data'][0] ?? [];
        $labels = array_column($row['fee_breakdown'] ?? [], 'label');
        $categories = array_column($row['fee_breakdown'] ?? [], 'category');

        $this->assertSame(23391.0, (float) ($row['fee_total'] ?? -1));
        $this->assertSame(23391.0, (float) ($row['seller_burden_total'] ?? -1));
        $this->assertSame(12000.0, (float) ($row['buyer_burden_total'] ?? -1));
        $this->assertSame(6000.0, (float) ($row['platform_burden_total'] ?? -1));
        $this->assertSame(5000.0, (float) ($row['voucher_total'] ?? -1));
        $this->assertSame(46391.0, (float) ($row['total_burden_total'] ?? -1));
        $this->assertContains('Biaya Proses Pesanan', $labels);
        $this->assertContains('Biaya Komisi AMS', $labels);
        $this->assertContains('Ongkos Kirim Dibayar Pembeli', $labels);
        $this->assertContains('Biaya Pembayaran Pembeli', $labels);
        $this->assertContains('Subsidi Ongkir Platform', $labels);
        $this->assertContains('Voucher Toko', $labels);
        $this->assertContains('voucher', $categories);
        $this->assertContains('buyer', $categories);
        $this->assertContains('platform', $categories);
    }

    public function test_settlements_endpoint_mendukung_pencarian_nama_produk_dan_sku_item()
    {
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM-SEARCH']);
        $store = $this->createStore();

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-SEARCH-001',
            'channel_order_id' => 'ORDER-SEARCH-001',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 120000,
            'total_paid_customer' => 120000,
        ]);

        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'item_name' => 'Kemeja Linen Navy',
            'variant_name' => 'XL',
            'item_sku' => 'SKU-LINEN-001',
            'model_sku' => 'MODEL-LINEN',
            'qty' => 1,
            'price' => 120000,
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 120000,
            'commission_fee' => 0,
            'service_fee' => 0,
            'transaction_fee' => 0,
            'affiliate_fee' => 0,
            'seller_voucher' => 0,
            'seller_coin_cash_back' => 0,
            'actual_shipping_fee' => 0,
            'shipping_fee_subsidy' => 0,
            'reverse_shipping_fee' => 0,
            'shipping_insurance_fee' => 0,
            'activity_fee' => 0,
            'drc_adjustable_refund' => 0,
            'escrow_tax' => 0,
            'ad_cost' => 0,
            'final_income' => 120000,
            'synced_at' => now(),
        ]);

        $request = Request::create('/api/marketplace/settlements', 'GET', [
            'store_id' => $store->id,
            'search' => 'linen',
            'page' => 1,
            'per_page' => 50,
        ]);
        $request->setUserResolver(fn () => $admin);

        $response = app(MarketplaceController::class)->settlements($request);

        $this->assertSame(200, $response->getStatusCode());
        $payload = $response->getData(true);

        $this->assertSame(1, (int) $payload['paginator']['total']);
        $this->assertSame('ORDER-SEARCH-001', $payload['paginator']['data'][0]['channel_order_id']);
    }

    public function test_filter_belum_cair_mencakup_record_pending_dan_order_tanpa_record_settlement()
    {
        $store = $this->createStore();

        $pendingOrder = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-PENDING-SETTLEMENT',
            'channel_order_id' => 'ORDER-PENDING-SETTLEMENT',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDays(2),
            'order_date' => now()->subDays(2),
            'subtotal_items' => 125000,
            'total_paid_customer' => 125000,
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $pendingOrder->id,
            'channel_order_id' => $pendingOrder->channel_order_id,
            'buyer_payment_amount' => 125000,
            'final_income' => 0,
            'settlement_time' => null,
        ]);

        $missingOrder = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-MISSING-SETTLEMENT',
            'channel_order_id' => 'ORDER-MISSING-SETTLEMENT',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 99000,
            'total_paid_customer' => 99000,
        ]);

        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-CANCELLED-NO-SETTLEMENT',
            'channel_order_id' => 'ORDER-CANCELLED-NO-SETTLEMENT',
            'order_status' => 'CANCELLED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 50000,
            'total_paid_customer' => 50000,
        ]);

        $request = Request::create('/api/marketplace/settlements', 'GET', [
            'store_id' => $store->id,
            'settlement_status' => 'belum_cair',
            'status' => 'COMPLETED',
            'page' => 1,
            'per_page' => 50,
        ]);

        $response = app(MarketplaceController::class)->settlements($request);
        $payload = $response->getData(true);
        $rows = $payload['paginator']['data'];

        $this->assertSame(2, $payload['paginator']['total']);
        $this->assertSame(2, $payload['meta']['kpi_count_unsettled']);
        $this->assertEqualsCanonicalizing([
            'ORDER-PENDING-SETTLEMENT',
            'ORDER-MISSING-SETTLEMENT',
        ], array_column($rows, 'channel_order_id'));
        $this->assertContains($missingOrder->id, array_column(array_column($rows, 'order'), 'id'));
        $this->assertContains(null, array_column($rows, 'settlement_time'));

        $cairRequest = Request::create('/api/marketplace/settlements', 'GET', [
            'store_id' => $store->id,
            'settlement_status' => 'cair',
            'page' => 1,
            'per_page' => 50,
        ]);

        $cairPayload = app(MarketplaceController::class)->settlements($cairRequest)->getData(true);
        $this->assertSame(0, $cairPayload['paginator']['total']);
    }

    public function test_tab_belum_cair_mencakup_order_tanpa_settlement_dan_estimasi_nol_tetap_valid(): void
    {
        $store = $this->createStore();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-TAB-PENDING',
            'channel_order_id' => 'ORDER-TAB-PENDING',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
        ]);

        $request = Request::create('/api/marketplace/settlements', 'GET', [
            'store_id' => $store->id,
            'tab' => 'belum_cair',
            'page' => 1,
            'per_page' => 50,
        ]);

        $payload = app(MarketplaceController::class)->settlements($request)->getData(true);
        $this->assertSame(1, $payload['paginator']['total']);
        $this->assertSame($order->channel_order_id, $payload['paginator']['data'][0]['channel_order_id']);
        $this->assertSame('manual_24', $payload['paginator']['data'][0]['income_estimation_source']);

        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => 100000,
            'final_income' => 0,
            'settlement_time' => null,
            'raw_json' => [
                '_income_detail' => ['estimated_escrow_amount' => 0],
            ],
        ]);

        $payload = app(MarketplaceController::class)->settlements($request)->getData(true);
        $row = $payload['paginator']['data'][0];
        $this->assertSame(0.0, (float) $row['estimated_escrow_amount']);
        $this->assertSame(0.0, (float) $row['final_income']);
        $this->assertTrue($row['is_estimated_income']);
        $this->assertSame('estimated_escrow', $row['income_estimation_source']);

        $highOrder = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-TAB-HIGH',
            'channel_order_id' => 'ORDER-TAB-HIGH',
            'order_status' => 'COMPLETED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $highOrder->id,
            'channel_order_id' => $highOrder->channel_order_id,
            'buyer_payment_amount' => 100000,
            'final_income' => 0,
            'settlement_time' => null,
            'raw_json' => [
                '_income_detail' => ['estimated_escrow_amount' => 99999],
            ],
        ]);

        $sortRequest = Request::create('/api/marketplace/settlements', 'GET', [
            'store_id' => $store->id,
            'tab' => 'semua',
            'sort_by' => 'final_income',
            'sort_dir' => 'desc',
            'page' => 1,
            'per_page' => 50,
        ]);
        $sorted = app(MarketplaceController::class)->settlements($sortRequest)->getData(true);
        $this->assertSame('ORDER-TAB-HIGH', $sorted['paginator']['data'][0]['channel_order_id']);
    }

    public function test_income_products_memakai_estimasi_shopee_dan_mencakup_pending_tanpa_settlement(): void
    {
        $store = $this->createStore();
        $estimatedOrder = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-PRODUCT-EST',
            'channel_order_id' => 'ORDER-PRODUCT-EST',
            'order_status' => 'SHIPPED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 100000,
            'total_paid_customer' => 100000,
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $estimatedOrder->id,
            'marketplace_order_id' => $estimatedOrder->id,
            'item_name' => 'Produk Estimasi',
            'variant_name' => 'Default',
            'item_sku' => 'SKU-EST',
            'qty' => 1,
            'price' => 100000,
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $estimatedOrder->id,
            'channel_order_id' => $estimatedOrder->channel_order_id,
            'buyer_payment_amount' => 100000,
            'final_income' => 0,
            'settlement_time' => null,
            'raw_json' => [
                '_income_detail' => ['estimated_escrow_amount' => 54321],
            ],
        ]);

        $missingOrder = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'EXT-PRODUCT-MISSING',
            'channel_order_id' => 'ORDER-PRODUCT-MISSING',
            'order_status' => 'SHIPPED',
            'ordered_at' => now()->subDay(),
            'order_date' => now()->subDay(),
            'subtotal_items' => 200000,
            'total_paid_customer' => 200000,
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $missingOrder->id,
            'marketplace_order_id' => $missingOrder->id,
            'item_name' => 'Produk Pending',
            'variant_name' => 'Default',
            'item_sku' => 'SKU-PENDING',
            'qty' => 1,
            'price' => 200000,
        ]);

        $request = Request::create('/marketplace/penghasilan/produk', 'GET', [
            'store_id' => $store->id,
        ]);
        $payload = app(MarketplaceFinanceController::class)->incomeProducts($request)->getData(true);

        $this->assertSame(2, $payload['meta']['total_unsettled_order_count']);
        $this->assertEqualsWithDelta(206321.0, (float) $payload['meta']['total_income_belum_cair'], 0.01);
    }
}
