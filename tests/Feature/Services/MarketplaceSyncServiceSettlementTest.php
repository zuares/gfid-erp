<?php

namespace Tests\Feature\Services;

use App\Models\Channel;
use App\Models\Journal;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Services\Channels\Shopee\ShopeeChannel;
use App\Services\MarketplaceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Test Fase 1 untuk MarketplaceSyncService::syncSettlements() dan method privat
 * pendukungnya (retry, validasi, mapping, normalisasi). Driver Shopee di-mock lewat
 * container (ChannelManager::driver() me-resolve app(ShopeeChannel::class)), jadi
 * TIDAK ada panggilan HTTP nyata di test ini.
 */
class MarketplaceSyncServiceSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected Channel $shopee;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $this->store = Store::create([
            'channel_id'  => $this->shopee->id,
            'code'        => 'S' . rand(1000, 9999),
            'name'        => 'Toko Uji',
            'status'      => 'active',
            'is_active'   => true,
            'credentials' => ['access_token' => 'dummy'],
            'token_expires_at' => now()->addDay(),
        ]);

        $this->mirrorLegacyMarketplaceStore($this->store);
    }

    /** Mirror legacy rows while the test exercises the canonical Store model. */
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

        DB::table('marketplace_stores')->insert([
            'id'         => $store->id,
            'channel_id' => $channelId,
            'name'       => $store->name,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrder(array $overrides = []): MarketplaceOrder
    {
        return MarketplaceOrder::create(array_merge([
            'store_id'          => $this->store->id,
            'external_order_id' => 'EXT-' . rand(10000, 99999),
            'order_date'        => now(),
            'channel_order_id'  => 'ORDER-' . rand(100000, 999999),
            'order_status'      => 'COMPLETED',
            'ordered_at'        => now()->subDays(2),
        ], $overrides));
    }

    private function createAdditionalStore(string $name = 'Toko Uji 2'): Store
    {
        $store = Store::create([
            'channel_id'  => $this->shopee->id,
            'code'        => 'S' . rand(1000, 9999),
            'name'        => $name,
            'status'      => 'active',
            'is_active'   => true,
            'credentials' => ['access_token' => 'dummy'],
            'token_expires_at' => now()->addDay(),
        ]);

        $this->mirrorLegacyMarketplaceStore($store);

        return $store;
    }

    private function mockDriver(\Closure $expectations): void
    {
        $this->mock(ShopeeChannel::class, $expectations);
    }

    private function escrowResponse(array $income, int $httpStatus = 200, ?string $retryAfter = null): array
    {
        return [
            'response' => ['order_income' => $income],
            '_meta'    => ['http_status' => $httpStatus, 'retry_after' => $retryAfter],
        ];
    }

    /**
     * Subset field REALISTIS meniru struktur response nyata Shopee yang diverifikasi
     * lewat UAT 23 Juli 2026 (order 2607181DCXQSBW, toko Greatfit.id, --inspect) — bukan
     * 83 field lengkap (tidak perlu, dan menghindari menaruh nama produk/data toko asli
     * di fixture test), hanya field yang relevan untuk keputusan mapping. SENGAJA TIDAK
     * menyertakan field-field yang jadi fokus pengujian spesifik per skenario (mis.
     * seller_transaction_fee, order_ams_commission_fee, campaign_fee) di base ini — tiap
     * test menambahkannya sendiri lewat $overrides supaya jelas apa yang sedang diuji.
     */
    private function realisticIncomeFields(array $overrides = []): array
    {
        return array_merge([
            'buyer_total_amount'     => 100000,
            'commission_fee'         => 5000,
            'service_fee'            => 6000,
            'voucher_from_seller'    => 150,
            'seller_coin_cash_back'  => 0,
            'actual_shipping_fee'    => 7000,
            'shopee_shipping_rebate' => 7000,
            'reverse_shipping_fee'   => 0,
            'drc_adjustable_refund'  => 0,
            'escrow_tax'             => 0,
            'escrow_amount'          => 60000,
            'order_sn'               => 'FIXTURE-ORDER',
        ], $overrides);
    }

    // ── 1. Order eligible tanpa settlement berhasil disimpan ───────────────────
    public function test_order_eligible_tanpa_settlement_berhasil_disimpan()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->withArgs(fn ($store, $sn) => $sn === $order->channel_order_id)
                ->andReturn($this->escrowResponse([
                    'final_income'    => 45000,
                    'commission_fee'  => 1000,
                    'order_sn'        => $order->channel_order_id,
                ]));
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store);

        $this->assertSame(1, $result['synced']);
        $this->assertSame(0, $result['errors']);
        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'final_income'     => '45000.00',
        ]);
    }

    // ── 2. Order tidak eligible dilewati (tidak masuk query) ───────────────────
    public function test_order_tidak_eligible_dilewati()
    {
        $this->createOrder(['order_status' => 'UNPAID']);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldNotReceive('getEscrowDetail');
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store);

        $this->assertSame(0, $result['found']);
        $this->assertSame(0, $result['synced']);
    }

    // ── 5. --resync memperbarui settlement existing ────────────────────────────
    public function test_resync_memperbarui_settlement_existing()
    {
        $order = $this->createOrder();
        MarketplaceOrderSettlement::create([
            'store_id' => $this->store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'commission_fee' => 500,
            'final_income' => 10000,
            'synced_at' => now()->subDay(),
        ]);

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse([
                    'final_income'   => 12345,
                    'commission_fee' => 700,
                    'order_sn'       => $order->channel_order_id,
                ]));
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store, resync: true);

        $this->assertSame(1, $result['synced']);
        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'final_income'     => '12345.00',
            'commission_fee'   => '700.00',
        ]);
        $this->assertSame(1, MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->count());
    }

    // ── 6. Tanpa --resync, settlement existing dilewati ────────────────────────
    public function test_tanpa_resync_settlement_existing_dilewati()
    {
        $order = $this->createOrder();
        MarketplaceOrderSettlement::create([
            'store_id' => $this->store->id,
            'order_id' => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'final_income' => 10000,
            'synced_at' => now(),
        ]);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldNotReceive('getEscrowDetail');
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store, resync: false);

        $this->assertSame(0, $result['found']); // whereDoesntHave('settlement') menyaring order ini
        $this->assertDatabaseHas('marketplace_order_settlements', ['final_income' => '10000.00']);
    }

    // ── 6b. Breakdown new vs updated pada response (ditulis 23 Juli 2026, saat
    //        menambah kebutuhan tombol UI "diterima/baru/diperbarui/dilewati/gagal")
    //        — BELUM DIJALANKAN, lihat catatan status test di laporan sesi ini.
    public function test_settlement_baru_dihitung_sebagai_new_bukan_updated()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse(['final_income' => 45000, 'order_sn' => $order->channel_order_id]));
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store);

        $this->assertSame(1, $result['synced']);
        $this->assertSame(1, $result['new']);
        $this->assertSame(0, $result['updated']);
    }

    // ── 6c. Settlement PENDING (settlement_time NULL) yang sudah lewat cooldown
    //        direfresh otomatis TANPA --resync, dan dihitung sebagai 'updated'.
    public function test_settlement_pending_direfresh_otomatis_setelah_cooldown_dan_dihitung_updated()
    {
        $order = $this->createOrder();
        MarketplaceOrderSettlement::create([
            'store_id'         => $this->store->id,
            'order_id'         => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'final_income'     => 10000,
            'settlement_time'  => null, // belum final
            'synced_at'        => now()->subMinutes(90), // lewat cooldown 60 menit
        ]);

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse([
                    'final_income'          => 12000,
                    'order_sn'              => $order->channel_order_id,
                    'escrow_release_time'   => now()->subMinute()->timestamp, // sekarang final
                ]));
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store, resync: false);

        $this->assertSame(1, $result['found']);
        $this->assertSame(1, $result['synced']);
        $this->assertSame(0, $result['new']);
        $this->assertSame(1, $result['updated']);
        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'final_income'     => '12000.00',
        ]);
        $this->assertSame(1, MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->count());
    }

    // ── 6d. Settlement PENDING yang BELUM lewat cooldown TIDAK di-refresh
    //        (mencegah request API berlebihan ke Shopee untuk order yang sama).
    public function test_settlement_pending_belum_lewat_cooldown_tidak_direfresh()
    {
        $order = $this->createOrder();
        MarketplaceOrderSettlement::create([
            'store_id'         => $this->store->id,
            'order_id'         => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'final_income'     => 10000,
            'settlement_time'  => null,
            'synced_at'        => now()->subMinutes(10), // belum 60 menit
        ]);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldNotReceive('getEscrowDetail');
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store, resync: false);

        $this->assertSame(0, $result['found']);
    }

    // ── 6e. Settlement yang SUDAH FINAL (settlement_time terisi) TIDAK PERNAH
    //        di-refresh otomatis tanpa --resync, walau synced_at sudah lama —
    //        ini invarian keamanan paling penting dari fitur pending-refresh:
    //        jangan sampai data yang sudah final diminta ulang terus-menerus.
    public function test_settlement_final_tidak_pernah_direfresh_otomatis()
    {
        $order = $this->createOrder();
        MarketplaceOrderSettlement::create([
            'store_id'         => $this->store->id,
            'order_id'         => $order->id,
            'channel_order_id' => $order->channel_order_id,
            'final_income'     => 10000,
            'settlement_time'  => now()->subDays(10),
            'synced_at'        => now()->subDays(10),
        ]);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldNotReceive('getEscrowDetail');
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store, resync: false);

        $this->assertSame(0, $result['found']);
    }

    // ── 10. Error satu order tidak menghentikan batch ──────────────────────────
    public function test_error_satu_order_tidak_menghentikan_batch()
    {
        $orderA = $this->createOrder();
        $orderB = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($orderA, $orderB) {
            $mock->shouldReceive('getEscrowDetail')
                ->withArgs(fn ($s, $sn) => $sn === $orderA->channel_order_id)
                ->andReturn(['error' => 'internal_error', 'message' => 'Boom', '_meta' => ['http_status' => 400, 'retry_after' => null]]);

            $mock->shouldReceive('getEscrowDetail')
                ->withArgs(fn ($s, $sn) => $sn === $orderB->channel_order_id)
                ->andReturn($this->escrowResponse(['final_income' => 5000, 'order_sn' => $orderB->channel_order_id]));
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store);

        $this->assertSame(1, $result['synced']);
        $this->assertSame(1, $result['errors']);
        $this->assertContains($orderA->channel_order_id, $result['failed_order_ids']);
        $this->assertDatabaseHas('marketplace_order_settlements', ['channel_order_id' => $orderB->channel_order_id]);
        $this->assertDatabaseMissing('marketplace_order_settlements', ['channel_order_id' => $orderA->channel_order_id]);
    }

    // ── 13. Tidak ada jurnal yang dibuat ────────────────────────────────────────
    public function test_tidak_ada_jurnal_dibuat()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse(['final_income' => 1000, 'order_sn' => $order->channel_order_id]));
        });

        $before = Journal::count();

        $service = app(MarketplaceSyncService::class);
        $service->syncSettlements($this->store);

        $this->assertSame($before, Journal::count());
    }

    // ── 14. Raw response disimpan ───────────────────────────────────────────────
    public function test_raw_response_disimpan()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse([
                    'final_income' => 1000,
                    'commission_fee' => 250,
                    'order_sn' => $order->channel_order_id,
                ]));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $settlement = MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->first();
        $this->assertIsArray($settlement->raw_json);
        $this->assertSame(1000, (int) $settlement->raw_json['final_income']);
        $this->assertSame($order->channel_order_id, $settlement->raw_json['order_sn']);
    }

    // ── 28. Metadata HTTP tidak ikut tersimpan sebagai raw payload Shopee ──────
    public function test_meta_tidak_ikut_tersimpan_di_raw_json()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse(['final_income' => 1000, 'order_sn' => $order->channel_order_id], 200, '5'));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $settlement = MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->first();
        $this->assertArrayNotHasKey('_meta', $settlement->raw_json);
        $this->assertArrayNotHasKey('http_status', $settlement->raw_json);
        $this->assertArrayNotHasKey('retry_after', $settlement->raw_json);
    }

    // ── 18. Response sukses HTTP tapi payload ada field 'error' → tidak membuat settlement ──
    public function test_response_dengan_field_error_tidak_membuat_settlement()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn(['error' => 'order_not_found', 'message' => 'Order tidak ditemukan', '_meta' => ['http_status' => 200, 'retry_after' => null]]);
        });

        $result = app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertSame(0, $result['synced']);
        $this->assertSame(1, $result['errors']);
        $this->assertDatabaseMissing('marketplace_order_settlements', ['channel_order_id' => $order->channel_order_id]);
    }

    // ── 19. Response kosong tidak membuat settlement bernilai nol ──────────────
    public function test_response_kosong_tidak_membuat_settlement_nol()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn(['response' => ['order_income' => []], '_meta' => ['http_status' => 200, 'retry_after' => null]]);
        });

        $result = app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertSame(0, $result['synced']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(0, $result['errors']);
        $this->assertDatabaseMissing('marketplace_order_settlements', ['channel_order_id' => $order->channel_order_id]);
        $this->assertNotNull($order->fresh()->settlement_sync_last_attempt_at);
    }

    public function test_response_kosong_menghormati_cooldown_tanpa_membuat_row_dummy()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn(['response' => ['order_income' => []], '_meta' => ['http_status' => 200, 'retry_after' => null]]);
        });

        $service = app(MarketplaceSyncService::class);
        $service->syncSettlements($this->store);
        $second = $service->syncSettlements($this->store);

        $this->assertSame(0, $second['found']);
        $this->assertDatabaseMissing('marketplace_order_settlements', ['channel_order_id' => $order->channel_order_id]);
    }

    // ── 20-22. Normalisasi nominal (null / string numerik / string nonnumerik) ─
    /**
     * decimalValue() sendiri (dipanggil via Reflection) HARUS mengembalikan null
     * murni untuk field yang tidak tersedia — ini semantik inti Koreksi 6, diuji
     * lepas dari keterbatasan skema kolom.
     */
    public function test_decimal_value_null_tetap_null_di_level_normalizer()
    {
        $service = app(MarketplaceSyncService::class);
        $method = new \ReflectionMethod($service, 'decimalValue');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($service, null));
        $this->assertNull($method->invoke($service, '')); // string kosong juga dianggap "tidak tersedia"
    }

    /**
     * Kolom fee di marketplace_order_settlements adalah NOT NULL (default '0') di
     * skema saat ini (lihat migration create_marketplace_order_settlements_table —
     * tidak ada ->nullable()). decimalValue() null (field tidak tersedia dari API)
     * sengaja "digenapkan" ke 0.00 HANYA di titik penyimpanan (lihat
     * MarketplaceSyncService::nn()) — bukan disamakan artinya, murni supaya tidak
     * melanggar constraint NOT NULL yang sudah ada. Test ini mendokumentasikan
     * perilaku penyimpanan yang sebenarnya terjadi.
     */
    public function test_field_tidak_tersedia_disimpan_sebagai_nol_karena_kolom_not_null()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse([
                    'final_income'   => 1000,
                    // commission_fee SENGAJA tidak ada di response sama sekali
                    'order_sn'       => $order->channel_order_id,
                ]));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'commission_fee'   => '0.00',
        ]);
    }

    public function test_string_numerik_berhasil_dinormalisasi()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse([
                    'final_income' => '15000.5', // Shopee kadang kirim angka sebagai string
                    'order_sn'     => $order->channel_order_id,
                ]));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'final_income'     => '15000.50',
        ]);
    }

    public function test_string_nonnumerik_menghasilkan_error()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse([
                    'final_income' => 'TIDAK_VALID',
                    'order_sn'     => $order->channel_order_id,
                ]));
        });

        $result = app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertSame(0, $result['synced']);
        $this->assertSame(1, $result['errors']);
        $this->assertDatabaseMissing('marketplace_order_settlements', ['channel_order_id' => $order->channel_order_id]);
    }

    // ── 23-24. Normalisasi timestamp (epoch detik / milidetik) ─────────────────
    public function test_timestamp_epoch_detik_diproses_benar()
    {
        $order = $this->createOrder();
        $epochSeconds = now()->subDay()->timestamp; // 10 digit wajar

        $this->mockDriver(function (MockInterface $mock) use ($order, $epochSeconds) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse([
                    'final_income'         => 1000,
                    'escrow_release_time'  => $epochSeconds,
                    'order_sn'             => $order->channel_order_id,
                ]));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $settlement = MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->first();
        $this->assertNotNull($settlement->settlement_time);
        $this->assertEqualsWithDelta($epochSeconds, $settlement->settlement_time->timestamp, 1);
    }

    public function test_timestamp_milidetik_diproses_benar()
    {
        $order = $this->createOrder();
        $epochSeconds = now()->subDay()->timestamp;
        $epochMillis = $epochSeconds * 1000; // 13 digit — di atas ambang heuristik

        $this->mockDriver(function (MockInterface $mock) use ($order, $epochMillis) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse([
                    'final_income'         => 1000,
                    'escrow_release_time'  => $epochMillis,
                    'order_sn'             => $order->channel_order_id,
                ]));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $settlement = MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->first();
        $this->assertNotNull($settlement->settlement_time);
        $this->assertEqualsWithDelta($epochSeconds, $settlement->settlement_time->timestamp, 1);
    }

    public function test_timestamp_nol_dianggap_null()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse([
                    'final_income'         => 1000,
                    'escrow_release_time'  => 0,
                    'order_sn'             => $order->channel_order_id,
                ]));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $settlement = MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->first();
        $this->assertNull($settlement->settlement_time);
    }

    public function test_update_time_order_tidak_dianggap_sebagai_waktu_pencairan()
    {
        $order = $this->createOrder([
            'raw_json' => ['update_time' => now()->subDay()->timestamp],
        ]);

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse([
                    'escrow_amount' => 1000,
                    'order_sn' => $order->channel_order_id,
                ]));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertNull(MarketplaceOrderSettlement::where('store_id', $this->store->id)
            ->where('channel_order_id', $order->channel_order_id)
            ->value('settlement_time'));
    }

    public function test_settlement_same_order_number_di_dua_toko_tidak_saling_menimpa()
    {
        $storeB = $this->createAdditionalStore();
        $orderA = $this->createOrder(['channel_order_id' => 'DUPLICATE-SN']);
        $orderB = $this->createOrder(['store_id' => $storeB->id, 'channel_order_id' => 'DUPLICATE-SN']);

        $this->mockDriver(function (MockInterface $mock) use ($orderA, $orderB) {
            $mock->shouldReceive('getEscrowDetail')
                ->withArgs(fn ($store, $sn) => $sn === 'DUPLICATE-SN')
                ->twice()
                ->andReturn(
                    $this->escrowResponse(['escrow_amount' => 1000, 'order_sn' => $orderA->channel_order_id]),
                    $this->escrowResponse(['escrow_amount' => 2000, 'order_sn' => $orderB->channel_order_id]),
                );
        });

        $service = app(MarketplaceSyncService::class);
        $service->syncSettlements($this->store);
        $service->syncSettlements($storeB);

        $this->assertSame(2, MarketplaceOrderSettlement::where('channel_order_id', 'DUPLICATE-SN')->count());
        $this->assertDatabaseHas('marketplace_order_settlements', [
            'store_id' => $this->store->id,
            'channel_order_id' => 'DUPLICATE-SN',
            'final_income' => '1000.00',
        ]);
        $this->assertDatabaseHas('marketplace_order_settlements', [
            'store_id' => $storeB->id,
            'channel_order_id' => 'DUPLICATE-SN',
            'final_income' => '2000.00',
        ]);
    }

    // ── 29. Transaction hanya per order, bukan seluruh batch ────────────────────
    public function test_transaction_per_order_bukan_per_batch()
    {
        $orderOk = $this->createOrder();
        $orderBad = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($orderOk, $orderBad) {
            $mock->shouldReceive('getEscrowDetail')
                ->withArgs(fn ($s, $sn) => $sn === $orderOk->channel_order_id)
                ->andReturn($this->escrowResponse(['final_income' => 1000, 'order_sn' => $orderOk->channel_order_id]));

            // Nominal nonnumerik -> decimalValue() throw di tengah mapping,
            // TAPI ini terjadi SETELAH order pertama sudah commit di transaction-nya sendiri.
            $mock->shouldReceive('getEscrowDetail')
                ->withArgs(fn ($s, $sn) => $sn === $orderBad->channel_order_id)
                ->andReturn($this->escrowResponse(['final_income' => 'RUSAK', 'order_sn' => $orderBad->channel_order_id]));
        });

        // Urutan diproses berdasarkan id (orderOk dibuat lebih dulu -> id lebih kecil)
        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', ['channel_order_id' => $orderOk->channel_order_id]);
        $this->assertDatabaseMissing('marketplace_order_settlements', ['channel_order_id' => $orderBad->channel_order_id]);
    }

    // ── Retry transient ditangani channel; service tidak retry ganda ────────────
    public function test_rate_limit_dicatat_tanpa_retry_ganda_di_service()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn(['error' => 'rate_limited', 'message' => 'Too many requests', '_meta' => ['http_status' => 429, 'retry_after' => 0]]);
        });

        $result = app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertSame(1, $result['errors']);
        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'settlement_sync_error_code' => 'rate_limit',
        ]);
    }

    public function test_tidak_retry_untuk_4xx_selain_429()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once() // TIDAK boleh dipanggil lagi untuk error 400
                ->andReturn(['error' => 'invalid_param', 'message' => 'Bad request', '_meta' => ['http_status' => 400, 'retry_after' => null]]);
        });

        $result = app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertSame(1, $result['errors']);
    }

    public function test_connection_exception_dicatat_tanpa_retry_ganda_di_service()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andThrow(new ConnectionException('Connection timed out'));
        });

        $result = app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertSame(1, $result['errors']);
        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'settlement_sync_error_code' => 'connection_exception',
        ]);
    }

    public function test_error_transient_dicoba_lagi_setelah_cooldown()
    {
        $order = $this->createOrder([
            'settlement_sync_error_code' => 'server_error',
            'settlement_sync_failed_at' => now(),
            'settlement_sync_last_attempt_at' => now(),
        ]);

        $service = app(MarketplaceSyncService::class);
        $first = $service->syncSettlements($this->store);
        $this->assertSame(0, $first['found']);

        $order->update(['settlement_sync_last_attempt_at' => now()->subHours(2)]);

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse(['final_income' => 1000, 'order_sn' => $order->channel_order_id]));
        });

        $second = $service->syncSettlements($this->store);

        $this->assertSame(1, $second['found']);
        $this->assertSame(1, $second['synced']);
        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'settlement_sync_error_code' => null,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Koreksi mapping berbasis UAT nyata 23 Juli 2026 (order 2607181DCXQSBW,
    // toko Greatfit.id) — lihat docblock MarketplaceSyncService::mapEscrowSettlement().
    // ═══════════════════════════════════════════════════════════════════════════

    // ── 1. buyer_total_amount dipetakan ke buyer_payment_amount ────────────────
    public function test_buyer_total_amount_dipetakan_ke_buyer_payment_amount()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'buyer_total_amount' => 81912,
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => '81912.00',
        ]);
    }

    // ── 2. buyer_total_amount prioritas atas fallback lama ──────────────────────
    public function test_buyer_total_amount_prioritas_atas_fallback_lama()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'buyer_total_amount'   => 81912,
                    'buyer_payment_amount' => 99999, // field lama — HARUS diabaikan
                    'buyer_paid_amount'    => 88888, // field lama — HARUS diabaikan
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'buyer_payment_amount' => '81912.00',
        ]);
    }

    // ── 3. voucher_from_seller dipetakan ke seller_voucher ──────────────────────
    public function test_voucher_from_seller_dipetakan_ke_seller_voucher()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'voucher_from_seller' => 200,
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'seller_voucher' => '200.00',
        ]);
    }

    // ── 4. seller_transaction_fee dipetakan ke transaction_fee ──────────────────
    public function test_seller_transaction_fee_dipetakan_ke_transaction_fee()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'seller_transaction_fee' => 350,
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'transaction_fee' => '350.00',
        ]);
    }

    // ── 5. buyer_transaction_fee TIDAK dipakai sebagai transaction fee seller ──
    public function test_buyer_transaction_fee_tidak_dipakai_sebagai_transaction_fee_seller()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                // seller_transaction_fee SENGAJA tidak diikutkan (absen), hanya
                // buyer_transaction_fee yang ada — buktikan itu TIDAK dipakai.
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'buyer_transaction_fee' => 700,
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'transaction_fee' => '0.00', // BUKAN 700.00
        ]);
    }

    // ── 6. credit_card_transaction_fee TIDAK dipakai sebagai transaction fee seller ──
    public function test_credit_card_transaction_fee_tidak_dipakai_sebagai_transaction_fee_seller()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'credit_card_transaction_fee' => 700,
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'transaction_fee' => '0.00', // BUKAN 700.00
        ]);
    }

    // ── 7. order_ams_commission_fee dipetakan ke activity_fee ───────────────────
    public function test_order_ams_commission_fee_dipetakan_ke_activity_fee()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'order_ams_commission_fee' => 300,
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'activity_fee' => '300.00',
        ]);
    }

    // ── 8. campaign_fee TIDAK dijumlahkan ke activity_fee ───────────────────────
    public function test_campaign_fee_tidak_dijumlahkan_ke_activity_fee()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'order_ams_commission_fee' => 300,
                    'campaign_fee' => 999999, // konsep beda — harus TIDAK ikut activity_fee
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'activity_fee' => '300.00', // bukan 300+999999, bukan 999999
        ]);

        // campaign_fee tetap tersimpan apa adanya di raw_json (tidak dihapus/diabaikan
        // sepenuhnya) — hanya tidak dipakai sebagai sumber activity_fee.
        $settlement = MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->first();
        $this->assertSame(999999, (int) $settlement->raw_json['campaign_fee']);
    }

    // ── 9. affiliate_fee dan shipping_insurance_fee ikut dipetakan ─────────────
    public function test_affiliate_fee_dan_shipping_insurance_fee_dipetakan()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'affiliate_commission_fee' => 425,
                    'shipping_insurance' => 1200,
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'affiliate_fee' => '425.00',
            'shipping_insurance_fee' => '1200.00',
        ]);
    }

    // ── 9. escrow_amount tetap dipetakan ke final_income (regresi, fixture baru) ──
    public function test_escrow_amount_tetap_dipetakan_ke_final_income()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'escrow_amount' => 68456,
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'final_income' => '68456.00',
        ]);
    }

    // ── 10. Metadata internal baru (payload/meta) TIDAK ikut masuk raw_json ────
    public function test_meta_internal_baru_tidak_ikut_masuk_raw_json()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'order_sn' => $order->channel_order_id,
                ]), 200, '3'));
        });

        $result = app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertArrayHasKey('last_call_meta', $result);
        $this->assertArrayHasKey('attempts', $result['last_call_meta']);
        $this->assertArrayHasKey('duration_ms', $result['last_call_meta']);

        $settlement = MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->first();
        $this->assertArrayNotHasKey('meta', $settlement->raw_json);
        $this->assertArrayNotHasKey('attempts', $settlement->raw_json);
        $this->assertArrayNotHasKey('duration_ms', $settlement->raw_json);
        $this->assertArrayNotHasKey('token_refreshed', $settlement->raw_json);
    }

    // ── 11. Attempt count tercatat pada result ──────────────────────────────────
    public function test_attempt_count_tercatat_pada_result()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn(['error' => 'rate_limited', 'message' => 'Too many requests', '_meta' => ['http_status' => 429, 'retry_after' => 0]]);
        });

        $result = app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertSame(1, $result['errors']);
        $this->assertNotNull($result['last_call_meta']);
        $this->assertSame(1, $result['last_call_meta']['attempts']);
    }

    // ── 12. Duration tersedia pada result ───────────────────────────────────────
    public function test_duration_tersedia_pada_result()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields(['order_sn' => $order->channel_order_id])));
        });

        $result = app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertArrayHasKey('duration_ms', $result['last_call_meta']);
        $this->assertIsFloat($result['last_call_meta']['duration_ms']);
        $this->assertGreaterThanOrEqual(0, $result['last_call_meta']['duration_ms']);
    }

    // ── 13. Missing API field dicatat TANPA menyimpan raw response ke log ──────
    public function test_missing_field_dicatat_tanpa_raw_response_di_log()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                // transaction_fee & activity_fee SENGAJA tidak tersedia sama sekali
                // (tidak ada di realisticIncomeFields() base, tidak ditambahkan di sini).
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        Log::spy();

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context = []) use ($order) {
                if (! str_contains($message, "Order {$order->channel_order_id} field API hilang")) {
                    return false;
                }
                $this->assertArrayHasKey('missing_financial_fields', $context);
                $this->assertContains('transaction_fee', $context['missing_financial_fields']);
                $this->assertContains('activity_fee', $context['missing_financial_fields']);
                // Hanya nama kolom lokal yang boleh ada — TIDAK ADA payload/raw response.
                $this->assertArrayNotHasKey('raw_json', $context);
                $this->assertArrayNotHasKey('response', $context);
                $this->assertArrayNotHasKey('income', $context);
                $this->assertArrayNotHasKey('payload', $context);
                return true;
            })
            ->once();
    }

    // ── 14. settlement_time tetap null ketika field release tidak tersedia ─────
    public function test_settlement_time_tetap_null_ketika_field_release_tidak_tersedia()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                // escrow_release_time & settlement_time SENGAJA tidak ada sama sekali.
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $settlement = MarketplaceOrderSettlement::where('channel_order_id', $order->channel_order_id)->first();
        $this->assertNull($settlement->settlement_time);
    }

    // ── 15. Nilai nol eksplisit TIDAK dianggap missing ──────────────────────────
    public function test_nilai_nol_eksplisit_tidak_dianggap_missing()
    {
        $order = $this->createOrder();

        $this->mockDriver(function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->andReturn($this->escrowResponse($this->realisticIncomeFields([
                    'commission_fee'       => 0,
                    'reverse_shipping_fee' => 0,
                    'order_sn' => $order->channel_order_id,
                ])));
        });

        Log::spy();

        app(MarketplaceSyncService::class)->syncSettlements($this->store);

        $this->assertDatabaseHas('marketplace_order_settlements', [
            'channel_order_id' => $order->channel_order_id,
            'commission_fee' => '0.00',
            'reverse_shipping_fee' => '0.00',
        ]);

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context = []) {
                if (! str_contains($message, 'field API hilang')) {
                    return true; // panggilan Log::info lain — bukan yang diuji di sini
                }
                $this->assertNotContains('commission_fee', $context['missing_financial_fields']);
                $this->assertNotContains('reverse_shipping_fee', $context['missing_financial_fields']);
                return true;
            });
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Permanent Failure & Revalidation (Tugas 3, Tugas 4, Tugas 5, Tugas 6)
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_query_kandidat_hanya_mengambil_order_completed_dan_shipped_tidak_dipanggil()
    {
        $this->createOrder(['order_status' => 'SHIPPED', 'channel_order_id' => 'ORD-SHIPPED']);
        $this->createOrder(['order_status' => 'TO_CONFIRM_RECEIVE', 'channel_order_id' => 'ORD-TCR']);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldNotReceive('getEscrowDetail');
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store);

        $this->assertEquals(0, $result['found']);
        $this->assertEquals(0, $result['processed']);
    }

    public function test_order_berubah_status_sebelum_api_menjadi_skipped()
    {
        $order = $this->createOrder(['order_status' => 'COMPLETED', 'channel_order_id' => 'ORD-STALE']);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldNotReceive('getEscrowDetail');
        });

        // Paksa agar kandidat bisa diambil tapi berubah status sebelum API dipanggil
        MarketplaceOrder::retrieved(function ($model) {
            if ($model->channel_order_id === 'ORD-STALE') {
                DB::table('marketplace_orders')->where('id', $model->id)->update(['order_status' => 'SHIPPED']);
            }
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store);

        $this->assertEquals(1, $result['found']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertEquals(0, $result['errors']);
        $this->assertEquals(0, $result['synced']);
    }

    public function test_order_not_found_membuat_permanent_failure()
    {
        $order = $this->createOrder(['channel_order_id' => 'ORD-NOTFOUND']);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldReceive('getEscrowDetail')->once()->andReturn([
                'error' => 'order_not_found',
                'message' => 'Order SN provided is invalid or does not belong to you.',
                '_meta' => ['http_status' => 200]
            ]);
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store);

        $this->assertEquals(1, $result['errors']);
        $this->assertDatabaseHas('marketplace_orders', [
            'channel_order_id' => 'ORD-NOTFOUND',
            'settlement_sync_error_code' => 'order_not_found',
        ]);
        $this->assertDatabaseMissing('marketplace_order_settlements', [
            'channel_order_id' => 'ORD-NOTFOUND'
        ]);
    }

    public function test_permanent_failure_tidak_dipilih_ulang_pada_batch_berikutnya()
    {
        $this->createOrder(['channel_order_id' => 'ORD-PERM-FAIL', 'settlement_sync_error_code' => 'order_not_found']);
        $this->createOrder(['channel_order_id' => 'ORD-VALID']);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldReceive('getEscrowDetail')->once()
                 ->withArgs(fn($store, $sn) => $sn === 'ORD-VALID')
                 ->andReturn($this->escrowResponse(['final_income' => 100]));
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store);

        $this->assertEquals(1, $result['found']); // Hanya 1 kandidat yang ketemu
        $this->assertEquals(1, $result['synced']);
    }

    public function test_explicit_order_masih_mencoba_ulang_permanent_failure()
    {
        $order = $this->createOrder(['channel_order_id' => 'ORD-PERM-FAIL', 'settlement_sync_error_code' => 'order_not_found']);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldReceive('getEscrowDetail')->once()
                 ->withArgs(fn($store, $sn) => $sn === 'ORD-PERM-FAIL')
                 ->andReturn($this->escrowResponse(['final_income' => 100]));
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store, null, null, 'ORD-PERM-FAIL');

        $this->assertEquals(1, $result['found']);
        $this->assertEquals(1, $result['synced']);
        
        $this->assertDatabaseHas('marketplace_orders', [
            'channel_order_id' => 'ORD-PERM-FAIL',
            'settlement_sync_error_code' => null, // Reset setelah sukses
        ]);
    }

    public function test_mixed_result_tercatat_partial_success()
    {
        $this->createOrder(['channel_order_id' => 'ORD-OK']);
        $this->createOrder(['channel_order_id' => 'ORD-FAIL']);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldReceive('getEscrowDetail')
                 ->withArgs(fn($store, $sn) => $sn === 'ORD-OK')
                 ->andReturn($this->escrowResponse(['final_income' => 100]));
            
            $mock->shouldReceive('getEscrowDetail')
                 ->withArgs(fn($store, $sn) => $sn === 'ORD-FAIL')
                 ->andReturn([
                     'error' => 'server_error',
                     'message' => 'Internal server error',
                     '_meta' => ['http_status' => 500]
                 ]);
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store);

        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(1, $result['synced']);
        $this->assertEquals(1, $result['errors']);
        
        $this->assertDatabaseHas('marketplace_sync_logs', [
            'store_id' => $this->store->id,
            'action' => 'sync_settlements',
            'status' => 'partial_success',
        ]);
    }

    public function test_seluruh_error_tercatat_failed()
    {
        $this->createOrder(['channel_order_id' => 'ORD-FAIL']);

        $this->mockDriver(function (MockInterface $mock) {
            $mock->shouldReceive('getEscrowDetail')
                 ->withArgs(fn($store, $sn) => $sn === 'ORD-FAIL')
                 ->andReturn([
                     'error' => 'server_error',
                     'message' => 'Internal server error',
                     '_meta' => ['http_status' => 500]
                 ]);
        });

        $service = app(MarketplaceSyncService::class);
        $result = $service->syncSettlements($this->store);

        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['synced']);
        $this->assertEquals(1, $result['errors']);
        
        $this->assertDatabaseHas('marketplace_sync_logs', [
            'store_id' => $this->store->id,
            'action' => 'sync_settlements',
            'status' => 'failed',
        ]);
    }

    public function test_sync_order_tidak_membuat_duplikat_untuk_settlement_final()
    {
        $orderSn = '260726PFTRAJ2Q';
        $finalTime = now()->subHour();

        $existingOrder = $this->createOrder([
            'channel_order_id' => $orderSn,
            'order_status' => 'COMPLETED',
        ]);

        MarketplaceOrderSettlement::create([
            'store_id' => $this->store->id,
            'order_id' => $existingOrder->id,
            'channel_order_id' => $orderSn,
            'buyer_payment_amount' => 86417,
            'final_income' => 80000,
            'settlement_time' => $finalTime,
            'raw_json' => ['escrow_amount' => 80000],
        ]);

        $method = new \ReflectionMethod(MarketplaceSyncService::class, 'upsertOrders');
        $method->setAccessible(true);
        $method->invoke(app(MarketplaceSyncService::class), $this->store, [[
            'order_sn' => $orderSn,
            'order_status' => 'COMPLETED',
            'total_amount' => 86417,
            'item_list' => [[
                'model_original_price' => 100000,
                'model_discounted_price' => 58000,
                'model_quantity_purchased' => 1,
            ]],
        ]]);

        $settlement = MarketplaceOrderSettlement::where('store_id', $this->store->id)
            ->where('channel_order_id', $orderSn)
            ->firstOrFail();

        $this->assertSame(1, MarketplaceOrderSettlement::where('store_id', $this->store->id)
            ->where('channel_order_id', $orderSn)
            ->count());
        $this->assertEquals($finalTime->timestamp, $settlement->settlement_time->timestamp);
        $this->assertSame(['escrow_amount' => 80000], $settlement->raw_json);
    }

    public function test_sync_order_tidak_mendowngrade_status_ready_to_handover()
    {
        $orderSn = 'ORDER-READY-HANDOVER';
        $order = $this->createOrder([
            'channel_order_id' => $orderSn,
            'order_status' => 'READY_TO_HANDOVER',
        ]);

        $method = new \ReflectionMethod(MarketplaceSyncService::class, 'upsertOrders');
        $method->setAccessible(true);
        $method->invoke(app(MarketplaceSyncService::class), $this->store, [[
            'order_sn' => $orderSn,
            'order_status' => 'PROCESSED',
            'total_amount' => 100000,
            'item_list' => [],
        ]]);

        $order->refresh();

        $this->assertSame('READY_TO_HANDOVER', $order->order_status);
        $this->assertSame('packed', $order->status);
    }
}
