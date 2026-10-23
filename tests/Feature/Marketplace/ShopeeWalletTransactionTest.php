<?php

namespace Tests\Feature\Marketplace;

use App\Models\Channel;
use App\Models\MarketplacePayout;
use App\Models\Store;
use App\Models\User;
use App\Services\Marketplace\Shopee\ShopeeWalletTransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopeeWalletTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function createShopeeStore(string $code = 'SHP-01'): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create([
            'channel_id'       => $channel->id,
            'code'             => $code,
            'name'             => "Toko {$code}",
            'status'           => 'active',
            'is_active'        => true,
            'connection_status'=> 'CONNECTED',
            'external_shop_id' => '12345678',
            'credentials'      => [
                'partner_id'   => '2000001',
                'partner_key'  => 'test_key',
                'shop_id'      => '12345678',
                'access_token' => 'dummy_access_token',
            ],
            'token_expires_at' => now()->addHours(3),
        ]);
    }

    protected function createTestUser(string $role = 'admin'): User
    {
        return User::factory()->create([
            'employee_code' => 'EMP' . rand(1000, 9999),
            'role'          => $role,
        ]);
    }

    protected function dummyShopeeApiResponse(): array
    {
        return [
            'response' => [
                'transaction_list' => [
                    [
                        'transaction_id'   => 90001,
                        'status'           => 'COMPLETED',
                        'transaction_type' => 'escrow_verified_add',
                        'amount'           => 350000,
                        'current_balance'  => 1350000,
                        'create_time'      => 1787220000,
                        'order_sn'         => '261023ABCD1234',
                        'wallet_type'      => 'seller_balance',
                        'reason'           => 'Pelepasan pesanan selesai',
                    ],
                    [
                        'transaction_id'   => 90002,
                        'status'           => 'COMPLETED',
                        'transaction_type' => 'withdrawal_created',
                        'amount'           => -150000,
                        'current_balance'  => 1200000,
                        'create_time'      => 1787223600,
                        'order_sn'         => '',
                        'wallet_type'      => 'seller_balance',
                        'reason'           => 'Penarikan dana ke rekening BCA',
                    ],
                    [
                        'transaction_id'   => 90003,
                        'status'           => 'COMPLETED',
                        'transaction_type' => 'paid_ads_charge',
                        'amount'           => -25000,
                        'current_balance'  => 1175000,
                        'create_time'      => 1787227200,
                        'order_sn'         => '',
                        'wallet_type'      => 'seller_balance',
                        'reason'           => 'Biaya iklan otomatis',
                    ],
                ],
                'more' => false,
            ],
        ];
    }

    public function test_service_fetches_normalizes_and_calculates_summary_without_database(): void
    {
        $store = $this->createShopeeStore();

        Http::fake([
            '*/api/v2/payment/get_wallet_transaction_list*' => Http::response($this->dummyShopeeApiResponse(), 200),
        ]);

        $service = app(ShopeeWalletTransactionService::class);
        $from = now()->subDays(5)->startOfDay();
        $to = now()->endOfDay();

        $result = $service->fetchTransactions($store, $from, $to);

        // Verifikasi struktur root
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('pagination', $result);

        // Verifikasi normalisasi 3 transaksi
        $items = $result['items'];
        $this->assertCount(3, $items);

        $first = $items[0];
        $this->assertSame('90001', $first['transaction_id']);
        $this->assertSame('MONEY_IN', $first['money_flow']);
        $this->assertEquals(350000.0, $first['amount']);
        $this->assertSame('Rp 350.000', $first['formatted_amount']);
        $this->assertSame('+ Rp 350.000', $first['signed_formatted_amount']);
        $this->assertSame('261023ABCD1234', $first['order_sn']);
        $this->assertSame('Pelepasan Escrow (Penjualan Selesai)', $first['transaction_type_label']);

        $second = $items[1];
        $this->assertSame('90002', $second['transaction_id']);
        $this->assertSame('MONEY_OUT', $second['money_flow']);
        $this->assertEquals(150000.0, $second['amount']);
        $this->assertSame('- Rp 150.000', $second['signed_formatted_amount']);
        $this->assertSame('Pengajuan Penarikan Saldo ke Bank', $second['transaction_type_label']);

        // Verifikasi ringkasan (summary)
        $summary = $result['summary'];
        $this->assertSame(3, $summary['total_count']);
        $this->assertEquals(350000.0, $summary['total_money_in']);
        $this->assertEquals(175000.0, $summary['total_money_out']); // 150000 + 25000
        $this->assertEquals(175000.0, $summary['net_amount']);       // 350000 - 175000

        // Verifikasi STRICT: Tidak ada data yang tersimpan ke tabel database mana pun
        $this->assertDatabaseCount('marketplace_payouts', 0);
    }

    public function test_service_throws_exception_on_non_shopee_store_or_api_error(): void
    {
        $tiktokChannel = Channel::firstOrCreate(['code' => 'tiktok'], ['name' => 'TikTok Shop']);
        $tiktokStore = Store::create([
            'channel_id' => $tiktokChannel->id,
            'code'       => 'TT-01',
            'name'       => 'Toko TikTok',
            'status'     => 'active',
            'is_active'  => true,
        ]);

        $service = app(ShopeeWalletTransactionService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('bukan channel Shopee');

        $service->fetchTransactions($tiktokStore, now()->subDays(2), now());
    }

    public function test_controller_index_returns_view(): void
    {
        $user = $this->createTestUser('owner');
        $this->createShopeeStore();

        $response = $this->actingAs($user)->get(route('marketplace.shopee.transactions'));

        $response->assertOk();
        $response->assertViewIs('marketplace.shopee.transactions');
        $response->assertViewHas(['stores', 'transactionTypes']);
        $response->assertSee('Transaksi Shopee');
        $response->assertSee(route('marketplace.shopee.transactions', absolute: false));
    }

    public function test_controller_data_endpoint_returns_json_live_data(): void
    {
        $user = $this->createTestUser('admin');
        $store = $this->createShopeeStore();

        Http::fake([
            '*/api/v2/payment/get_wallet_transaction_list*' => Http::response($this->dummyShopeeApiResponse(), 200),
        ]);

        $response = $this->actingAs($user)->getJson(route('marketplace.api.shopee-transactions', [
            'store'     => $store->id,
            'date_from' => now()->subDays(6)->toDateString(),
            'date_to'   => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'source'  => 'shopee_live',
        ]);
        $response->assertJsonPath('data.summary.total_count', 3);
        $response->assertJsonPath('data.items.0.transaction_id', '90001');

        // Pastikan database tetap kosong
        $this->assertDatabaseCount('marketplace_payouts', 0);
    }

    public function test_controller_validates_15_days_limit(): void
    {
        $user = $this->createTestUser('admin');
        $store = $this->createShopeeStore();

        $response = $this->actingAs($user)->getJson(route('marketplace.api.shopee-transactions', [
            'store'     => $store->id,
            'date_from' => now()->subDays(20)->toDateString(),
            'date_to'   => now()->toDateString(),
        ]));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('maksimal 15 hari', $response->json('message'));
    }

    public function test_controller_export_streams_csv_without_database(): void
    {
        $user = $this->createTestUser('admin');
        $store = $this->createShopeeStore();

        Http::fake([
            '*/api/v2/payment/get_wallet_transaction_list*' => Http::response($this->dummyShopeeApiResponse(), 200),
        ]);

        $response = $this->actingAs($user)->get(route('marketplace.api.shopee-transactions.export', [
            'store'     => $store->id,
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to'   => now()->toDateString(),
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $content = $response->streamedContent();

        $this->assertStringContainsString('Waktu Transaksi', $content);
        $this->assertStringContainsString('ID Transaksi', $content);
        $this->assertStringContainsString('Tipe Transaksi', $content);
        $this->assertStringContainsString('90001', $content);
        $this->assertStringContainsString('261023ABCD1234', $content);
        $this->assertStringContainsString('350000', $content);

        // Pastikan database tetap kosong
        $this->assertDatabaseCount('marketplace_payouts', 0);
    }

    public function test_artisan_command_fetches_and_displays_transactions(): void
    {
        $store = $this->createShopeeStore('SHP-CLI');

        Http::fake([
            '*/api/v2/payment/get_wallet_transaction_list*' => Http::response($this->dummyShopeeApiResponse(), 200),
        ]);

        $this->artisan('shopee:fetch-transactions', [
            'store'  => (string) $store->id,
            '--from' => now()->subDays(4)->toDateString(),
            '--to'   => now()->toDateString(),
        ])
            ->expectsOutputToContain('Total Transaksi')
            ->expectsOutputToContain('Pelepasan pesanan selesai')
            ->assertExitCode(0);

        // Pastikan database tetap kosong
        $this->assertDatabaseCount('marketplace_payouts', 0);
    }
}
