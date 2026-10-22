<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureModuleAccess;
use App\Models\Channel;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplacePayoutTest extends TestCase
{
    use RefreshDatabase;

    private Channel $shopee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([Authenticate::class, EnsureModuleAccess::class]);
        $this->shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
    }

    private function store(?Channel $channel = null): Store
    {
        return Store::create([
            'channel_id' => ($channel ?: $this->shopee)->id,
            'code' => 'PAYOUT-'.fake()->unique()->numerify('####'),
            'name' => 'Toko Payout',
            'status' => 'active',
            'is_active' => true,
            'external_shop_id' => '123456',
            'credentials' => ['access_token' => 'test-token'],
            'token_expires_at' => now()->addDay(),
        ]);
    }

    public function test_payout_page_is_available(): void
    {
        $this->get(route('marketplace.payout'))
            ->assertOk()
            ->assertSee('Payout Shopee')
            ->assertSee('get_wallet_transaction_list')
            ->assertSee('seller lokal Shopee Indonesia')
            ->assertSee('tanpa cache database');
    }

    public function test_info_forwards_cursor_date_window_and_normalizes_payout(): void
    {
        $store = $this->store();

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store): void {
            $mock->shouldReceive('getWalletTransactionList')
                ->once()
                ->withArgs(function (Store $actual, int $pageNo, int $pageSize, int $from, int $to, string $moneyFlow, string $transactionType, ?string $walletType, string $transactionTabType) use ($store): bool {
                    return $actual->is($store)
                        && $pageNo === 0
                        && $pageSize === 100
                        && $from === Carbon::createFromFormat('Y-m-d', '2026-08-01', config('app.timezone'))->startOfDay()->timestamp
                        && $to === Carbon::createFromFormat('Y-m-d', '2026-08-10', config('app.timezone'))->endOfDay()->timestamp
                        && $moneyFlow === 'MONEY_OUT'
                        && $transactionType === '201'
                        && $walletType === null
                        && $transactionTabType === 'wallet_withdrawals';
                })
                ->andReturn([
                    'response' => [
                        'transaction_list' => [[
                            'transaction_id' => 'TX-1',
                            'transaction_type' => 'withdrawal_created',
                            'amount' => -1600000,
                            'create_time' => 1754395200,
                            'status' => 'COMPLETED',
                            'reason' => 'Withdrawal to bank',
                        ]],
                        'more' => true,
                    ],
                ]);
        });

        $this->getJson("/api/marketplace/stores/{$store->id}/payout-info?date_from=2026-08-01&date_to=2026-08-10")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.from_currency', 'IDR')
            ->assertJsonPath('data.items.0.payout_currency', 'IDR')
            ->assertJsonPath('data.items.0.payout_amount', 1600000)
            ->assertJsonPath('data.items.0.transaction_id', 'TX-1')
            ->assertJsonPath('data.more', true)
            ->assertJsonPath('data.next_cursor', '100');
    }

    public function test_detail_normalizes_escrow_and_adjustments(): void
    {
        $store = $this->store();

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store): void {
            $mock->shouldReceive('getWalletTransactionList')
                ->once()
                ->withArgs(fn (Store $actual, int $pageNo, int $pageSize, int $from, int $to, string $moneyFlow, string $transactionType, ?string $walletType, string $transactionTabType): bool => $actual->is($store)
                    && $pageNo === 100
                    && $pageSize === 100
                    && $from === Carbon::createFromFormat('Y-m-d', '2026-08-01', config('app.timezone'))->startOfDay()->timestamp
                    && $to === Carbon::createFromFormat('Y-m-d', '2026-08-10', config('app.timezone'))->endOfDay()->timestamp
                    && $moneyFlow === 'MONEY_OUT'
                    && $transactionType === '201'
                    && $walletType === null
                    && $transactionTabType === 'wallet_withdrawals')
                ->andReturn([
                    'response' => [
                        'transaction_list' => [[
                            'transaction_id' => 'TX-1',
                            'transaction_type' => 'withdrawal_created',
                            'amount' => -1600000,
                            'create_time' => 1754395200,
                            'status' => 'COMPLETED',
                            'reason' => 'Withdrawal to bank',
                        ]],
                        'more' => false,
                    ],
                    '_meta' => ['http_status' => 200],
                ]);
        });

        $this->getJson("/api/marketplace/stores/{$store->id}/payout-detail?date_from=2026-08-01&date_to=2026-08-10&page_no=2")
            ->assertOk()
            ->assertJsonPath('data.items.0.payout_info.payout_amount', 1600000)
            ->assertJsonPath('data.items.0.payout_info.transaction_id', 'TX-1')
            ->assertJsonPath('data.items.0.escrow_list', [])
            ->assertJsonPath('data.items.0.offline_adjustment_list', [])
            ->assertJsonMissingPath('data.raw_response._meta');
    }

    public function test_payout_rejects_window_longer_than_fifteen_days(): void
    {
        $store = $this->store();
        $gateway = $this->mock(MarketplaceApiGateway::class);
        $gateway->shouldNotReceive('getPayoutInfo');

        $this->getJson("/api/marketplace/stores/{$store->id}/payout-info?date_from=2026-08-01&date_to=2026-08-16")
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Rentang tanggal maksimal 15 hari sesuai batas endpoint wallet payout Shopee.');
    }

    public function test_non_shopee_store_is_rejected(): void
    {
        $tiktok = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = $this->store($tiktok);

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('getPayoutInfo');
        });

        $this->getJson("/api/marketplace/stores/{$store->id}/payout-info?date_from=2026-08-01&date_to=2026-08-10")
            ->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Modul payout hanya tersedia untuk toko Shopee.');
    }

    public function test_unconnected_shopee_store_is_rejected_without_calling_api(): void
    {
        $store = $this->store();
        $store->update(['credentials' => null, 'token_expires_at' => null]);

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('getPayoutInfo');
        });

        $this->getJson("/api/marketplace/stores/{$store->id}/payout-info?date_from=2026-08-01&date_to=2026-08-10")
            ->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Toko Shopee belum terhubung. Hubungkan toko terlebih dahulu sebelum mengambil payout.');
    }
}
