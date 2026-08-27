<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use App\Http\Middleware\EnsureModuleAccess;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceEscrowTest extends TestCase
{
    use RefreshDatabase;

    private Channel $shopee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            Authenticate::class,
            EnsureModuleAccess::class,
        ]);
        $this->shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
    }

    private function store(?Channel $channel = null): Store
    {
        return Store::create([
            'channel_id' => ($channel ?: $this->shopee)->id,
            'code' => 'ESCROW-'.fake()->unique()->numerify('####'),
            'name' => 'Toko Escrow',
            'status' => 'active',
            'is_active' => true,
            'external_shop_id' => '123456',
            'credentials' => ['access_token' => 'test-token'],
            'token_expires_at' => now()->addDay(),
        ]);
    }

    public function test_escrow_page_is_available(): void
    {
        $this->get(route('marketplace.escrow'))
            ->assertOk()
            ->assertSee('Escrow Shopee')
            ->assertSee('tanpa cache database');
    }

    public function test_list_forwards_date_window_and_normalizes_payload(): void
    {
        $store = $this->store();
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store): void {
            $mock->shouldReceive('getEscrowList')
                ->once()
                ->withArgs(function (Store $actual, int $from, int $to, int $pageNo, int $pageSize) use ($store): bool {
                    return $actual->is($store)
                        && $from === Carbon::createFromFormat('Y-m-d', '2026-08-01', config('app.timezone'))->startOfDay()->timestamp
                        && $to === Carbon::createFromFormat('Y-m-d', '2026-08-10', config('app.timezone'))->endOfDay()->timestamp
                        && $pageNo === 2
                        && $pageSize === 100;
                })
                ->andReturn([
                    'response' => [
                        'escrow_list' => [
                            ['order_sn' => 'ORDER-1', 'payout_amount' => 12345.5, 'escrow_release_time' => 1754395200],
                        ],
                        'more' => true,
                    ],
                ]);
        });

        $this->getJson("/api/marketplace/stores/{$store->id}/escrow-list?date_from=2026-08-01&date_to=2026-08-10&page_no=2")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'shopee_live')
            ->assertJsonPath('data.items.0.order_sn', 'ORDER-1')
            ->assertJsonPath('data.items.0.payout_amount', 12345.5)
            ->assertJsonPath('data.more', true)
            ->assertJsonPath('data.page_no', 2);
    }

    public function test_list_rejects_window_longer_than_fifteen_days_without_calling_api(): void
    {
        $store = $this->store();
        $gateway = $this->mock(MarketplaceApiGateway::class);
        $gateway->shouldNotReceive('getEscrowList');

        $this->getJson("/api/marketplace/stores/{$store->id}/escrow-list?date_from=2026-08-01&date_to=2026-08-16")
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Rentang tanggal maksimal 15 hari sesuai batas endpoint Shopee.');
    }

    public function test_detail_returns_income_and_raw_response_without_persisting_data(): void
    {
        $store = $this->store();

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store): void {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->withArgs(fn (Store $actual, string $orderSn): bool => $actual->is($store) && $orderSn === 'ORDER-DETAIL')
                ->andReturn([
                    'response' => [
                        'order_sn' => 'ORDER-DETAIL',
                        'buyer_user_name' => 'buyer-1',
                        'return_order_sn_list' => ['RETURN-1'],
                        'order_income' => ['escrow_amount' => 98765, 'commission_fee' => 1234],
                    ],
                    '_meta' => ['http_status' => 200],
                ]);
        });

        $this->getJson("/api/marketplace/stores/{$store->id}/escrow-detail?order_sn=ORDER-DETAIL")
            ->assertOk()
            ->assertJsonPath('data.order_sn', 'ORDER-DETAIL')
            ->assertJsonPath('data.income.escrow_amount', 98765)
            ->assertJsonPath('data.raw_response.response.order_income.commission_fee', 1234)
            ->assertJsonMissingPath('data.raw_response._meta');
    }

    public function test_non_shopee_store_is_rejected(): void
    {
        $tiktok = Channel::create(['code' => 'tiktok', 'name' => 'TikTok Shop']);
        $store = $this->store($tiktok);

        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('getEscrowDetail');
        });

        $this->getJson("/api/marketplace/stores/{$store->id}/escrow-detail?order_sn=ORDER-X")
            ->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Modul escrow hanya tersedia untuk toko Shopee.');
    }
}
