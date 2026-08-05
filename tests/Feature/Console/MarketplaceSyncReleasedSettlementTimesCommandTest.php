<?php

namespace Tests\Feature\Console;

use App\Models\Channel;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceSyncReleasedSettlementTimesCommandTest extends TestCase
{
    use RefreshDatabase;

    private Channel $shopee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
    }

    private function createStore(string $name): Store
    {
        return Store::create([
            'channel_id' => $this->shopee->id,
            'code' => 'S' . rand(1000, 9999),
            'name' => $name,
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'dummy'],
            'token_expires_at' => now()->addDay(),
        ]);
    }

    public function test_satu_toko_gagal_tidak_menghentikan_toko_lain(): void
    {
        $first = $this->createStore('Toko Release 1');
        $second = $this->createStore('Toko Release 2');

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) use ($first, $second): void {
            $mock->shouldReceive('syncReleasedSettlementTimes')
                ->twice()
                ->andReturnUsing(function (Store $store) use ($first, $second): array {
                    if ($store->id === $first->id) {
                        return ['found' => 2, 'updated' => 2, 'unmatched' => 0, 'errors' => 0];
                    }

                    if ($store->id === $second->id) {
                        throw new \RuntimeException('Shopee release endpoint timeout');
                    }

                    return [];
                });
        });

        $this->artisan('marketplace:sync-released-settlements', [
            '--from' => '2026-08-01',
            '--to' => '2026-08-05',
        ])
            ->expectsOutputToContain('Toko Release 1: ditemukan=2 updated=2')
            ->expectsOutputToContain('Toko Release 2: enrichment gagal sementara')
            ->expectsOutputToContain('Updated   : 2')
            ->assertSuccessful();
    }

    public function test_rentang_tanggal_invalid_ditolak(): void
    {
        $this->artisan('marketplace:sync-released-settlements', [
            '--from' => '2026-08-05',
            '--to' => '2026-08-01',
        ])
            ->expectsOutputToContain('--from tidak boleh lebih besar dari --to')
            ->assertFailed();
    }
}
