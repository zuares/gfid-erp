<?php

namespace Tests\Feature\Database;

use App\Models\Channel;
use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Test migration 2026_07_23_000001_change_unique_constraint_on_marketplace_order_settlements.
 *
 * CATATAN: pakai RefreshDatabase (BUKAN DatabaseMigrations — sempat dicoba, dan
 * ternyata salah: DatabaseMigrations menjalankan migrate:rollback (yang memanggil
 * down() migrasi INI SENDIRI) di teardown SETIAP test, dan down() itu mengasumsikan
 * skema masih dalam kondisi konsisten dari up() migration asli. Tapi beberapa test di
 * sini sengaja mengubah skema langsung lewat Schema:: (dropCurrentUnique() /
 * restoreOldUniqueConstraint()) untuk mensimulasikan state "sebelum migration pernah
 * jalan" — itu bikin down() di teardown gagal karena index yang dicarinya sudah
 * diubah/dihapus manual oleh test. RefreshDatabase tidak treasa masalah ini karena
 * seluruh test dibungkus SATU transaksi yang di-ROLLBACK utuh di akhir (bukan dengan
 * memanggil down()), jadi mutasi skema manual ikut ter-undo otomatis.
 *
 * Migration ini SEMUA dijalankan lewat migration awal test suite, jadi di awal tiap
 * test skema SUDAH memakai unique constraint BARU (per-store). Untuk menguji skenario
 * preflight up() (data lama sudah duplikat / store_id tidak valid), test melepas dulu
 * constraint yang sedang aktif secara eksplisit sebelum insert data uji — ini
 * mensimulasikan kondisi "sebelum migration pernah dijalankan" tanpa mengandalkan
 * urutan migrasi riil.
 *
 * Migration TIDAK dijalankan ke database development/production sungguhan di sini —
 * murni terhadap SQLite in-memory milik test suite (lihat phpunit.xml).
 */
class MarketplaceOrderSettlementsUniqueConstraintMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_FILE = 'change_unique_constraint_on_marketplace_order_settlements.php';

    protected Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
    }

    private function migrationInstance(): Migration
    {
        $files = glob(database_path('migrations/*' . self::MIGRATION_FILE));
        $this->assertNotEmpty($files, 'File migration tidak ditemukan.');

        return include $files[0];
    }

    private function createStore(): Store
    {
        return Store::create([
            'channel_id' => $this->channel->id,
            'code'       => 'S' . rand(10000, 99999),
            'name'       => 'Toko',
            'status'     => 'active',
            'is_active'  => true,
        ]);
    }

    private function insertSettlement(?int $storeId, string $channelOrderId): void
    {
        DB::table('marketplace_order_settlements')->insert([
            'store_id'         => $storeId,
            'channel_order_id' => $channelOrderId,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    private function dropCurrentUnique(): void
    {
        // Setelah migrasi awal test suite, constraint aktif adalah yang BARU (per-store).
        // Sengaja TIDAK memulihkan constraint lama di sini — dipakai oleh skenario
        // yang butuh tabel benar-benar tanpa unique constraint sama sekali (supaya
        // data "kotor" bisa di-insert untuk menguji preflight up() secara terisolasi).
        Schema::table('marketplace_order_settlements', function (Blueprint $table) {
            $table->dropUnique('mos_store_channel_order_unique');
        });
    }

    /**
     * Mensimulasikan kondisi SEBENARNYA sebelum migration ini pernah dijalankan:
     * constraint lama (unique global pada channel_order_id saja) aktif. Dipakai oleh
     * skenario "up() berhasil" karena up() perlu MENEMUKAN index lama itu (via
     * findUniqueIndexName) untuk di-drop lalu diganti index baru — kalau tabel sama
     * sekali tanpa unique constraint (seperti dropCurrentUnique() polos), up() akan
     * berhenti dengan RuntimeException "index tidak ditemukan", sesuai desainnya.
     */
    private function restoreOldUniqueConstraint(): void
    {
        Schema::table('marketplace_order_settlements', function (Blueprint $table) {
            $table->dropUnique('mos_store_channel_order_unique');
            $table->unique('channel_order_id');
        });
    }

    // ── up(): preflight menolak duplikat store_id+channel_order_id ────────────
    public function test_up_gagal_jika_ada_duplikat_store_dan_channel_order_id()
    {
        $this->dropCurrentUnique();

        $store = $this->createStore();
        $this->insertSettlement($store->id, 'ORDER-DUP');
        $this->insertSettlement($store->id, 'ORDER-DUP'); // duplikat sengaja, sama persis

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/duplikat/i');

        $this->migrationInstance()->up();
    }

    // ── up(): preflight menolak store_id invalid ────────────────────────────
    public function test_up_gagal_jika_ada_store_id_tidak_valid()
    {
        $this->dropCurrentUnique();

        // CATATAN PENTING: awalnya test ini mencoba menyuntikkan store_id=999999
        // (angka yang tidak ada di tabel stores) untuk menguji Preflight 3. Itu SALAH
        // secara teknis: kolom store_id di marketplace_order_settlements punya FK aktif
        // ke tabel stores (nullable, nullOnDelete) — jadi insert store_id yang benar-benar
        // tidak ada akan DITOLAK DATABASE lebih dulu, sebelum sempat menguji migration
        // sama sekali. Mematikan PRAGMA foreign_keys tidak bisa dipakai sebagai jalan
        // pintas di sini karena SQLite melarang toggle pragma itu selama ada transaksi
        // terbuka (RefreshDatabase membungkus tiap test dalam transaksi).
        //
        // Skenario "store_id tidak valid" yang BENAR-BENAR bisa terjadi tanpa melanggar
        // constraint apa pun adalah store_id NULL (kolom ini memang nullable) — misalnya
        // baris settlement lama yang belum sempat dikaitkan ke toko. Preflight 3 di
        // migration ini memang secara eksplisit menguji whereNull('store_id') sebagai
        // bagian dari kondisi tidak valid, jadi ini pengujian yang tepat dan realistis.
        $this->insertSettlement(null, 'ORDER-INVALID-STORE');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/store_id NULL atau tidak valid/i');

        $this->migrationInstance()->up();
    }

    // ── up(): data bersih -> berhasil, dan order sama di toko berbeda diizinkan ──
    public function test_up_berhasil_dan_order_sama_di_toko_berbeda_diizinkan()
    {
        // PENTING (bug di versi test sebelumnya): kedua insert "order sama, toko
        // beda" HARUS terjadi SETELAH up() dijalankan, bukan sebelum. Sebelum up(),
        // constraint LAMA (global unique channel_order_id) masih aktif — insert kedua
        // dengan channel_order_id yang sama akan ditolak database SEBELUM migration
        // sempat dijalankan sama sekali, membuat skenario ini mustahil diuji dengan
        // urutan lama. Constraint lama memang harus ada dulu (state bersih, tabel
        // kosong) supaya up() bisa MENEMUKAN index lama itu untuk di-drop.
        $this->restoreOldUniqueConstraint();

        $this->migrationInstance()->up(); // tidak boleh exception

        $storeA = $this->createStore();
        $storeB = $this->createStore();

        // Order_sn sama, toko berbeda -> HARUS diizinkan oleh constraint BARU (per-store).
        $this->insertSettlement($storeA->id, 'ORDER-SHARED');
        $this->insertSettlement($storeB->id, 'ORDER-SHARED');

        // Buktikan constraint baru benar-benar unique PER STORE: insert kombinasi
        // yang sama persis (storeA + ORDER-SHARED) harus ditolak database.
        $this->expectException(QueryException::class);
        $this->insertSettlement($storeA->id, 'ORDER-SHARED');
    }

    // ── down(): ditolak kalau ada channel_order_id lintas toko ─────────────────
    public function test_down_gagal_jika_ada_channel_order_id_lintas_toko()
    {
        // State awal (dari migrasi test suite) sudah memakai constraint baru per-store.
        $storeA = $this->createStore();
        $storeB = $this->createStore();
        $this->insertSettlement($storeA->id, 'ORDER-CROSS');
        $this->insertSettlement($storeB->id, 'ORDER-CROSS'); // sah di constraint baru

        try {
            $this->migrationInstance()->down();
            $this->fail('Diharapkan RuntimeException karena ada channel_order_id lintas toko.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('TIDAK BISA dipulihkan', $e->getMessage());
        }

        // Pastikan ATOMIC: constraint baru TIDAK ikut terhapus meski down() gagal.
        $this->assertTrue($this->uniqueIndexExists('mos_store_channel_order_unique'));

        // Constraint baru masih berfungsi normal setelah percobaan rollback yang gagal.
        $this->expectException(QueryException::class);
        $this->insertSettlement($storeA->id, 'ORDER-CROSS');
    }

    // ── down(): berhasil memulihkan unique global kalau data aman ──────────────
    public function test_down_berhasil_memulihkan_unique_global_ketika_data_aman()
    {
        $storeA = $this->createStore();
        $storeB = $this->createStore();
        $this->insertSettlement($storeA->id, 'ORDER-SAFE-A');
        $this->insertSettlement($storeB->id, 'ORDER-SAFE-B'); // tidak ada channel_order_id yang sama lintas toko

        $this->migrationInstance()->down(); // tidak boleh exception

        $this->assertFalse($this->uniqueIndexExists('mos_store_channel_order_unique'));

        // Constraint lama (global) aktif lagi: order_sn sama di toko lain harus ditolak.
        $this->expectException(QueryException::class);
        $this->insertSettlement($storeB->id, 'ORDER-SAFE-A');
    }

    private function uniqueIndexExists(string $indexName): bool
    {
        $rows = DB::select("PRAGMA index_list('marketplace_order_settlements')");
        foreach ($rows as $row) {
            if (($row->name ?? null) === $indexName && (int) $row->unique === 1) {
                return true;
            }
        }
        return false;
    }
}
