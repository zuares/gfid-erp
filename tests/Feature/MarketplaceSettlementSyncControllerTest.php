<?php

namespace Tests\Feature;

use App\Http\Controllers\MarketplaceController;
use App\Models\Channel;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
        return Store::create([
            'channel_id' => $this->shopee->id,
            'code' => 'S-' . rand(1000, 9999),
            'name' => 'Test Store',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'dummy-token'],
            'token_expires_at' => now()->addDay(),
        ]);
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
}
