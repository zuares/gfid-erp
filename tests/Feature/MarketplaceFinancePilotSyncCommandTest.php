<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceSettlementSyncService;
use App\Services\Marketplace\MarketplaceApiGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceFinancePilotSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_resolves_store_by_name_without_calling_payout_api(): void
    {
        $this->store();
        $this->mock(MarketplaceSettlementSyncService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('syncPayouts');
        });

        $this->artisan('marketplace:finance-pilot-sync', [
            '--store' => 'Greatfit.id',
            '--from' => '2026-10-01',
            '--to' => '2026-10-02',
        ])->assertExitCode(0);

        $this->assertSame(0, MarketplaceFinanceSettlement::count());
    }

    public function test_apply_requires_a_connected_store_before_writing(): void
    {
        $this->store();
        $this->mock(MarketplaceSettlementSyncService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('syncPayouts');
        });

        $this->artisan('marketplace:finance-pilot-sync', [
            '--store' => 'Greatfit.id',
            '--from' => '2026-10-01',
            '--to' => '2026-10-02',
            '--apply' => true,
        ])->assertExitCode(1);
    }

    public function test_probe_reads_payout_order_references_without_writing_finance(): void
    {
        $this->store(['credentials' => ['access_token' => 'test-token']]);
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getPayoutInfo')->once()->andReturn([
                'response' => ['payout_list' => [['payout_info' => ['encrypted_payout_id' => 'PILOT-1']]], 'more' => false],
            ]);
            $mock->shouldReceive('getPayoutDetail')->once()->andReturn([
                'response' => ['payout_list' => [[
                    'payout_info' => ['encrypted_payout_id' => 'PILOT-1'],
                    'escrow_list' => [['order_sn' => 'ORDER-1', 'escrow_amount' => 100000]],
                ]], 'more' => false],
            ]);
        });
        $this->mock(MarketplaceSettlementSyncService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('syncPayouts');
        });

        $this->artisan('marketplace:finance-pilot-sync', [
            '--store' => 'Greatfit.id',
            '--from' => '2026-10-01',
            '--to' => '2026-10-02',
            '--probe-payout' => true,
        ])->expectsOutputToContain('order_refs=1')->assertExitCode(0);

        $this->assertDatabaseCount('marketplace_finance_settlements', 0);
        $this->assertDatabaseCount('marketplace_financial_transactions', 0);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_apply_stops_before_backfill_when_payout_probe_fails(): void
    {
        $this->store(['credentials' => ['access_token' => 'test-token']]);
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getPayoutInfo')->once()->andReturn(['error' => 'unsupported']);
            $mock->shouldNotReceive('getPayoutDetail');
        });
        $this->mock(MarketplaceSettlementSyncService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('syncPayouts');
        });

        $this->artisan('marketplace:finance-pilot-sync', [
            '--store' => 'Greatfit.id',
            '--from' => '2026-10-01',
            '--to' => '2026-10-02',
            '--apply' => true,
        ])->assertExitCode(1);

        $this->assertDatabaseCount('marketplace_finance_settlements', 0);
        $this->assertDatabaseCount('marketplace_financial_transactions', 0);
    }

    public function test_connected_store_can_sync_payout_without_posting_journal(): void
    {
        $store = $this->store(['credentials' => ['access_token' => 'test-token']]);
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getPayoutInfo')->once()->andReturn(['response' => ['payout_list' => [], 'more' => false]]);
            $mock->shouldReceive('getPayoutDetail')->once()->andReturn(['response' => ['payout_list' => [], 'more' => false]]);
        });
        $this->mock(MarketplaceSettlementSyncService::class, function (MockInterface $mock) use ($store): void {
            $mock->shouldReceive('syncPayouts')->once()
                ->withArgs(fn ($actualStore, $from, $to): bool => $actualStore->id === $store->id
                    && $from->toDateString() === '2026-10-01'
                    && $to->toDateString() === '2026-10-02')
                ->andReturn(['status' => 'synced', 'found' => 1, 'synced' => 1, 'allocated' => 0, 'unmatched' => 0, 'errors' => 0]);
        });

        $this->artisan('marketplace:finance-pilot-sync', [
            '--store' => 'Greatfit.id',
            '--from' => '2026-10-01',
            '--to' => '2026-10-02',
            '--apply' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('journals', 0);
    }

    private function store(array $overrides = []): Store
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);

        return Store::create(array_merge([
            'channel_id' => $channel->id,
            'code' => 'GREATFIT-PILOT',
            'name' => 'Greatfit.id',
            'status' => 'active',
            'is_active' => true,
        ], $overrides));
    }
}
