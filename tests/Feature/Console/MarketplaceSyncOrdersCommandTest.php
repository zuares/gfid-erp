<?php

namespace Tests\Feature\Console;

use App\Models\Channel;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceSyncOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Channel $shopee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
    }

    private function createStore(string $status = 'CONNECTED'): Store
    {
        $store = Store::create([
            'channel_id' => $this->shopee->id,
            'code' => 'S' . rand(100, 999),
            'name' => 'Store ' . rand(100, 999),
            'status'     => 'active',
            'credentials' => [
                'access_token' => $status === 'NOT_CONNECTED' ? null : 'dummy_token',
            ],
            'token_expires_at' => $status === 'CONNECTED' ? now()->addDays(1) : ($status === 'NOT_CONNECTED' ? null : now()->subDays(1)),
        ]);
        return $store;
    }

    public function test_semua_toko_terhubung_keduanya_diproses_dan_sukses()
    {
        $storeA = $this->createStore();
        $storeB = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) use ($storeA, $storeB) {
            $mock->shouldReceive('syncOrders')
                ->withArgs(function ($s) use ($storeA) { return $s->id === $storeA->id; })
                ->once()
                ->andReturn(['new' => 2, 'updated' => 0]);
                
            $mock->shouldReceive('syncOrders')
                ->withArgs(function ($s) use ($storeB) { return $s->id === $storeB->id; })
                ->once()
                ->andReturn(['new' => 1, 'updated' => 1]);
        });

        $this->artisan('marketplace:sync-orders')
            ->expectsOutputToContain("Ditemukan: 2 toko Shopee aktif")
            ->expectsOutputToContain("[1/2] {$storeA->name}")
            ->expectsOutputToContain("[2/2] {$storeB->name}")
            ->expectsOutputToContain("Hasil: 2 order baru")
            ->expectsOutputToContain("Berhasil: 2 toko")
            ->assertSuccessful();
    }

    public function test_satu_toko_belum_terhubung_dilewati()
    {
        $storeA = $this->createStore('CONNECTED');
        $storeB = $this->createStore('NOT_CONNECTED');

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) use ($storeA) {
            $mock->shouldReceive('syncOrders')
                ->withArgs(function ($s) use ($storeA) { return $s->id === $storeA->id; })
                ->once()
                ->andReturn(['new' => 2, 'updated' => 0]);
        });

        $this->artisan('marketplace:sync-orders')
            ->expectsOutputToContain("[2/2] {$storeB->name}")
            ->expectsOutputToContain("Status koneksi: Belum terhubung")
            ->expectsOutputToContain("Hasil: Dilewati")
            ->expectsOutputToContain("Berhasil: 1 toko")
            ->expectsOutputToContain("Dilewati: 1 toko")
            ->assertSuccessful();
    }

    public function test_satu_toko_exception_tetap_proses_toko_lain()
    {
        $storeA = $this->createStore();
        $storeB = $this->createStore();
        $storeC = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) use ($storeA, $storeB, $storeC) {
            $mock->shouldReceive('syncOrders')->withArgs(function ($s) use ($storeA) { return $s->id === $storeA->id; })
                 ->once()->andReturn(['new' => 1]);
                 
            $mock->shouldReceive('syncOrders')->withArgs(function ($s) use ($storeB) { return $s->id === $storeB->id; })
                 ->once()->andThrow(new \RuntimeException("API Error Shopee"));
                 
            $mock->shouldReceive('syncOrders')->withArgs(function ($s) use ($storeC) { return $s->id === $storeC->id; })
                 ->once()->andReturn(['new' => 1]);
        });

        $this->artisan('marketplace:sync-orders')
            ->expectsOutputToContain("Hasil: Gagal (API Error Shopee)")
            ->expectsOutputToContain("Berhasil: 2 toko")
            ->expectsOutputToContain("Gagal: 1 toko")
            ->assertFailed(); // Exit code gagal jika ada yang gagal
    }

    public function test_lock_aktif_toko_dilewati()
    {
        $storeA = $this->createStore();

        // Aktifkan lock
        Cache::lock("sync_store_{$storeA->id}", 60)->get();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('syncOrders');
        });

        $this->artisan('marketplace:sync-orders')
            ->expectsOutputToContain("Hasil: Dilewati (Toko sedang disinkronkan oleh proses lain)")
            ->expectsOutputToContain("Dilewati: 1 toko")
            ->assertSuccessful();
            
        // Release lock for cleanup just in case
        Cache::lock("sync_store_{$storeA->id}")->forceRelease();
    }

    public function test_filter_store_hanya_memproses_satu_toko()
    {
        $storeA = $this->createStore();
        $storeB = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) use ($storeB) {
            $mock->shouldReceive('syncOrders')
                ->withArgs(function ($s) use ($storeB) { return $s->id === $storeB->id; })
                ->once()
                ->andReturn(['new' => 1]);
        });

        $this->artisan("marketplace:sync-orders", ['--store' => $storeB->id])
            ->expectsOutputToContain("Ditemukan: 1 toko")
            ->expectsOutputToContain("[1/1] {$storeB->name}")
            ->assertSuccessful();
    }

    public function test_dry_run_tidak_memanggil_api()
    {
        $storeA = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('syncOrders'); // API tidak dipanggil
        });

        $this->artisan('marketplace:sync-orders', ['--dry-run' => true])
            ->expectsOutputToContain("Hasil: Dry-run OK (tidak ada API call)")
            ->assertSuccessful();
    }
    
    public function test_scheduler_terdaftar()
    {
        Artisan::call('schedule:list');
        $output = Artisan::output();
        $this->assertStringContainsString('sync-orders', $output);
    }
}
