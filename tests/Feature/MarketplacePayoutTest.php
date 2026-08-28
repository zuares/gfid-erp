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
            ->assertSee('get_payout_info')
            ->assertSee('get_payout_detail')
            ->assertSee('tanpa cache database');
    }

    public function test_info_forwards_cursor_date_window_and_normalizes_payout(): void
    {
        $store = $this->store();

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store): void {
            $mock->shouldReceive('getPayoutInfo')
                ->once()
                ->withArgs(function (Store $actual, int $from, int $to, string $cursor, int $pageSize) use ($store): bool {
                    return $actual->is($store)
                        && $from === Carbon::createFromFormat('Y-m-d', '2026-08-01', config('app.timezone'))->startOfDay()->timestamp
                        && $to === Carbon::createFromFormat('Y-m-d', '2026-08-10', config('app.timezone'))->endOfDay()->timestamp
                        && $cursor === 'CURSOR-X'
                        && $pageSize === 100;
                })
                ->andReturn([
                    'response' => [
                        'payout_list' => [
                            'from_currency' => 'USD',
                            'payout_currency' => 'IDR',
                            'from_amount' => 100,
                            'payout_amount' => 1600000,
                            'exchange_rate' => '16000',
                            'payout_time' => 1754395200,
                            'pay_service' => 'payoneer',
                            'payee_id' => 'PAYEE-1',
                            'encrypted_payout_id' => 'ENC-1',
                        ],
                        'more' => true,
                        'next_cursor' => 'CURSOR-Y',
                    ],
                ]);
        });

        $this->getJson("/api/marketplace/stores/{$store->id}/payout-info?date_from=2026-08-01&date_to=2026-08-10&cursor=CURSOR-X")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.from_currency', 'USD')
            ->assertJsonPath('data.items.0.payout_currency', 'IDR')
            ->assertJsonPath('data.items.0.payout_amount', 1600000)
            ->assertJsonPath('data.items.0.encrypted_payout_id', 'ENC-1')
            ->assertJsonPath('data.more', true)
            ->assertJsonPath('data.next_cursor', 'CURSOR-Y');
    }

    public function test_detail_normalizes_escrow_and_adjustments(): void
    {
        $store = $this->store();

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store): void {
            $mock->shouldReceive('getPayoutDetail')
                ->once()
                ->withArgs(fn (Store $actual, int $from, int $to, int $pageNo, int $pageSize): bool => $actual->is($store)
                    && $from === Carbon::createFromFormat('Y-m-d', '2026-08-01', config('app.timezone'))->startOfDay()->timestamp
                    && $to === Carbon::createFromFormat('Y-m-d', '2026-08-10', config('app.timezone'))->endOfDay()->timestamp
                    && $pageNo === 2
                    && $pageSize === 100)
                ->andReturn([
                    'response' => [
                        'payout_list' => [[
                            'payout_info' => [
                                'payout_currency' => 'IDR',
                                'payout_amount' => 1600000,
                                'payout_time' => 1754395200,
                            ],
                            'escrow_list' => [['order_sn' => 'ORDER-1', 'escrow_amount' => 1500000, 'currency' => 'USD']],
                            'offline_adjustment_list' => [['adjustment_amount' => -100]],
                        ]],
                        'more' => false,
                    ],
                    '_meta' => ['http_status' => 200],
                ]);
        });

        $this->getJson("/api/marketplace/stores/{$store->id}/payout-detail?date_from=2026-08-01&date_to=2026-08-10&page_no=2")
            ->assertOk()
            ->assertJsonPath('data.items.0.payout_info.payout_amount', 1600000)
            ->assertJsonPath('data.items.0.escrow_list.0.order_sn', 'ORDER-1')
            ->assertJsonPath('data.items.0.escrow_list.0.escrow_amount', 1500000)
            ->assertJsonPath('data.items.0.offline_adjustment_list.0.adjustment_amount', -100)
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
            ->assertJsonPath('message', 'Rentang tanggal maksimal 15 hari sesuai batas endpoint payout Shopee.');
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
