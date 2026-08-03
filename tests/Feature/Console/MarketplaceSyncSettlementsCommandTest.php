<?php

namespace Tests\Feature\Console;

use App\Models\Channel;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceSyncSettlementsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Channel $shopee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopee = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
    }

    private function createStore(?string $name = null): Store
    {
        $store = Store::create([
            'channel_id' => $this->shopee->id,
            'code'       => 'S' . rand(1000, 9999),
            'name'       => $name ?? ('Toko ' . rand(100, 999)),
            'status'     => 'active',
            'is_active'  => true,
            'credentials' => ['access_token' => 'dummy'],
            'token_expires_at' => now()->addDay(),
        ]);

        $this->mirrorLegacyMarketplaceStore($store);

        return $store;
    }

    /** Mirror legacy rows while the test exercises the canonical Store model. */
    private function mirrorLegacyMarketplaceStore(Store $store): void
    {
        $channelId = DB::table('marketplace_channels')->where('code', $this->shopee->code)->value('id');

        if (! $channelId) {
            $channelId = DB::table('marketplace_channels')->insertGetId([
                'code'       => $this->shopee->code,
                'name'       => $this->shopee->name,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('marketplace_stores')->insert([
            'id'         => $store->id,
            'channel_id' => $channelId,
            'name'       => $store->name,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrder(Store $store, string $status = 'COMPLETED', ?string $channelOrderId = null): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id'          => $store->id,
            'external_order_id' => 'EXT-' . rand(10000, 99999),
            'order_date'        => now(),
            'channel_order_id'  => $channelOrderId ?? ('ORDER-' . rand(100000, 999999)),
            'order_status'      => $status,
            'ordered_at'        => now()->subDay(),
        ]);
    }

    private function successResult(array $overrides = []): array
    {
        return array_merge([
            'found' => 1, 'processed' => 1, 'synced' => 1, 'skipped' => 0, 'errors' => 0,
            'last_processed_id' => 1, 'failed_order_ids' => [], 'message' => 'ok',
        ], $overrides);
    }

    // ── Validasi opsi ────────────────────────────────────────────────────────
    public function test_limit_di_luar_batas_ditolak()
    {
        $this->artisan('marketplace:sync-settlements', ['--limit' => 1000])
            ->expectsOutputToContain('--limit harus berupa angka antara 1 dan 500')
            ->assertFailed();
    }

    public function test_format_tanggal_invalid_ditolak()
    {
        $this->artisan('marketplace:sync-settlements', ['--from' => '01-07-2026'])
            ->expectsOutputToContain('--from harus berformat Y-m-d')
            ->assertFailed();
    }

    public function test_from_lebih_besar_dari_to_ditolak()
    {
        $this->artisan('marketplace:sync-settlements', ['--from' => '2026-07-10', '--to' => '2026-07-01'])
            ->expectsOutputToContain('--from harus lebih kecil atau sama dengan --to')
            ->assertFailed();
    }

    public function test_store_nonnumerik_ditolak()
    {
        $this->artisan('marketplace:sync-settlements', ['--store' => 'abc'])
            ->expectsOutputToContain('--store harus berupa ID numerik')
            ->assertFailed();
    }

    public function test_order_kosong_ditolak()
    {
        $this->artisan('marketplace:sync-settlements', ['--order' => ''])
            ->expectsOutputToContain('--order tidak boleh string kosong')
            ->assertFailed();
    }

    public function test_inspect_tanpa_order_ditolak()
    {
        $this->artisan('marketplace:sync-settlements', ['--inspect' => true])
            ->expectsOutputToContain('--inspect hanya valid bersama --order')
            ->assertFailed();
    }

    public function test_all_bersama_order_ditolak()
    {
        $this->artisan('marketplace:sync-settlements', ['--order' => 'ORDER-1', '--all' => true])
            ->expectsOutputToContain('--all tidak diperlukan saat --order dipakai')
            ->assertFailed();
    }

    // ── --store hanya memproses satu toko ───────────────────────────────────
    public function test_filter_store_hanya_memproses_satu_toko()
    {
        $storeA = $this->createStore();
        $storeB = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) use ($storeB) {
            $mock->shouldReceive('syncSettlements')
                ->withArgs(fn ($store) => $store->id === $storeB->id)
                ->once()
                ->andReturn($this->successResult());
        });

        $this->artisan('marketplace:sync-settlements', ['--store' => $storeB->id])
            ->expectsOutputToContain("[1/1] {$storeB->name}")
            ->assertSuccessful();
    }

    // ── --order ambigu lintas toko tanpa --store ───────────────────────────
    public function test_order_ambigu_lintas_toko_tanpa_store_ditolak()
    {
        $storeA = $this->createStore();
        $storeB = $this->createStore();
        $this->createOrder($storeA, 'COMPLETED', 'SAMA-123');
        $this->createOrder($storeB, 'COMPLETED', 'SAMA-123');

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('syncSettlements');
        });

        $this->artisan('marketplace:sync-settlements', ['--order' => 'SAMA-123'])
            ->expectsOutputToContain('AMBIGU')
            ->assertFailed();
    }

    public function test_order_dengan_store_eksplisit_pada_order_ambigu_berhasil_dipilih()
    {
        $storeA = $this->createStore();
        $storeB = $this->createStore();
        $this->createOrder($storeA, 'COMPLETED', 'SAMA-123');
        $this->createOrder($storeB, 'COMPLETED', 'SAMA-123');

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) use ($storeA) {
            $mock->shouldReceive('syncSettlements')
                ->withArgs(fn ($store) => $store->id === $storeA->id)
                ->once()
                ->andReturn($this->successResult());
        });

        $this->artisan('marketplace:sync-settlements', ['--order' => 'SAMA-123', '--store' => $storeA->id])
            ->assertSuccessful();
    }

    // ── --order belum eligible: tidak memanggil service sama sekali ───────
    public function test_order_belum_eligible_tidak_memanggil_service()
    {
        $store = $this->createStore();
        $this->createOrder($store, 'UNPAID', 'ORD-BELUM-ELIGIBLE');

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('syncSettlements');
        });

        $this->artisan('marketplace:sync-settlements', ['--order' => 'ORD-BELUM-ELIGIBLE'])
            ->expectsOutputToContain('BELUM eligible')
            ->assertSuccessful();
    }

    public function test_order_tidak_ditemukan_gagal()
    {
        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('syncSettlements');
        });

        $this->artisan('marketplace:sync-settlements', ['--order' => 'TIDAK-ADA'])
            ->expectsOutputToContain('tidak ditemukan')
            ->assertFailed();
    }

    // ── Lock aktif → toko dilewati ──────────────────────────────────────────
    public function test_lock_aktif_toko_dilewati()
    {
        $store = $this->createStore();
        Cache::lock("sync_settlements_store_{$store->id}", 60)->get();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('syncSettlements');
        });

        $this->artisan('marketplace:sync-settlements', ['--store' => $store->id])
            ->expectsOutputToContain('Dilewati (toko sedang disinkronkan proses lain)')
            ->assertSuccessful();

        Cache::lock("sync_settlements_store_{$store->id}")->forceRelease();
    }

    // ── --all berhenti ketika tidak ada kemajuan ────────────────────────────
    public function test_all_berhenti_ketika_tidak_ada_kemajuan()
    {
        $store = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            // Simulasi: cursor "macet" (last_processed_id tidak pernah lebih besar dari
            // afterId yang dikirim) dan semua order gagal terus — safety net HARUS
            // menghentikan loop setelah 2x batch tanpa kemajuan, bukan lanjut sampai
            // batas maksimum 20 batch.
            $mock->shouldReceive('syncSettlements')
                ->andReturn([
                    'found' => 3, 'processed' => 3, 'synced' => 0, 'skipped' => 0, 'errors' => 3,
                    'last_processed_id' => 0, 'failed_order_ids' => ['A', 'B', 'C'], 'message' => 'gagal terus',
                ]);
        });

        $this->artisan('marketplace:sync-settlements', ['--store' => $store->id, '--all' => true])
            ->expectsOutputToContain('berturut-turut tanpa kemajuan')
            ->assertFailed(); // seluruh batch error, synced=0 -> FAILURE
    }

    // ── Exit code: seluruh batch error → FAILURE ────────────────────────────
    public function test_seluruh_batch_error_exit_code_failure()
    {
        $store = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('syncSettlements')
                ->once()
                ->andReturn([
                    'found' => 2, 'processed' => 2, 'synced' => 0, 'skipped' => 0, 'errors' => 2,
                    'last_processed_id' => 5, 'failed_order_ids' => ['A', 'B'], 'message' => 'gagal semua',
                ]);
        });

        $this->artisan('marketplace:sync-settlements', ['--store' => $store->id])
            ->assertFailed();
    }

    // ── Exit code: sukses normal → SUCCESS ──────────────────────────────────
    public function test_sinkronisasi_sukses_exit_code_success()
    {
        $store = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('syncSettlements')
                ->once()
                ->andReturn($this->successResult(['found' => 5, 'processed' => 5, 'synced' => 4, 'skipped' => 1, 'errors' => 0]));
        });

        $this->artisan('marketplace:sync-settlements', ['--store' => $store->id])
            ->expectsOutputToContain('Synced    : 4')
            ->assertSuccessful();
    }

    public function test_toko_gagal_total_exception_exit_code_failure()
    {
        $store = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('syncSettlements')
                ->once()
                ->andThrow(new \RuntimeException('API Shopee down'));
        });

        $this->artisan('marketplace:sync-settlements', ['--store' => $store->id])
            ->expectsOutputToContain('Hasil: Gagal (API Shopee down)')
            ->assertFailed();
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // Observability command-level (Koreksi mapping 23 Juli 2026, Section 5 & 6)
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Menimpa kolom `credentials` langsung lewat query builder (bukan Eloquent),
     * dengan string yang BUKAN ciphertext valid hasil Crypt::encrypt() — memaksa
     * Illuminate\Contracts\Encryption\DecryptException saat cast 'encrypted:array'
     * pada model Store mencoba mendekripsi ulang di akses berikutnya.
     */
    private function createStoreWithCorruptCredentials(): Store
    {
        $store = $this->createStore();

        DB::table('stores')->where('id', $store->id)->update([
            'credentials' => 'ciphertext-rusak-tidak-valid-' . uniqid(),
        ]);

        return $store->fresh();
    }

    // ── 1. Durasi tampil pada output command ────────────────────────────────
    public function test_durasi_tampil_pada_output_command()
    {
        $store = $this->createStore();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('syncSettlements')
                ->once()
                ->andReturn($this->successResult());
        });

        // Sengaja TIDAK menguji angka durasi secara presisi — hanya memastikan
        // baris ringkasan durasi benar-benar tampil di output.
        $this->artisan('marketplace:sync-settlements', ['--store' => $store->id])
            ->expectsOutputToContain('Durasi')
            ->assertSuccessful();
    }

    // ── 2. Inspect menampilkan metadata panggilan API (sukses) ─────────────
    public function test_inspect_menampilkan_metadata_panggilan_api()
    {
        $store = $this->createStore();
        $this->createOrder($store, 'COMPLETED', 'ORDER-INSPECT-OK');

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('syncSettlements')
                ->once()
                ->andReturn([
                    'found' => 1,
                    'processed' => 1,
                    'synced' => 1,
                    'skipped' => 0,
                    'errors' => 0,
                    'last_call_meta' => [
                        'attempts' => 2,
                        'http_status' => 200,
                        'retry_after' => null,
                        'token_refreshed' => null,
                        'duration_ms' => 123.45,
                    ],
                ]);
        });

        $this->artisan('marketplace:sync-settlements', [
            '--order' => 'ORDER-INSPECT-OK',
            '--inspect' => true,
        ])
            ->expectsOutputToContain('HTTP status')
            ->expectsOutputToContain('Attempts')
            ->expectsOutputToContain('Retry-After')
            ->expectsOutputToContain('Token refreshed')
            ->expectsOutputToContain('Duration')
            ->doesntExpectOutputToContain('dummy') // nilai credentials TIDAK PERNAH ditampilkan
            ->assertSuccessful();
    }

    // ── 3. Inspect error tetap menampilkan metadata ─────────────────────────
    public function test_inspect_error_tetap_menampilkan_metadata()
    {
        $store = $this->createStore();
        $this->createOrder($store, 'COMPLETED', 'ORDER-INSPECT-ERROR');

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('syncSettlements')
                ->once()
                ->andReturn([
                    'found' => 1,
                    'processed' => 1,
                    'synced' => 0,
                    'skipped' => 0,
                    'errors' => 1,
                    'last_call_meta' => [
                        'attempts' => 3,
                        'http_status' => 429,
                        'retry_after' => '5',
                        'token_refreshed' => null,
                        'duration_ms' => 7000,
                    ],
                ]);
        });

        // Command tidak boleh crash: assertion di bawah sudah cukup untuk membuktikan
        // eksekusi berjalan sampai selesai (exception yang tidak tertangani akan
        // menggagalkan test ini dengan cara berbeda, bukan assertFailed()).
        $this->artisan('marketplace:sync-settlements', [
            '--order' => 'ORDER-INSPECT-ERROR',
            '--inspect' => true,
        ])
            ->expectsOutputToContain('HTTP status')
            ->expectsOutputToContain('Attempts')
            ->expectsOutputToContain('Retry-After')
            ->expectsOutputToContain('Token refreshed')
            ->expectsOutputToContain('Duration')
            ->expectsOutputToContain('Tidak ada settlement tersimpan')
            ->assertFailed(); // processed>0, synced=0, errors>0 -> FAILURE by design
    }

    // ── 4. DecryptException berhenti sebelum service dipanggil ─────────────
    public function test_decrypt_exception_berhenti_sebelum_service_dipanggil()
    {
        $store = $this->createStoreWithCorruptCredentials();

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('syncSettlements');
        });

        $this->artisan('marketplace:sync-settlements', ['--store' => $store->id])
            ->expectsOutputToContain('Gagal dekripsi kredensial')
            ->expectsOutputToContain('APP_KEY')
            ->doesntExpectOutputToContain('ciphertext-rusak-tidak-valid') // tidak membocorkan nilai mentah
            ->assertFailed(); // hanya toko ini yang dipilih (--store) -> storesFailed > 0 -> FAILURE
    }

    // ── 5. Metadata tidak tersedia tetap aman ────────────────────────────────
    public function test_inspect_tanpa_metadata_tetap_aman()
    {
        $store = $this->createStore();
        $this->createOrder($store, 'COMPLETED', 'ORDER-INSPECT-NOMETA');

        $this->mock(MarketplaceSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('syncSettlements')
                ->once()
                ->andReturn([
                    'found' => 1,
                    'processed' => 1,
                    'synced' => 1,
                    'skipped' => 0,
                    'errors' => 0,
                    'last_processed_id' => 1,
                    'failed_order_ids' => [],
                    // Sengaja tidak mengembalikan last_call_meta
                ]);
        });

        $this->artisan('marketplace:sync-settlements', [
            '--order' => 'ORDER-INSPECT-NOMETA',
            '--inspect' => true,
        ])
            ->expectsOutputToContain('--- Inspect: ORDER-INSPECT-NOMETA')
            ->expectsOutputToContain('Selesai')
            ->assertSuccessful();
    }
}
