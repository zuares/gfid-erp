<?php

namespace Tests\Feature\Console;

use App\Models\Channel;
use App\Models\MarketplaceFinanceSyncRun;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceSettlementSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceSyncWalletPayoutsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_syncs_connected_shopee_stores_and_uses_safe_window(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'WALLET-1',
            'name' => 'Toko Wallet',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'token'],
            'token_expires_at' => now()->addDay(),
        ]);

        $this->mock(MarketplaceSettlementSyncService::class, function (MockInterface $mock) use ($store): void {
            $mock->shouldReceive('syncWalletPayouts')
                ->once()
                ->withArgs(function (Store $actual, $from, $to, int $pageSize) use ($store): bool {
                    return $actual->is($store)
                        && $from->toDateString() === '2026-08-01'
                        && $to->toDateString() === '2026-08-10'
                        && $pageSize === 50;
                })
                ->andReturn(['status' => 'synced', 'found' => 1, 'created' => 1, 'updated' => 0, 'invalid' => 0, 'pages' => 1]);
        });

        $this->artisan('marketplace:sync-wallet-payouts', [
            '--from' => '2026-08-01',
            '--to' => '2026-08-10',
            '--page-size' => 50,
            '--trigger' => 'schedule',
        ])
            ->expectsOutputToContain('Toko Wallet: status=synced found=1 created=1')
            ->assertSuccessful();

        $this->assertDatabaseHas('marketplace_finance_sync_runs', [
            'trigger' => 'wallet_payout_schedule',
            'status' => 'success',
        ]);
        $this->assertStringContainsString('"scope":"wallet_payout"', MarketplaceFinanceSyncRun::latest('id')->value('output'));
    }

    public function test_command_splits_long_ranges_into_api_safe_windows(): void
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'WALLET-BACKFILL',
            'name' => 'Toko Backfill',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'token'],
            'token_expires_at' => now()->addDay(),
        ]);
        $ranges = [];
        $this->mock(MarketplaceSettlementSyncService::class, function (MockInterface $mock) use ($store, &$ranges): void {
            $mock->shouldReceive('syncWalletPayouts')
                ->twice()
                ->withArgs(function (Store $actual, $from, $to, int $pageSize) use ($store, &$ranges): bool {
                    $ranges[] = [$from->toDateString(), $to->toDateString()];

                    return $actual->is($store) && $pageSize === 100;
                })
                ->andReturn(['status' => 'synced', 'found' => 1, 'created' => 1, 'updated' => 0, 'invalid' => 0, 'pages' => 1]);
        });

        $this->artisan('marketplace:sync-wallet-payouts', [
            '--from' => '2026-08-01',
            '--to' => '2026-08-17',
        ])->expectsOutputToContain('chunks=2')->assertSuccessful();

        $this->assertSame([
            ['2026-08-01', '2026-08-15'],
            ['2026-08-16', '2026-08-17'],
        ], $ranges);
    }
}
