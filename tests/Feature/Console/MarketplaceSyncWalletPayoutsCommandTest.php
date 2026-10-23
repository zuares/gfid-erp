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

    public function test_command_rejects_ranges_longer_than_fifteen_days(): void
    {
        $this->artisan('marketplace:sync-wallet-payouts', [
            '--from' => '2026-08-01',
            '--to' => '2026-08-17',
        ])->expectsOutputToContain('Rentang payout maksimal 15 hari.')->assertFailed();
    }
}
