<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah unique constraint marketplace_order_settlements dari GLOBAL (channel_order_id
 * saja) menjadi PER-STORE (store_id + channel_order_id) — supaya order_sn dari toko
 * berbeda tidak berpotensi bentrok.
 *
 * PENTING — TIDAK BOLEH DIJALANKAN begitu saja ke database yang belum diverifikasi:
 * migration ini melakukan preflight validation di up() dan down(), dan akan BERHENTI
 * dengan RuntimeException (bukan melanjutkan setengah jalan atau menghapus data) kalau
 * kondisi data tidak aman. Lihat AUDIT_FASE1_RANCANGAN_FINAL.md Bagian 11 & Koreksi 8-9
 * untuk latar belakang keputusan.
 *
 * Migration ini TIDAK PERNAH menghapus data secara otomatis.
 */
return new class extends Migration
{
    private string $table = 'marketplace_order_settlements';
    private string $newIndexName = 'mos_store_channel_order_unique';

    public function up(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        // ── PREFLIGHT 1: duplicate store_id + channel_order_id ──────────────────
        $dupStoreOrder = DB::table($this->table)
            ->select('store_id', 'channel_order_id', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('store_id', 'channel_order_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($dupStoreOrder->isNotEmpty()) {
            throw new \RuntimeException(
                'Migration DIBATALKAN: ditemukan ' . $dupStoreOrder->count() .
                ' kombinasi store_id+channel_order_id duplikat di ' . $this->table . '. Contoh: ' .
                $dupStoreOrder->take(5)->map(fn ($r) => "store_id={$r->store_id},order={$r->channel_order_id} ({$r->jumlah}x)")->implode('; ') .
                '. Bersihkan data ini secara MANUAL dulu (migration ini tidak akan menghapus apa pun secara otomatis), lalu jalankan ulang migration.'
            );
        }

        // ── PREFLIGHT 2: channel_order_id sama tapi beda store_id ───────────────
        // Bukan blocker (ini justru kasus yang SAH untuk unique per-store baru),
        // tapi wajib dilaporkan supaya diverifikasi manual bukan kesalahan data entry.
        $crossStore = DB::table($this->table)
            ->select('channel_order_id', DB::raw('COUNT(DISTINCT store_id) as jumlah_toko'))
            ->groupBy('channel_order_id')
            ->havingRaw('COUNT(DISTINCT store_id) > 1')
            ->get();

        if ($crossStore->isNotEmpty()) {
            Log::warning(
                'Migration unique constraint settlement: ditemukan ' . $crossStore->count() .
                ' channel_order_id yang dipakai di lebih dari satu toko (SAH untuk unique per-store, tapi mohon diverifikasi manual).',
                ['samples' => $crossStore->take(10)->toArray()]
            );
        }

        // ── PREFLIGHT 3: settlement dengan store_id NULL/invalid ─────────────────
        $invalidStoreCount = DB::table($this->table)
            ->where(function ($q) {
                $q->whereNull('store_id')
                    ->orWhereNotIn('store_id', function ($sub) {
                        $sub->select('id')->from('stores');
                    });
            })
            ->count();

        if ($invalidStoreCount > 0) {
            throw new \RuntimeException(
                "Migration DIBATALKAN: {$invalidStoreCount} baris {$this->table} punya store_id NULL atau tidak valid " .
                '(tidak ada di tabel stores). Unique constraint baru mensyaratkan store_id valid. ' .
                'Perbaiki data ini secara MANUAL dulu, lalu jalankan ulang migration.'
            );
        }

        // ── Cari nama unique index LAMA secara aman (driver-aware, bukan tebakan) ──
        $driver = Schema::getConnection()->getDriverName();
        $oldIndexName = $this->findUniqueIndexName($driver, $this->table, 'channel_order_id');

        if ($oldIndexName === null) {
            throw new \RuntimeException(
                "Migration DIBATALKAN: tidak dapat menemukan nama unique index existing pada kolom 'channel_order_id' " .
                "di tabel {$this->table} (driver: {$driver}). Migration berhenti sebelum mengubah apa pun, supaya tidak " .
                'menebak nama index dan berisiko gagal setengah jalan. Verifikasi manual nama constraint di database ini ' .
                'dulu (SQLite: PRAGMA index_list; MySQL: information_schema.statistics), lalu sesuaikan migration ini.'
            );
        }

        // ── Semua preflight lolos → ubah constraint ──────────────────────────────
        Schema::table($this->table, function (Blueprint $table) use ($oldIndexName) {
            $table->dropUnique($oldIndexName);
            $table->unique(['store_id', 'channel_order_id'], $this->newIndexName);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        // ── PREFLIGHT ROLLBACK: cek dulu SEBELUM mengubah apa pun ───────────────
        // Urutan aman (Koreksi 8): periksa konflik dulu → kalau ada, batalkan TOTAL
        // (jangan drop unique baru dulu baru ketahuan gagal, karena itu bisa
        // meninggalkan tabel tanpa unique constraint sama sekali).
        $conflicts = DB::table($this->table)
            ->select('channel_order_id', DB::raw('COUNT(DISTINCT store_id) as jumlah_toko'))
            ->groupBy('channel_order_id')
            ->havingRaw('COUNT(DISTINCT store_id) > 1')
            ->get();

        if ($conflicts->isNotEmpty()) {
            throw new \RuntimeException(
                'Rollback DIBATALKAN: unique global channel_order_id TIDAK BISA dipulihkan karena ada ' .
                $conflicts->count() . ' channel_order_id yang dipakai di lebih dari satu toko (sah untuk unique ' .
                'per-store, tapi akan melanggar unique global lama). Tidak ada perubahan skema yang dilakukan — ' .
                'tabel tetap memakai unique per-store yang sekarang. Bersihkan/rencanakan ulang data dulu kalau ' .
                'rollback penuh benar-benar diperlukan.'
            );
        }

        // Tidak ada konflik → aman melakukan swap constraint secara utuh.
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropUnique($this->newIndexName);
            $table->unique('channel_order_id');
        });
    }

    /**
     * Cari nama unique index yang PERSIS terdiri dari satu kolom ($column) di tabel
     * $table, driver-aware. Tidak menaruh asumsi nama (mis. konvensi default Laravel)
     * — mengecek langsung ke metadata database. Return null kalau tidak ditemukan;
     * caller WAJIB berhenti dengan pesan jelas, bukan melanjutkan dengan tebakan.
     */
    private function findUniqueIndexName(string $driver, string $table, string $column): ?string
    {
        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $idx) {
                if ((int) ($idx->unique ?? 0) !== 1) {
                    continue;
                }
                $cols = DB::select("PRAGMA index_info('{$idx->name}')");
                $colNames = array_map(fn ($c) => $c->name, $cols);
                if ($colNames === [$column]) {
                    return $idx->name;
                }
            }
            return null;
        }

        if ($driver === 'mysql') {
            $rows = DB::select(
                'SELECT index_name AS index_name,
                        GROUP_CONCAT(column_name ORDER BY seq_in_index) AS cols,
                        MAX(non_unique) AS non_unique
                 FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = ?
                 GROUP BY index_name',
                [$table]
            );
            foreach ($rows as $row) {
                if ((int) $row->non_unique === 0 && $row->cols === $column) {
                    return $row->index_name;
                }
            }
            return null;
        }

        // Driver lain (pgsql, sqlsrv, dll) belum didukung eksplisit di migration ini —
        // sengaja return null supaya caller berhenti dengan pesan jelas, bukan menebak.
        return null;
    }
};
