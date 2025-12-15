<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ===============================
        // 1) Tambah kolom dispatch/receive di lines
        // ===============================
        Schema::table('stock_request_lines', function (Blueprint $table) {
            // Ramah produksi: default 0 supaya tidak null di perhitungan
            if (!Schema::hasColumn('stock_request_lines', 'qty_dispatched')) {
                $table->decimal('qty_dispatched', 12, 3)->default(0)->after('qty_request');
            }

            if (!Schema::hasColumn('stock_request_lines', 'qty_received')) {
                $table->decimal('qty_received', 12, 3)->default(0)->after('qty_dispatched');
            }

            // OPTIONAL (recommended untuk audit, tapi aman kalau kamu mau skip)
            // if (!Schema::hasColumn('stock_request_lines', 'dispatched_at')) {
            //     $table->timestamp('dispatched_at')->nullable()->after('qty_dispatched');
            // }
            // if (!Schema::hasColumn('stock_request_lines', 'received_at')) {
            //     $table->timestamp('received_at')->nullable()->after('qty_received');
            // }
        });

        // ===============================
        // 2) Backfill: qty_dispatched = qty_issued (legacy)
        // ===============================
        // Aman: hanya isi kalau qty_dispatched masih 0 dan qty_issued ada nilainya
        if (Schema::hasColumn('stock_request_lines', 'qty_issued')) {
            DB::table('stock_request_lines')
                ->where('qty_dispatched', 0)
                ->whereNotNull('qty_issued')
                ->where('qty_issued', '>', 0)
                ->update([
                    'qty_dispatched' => DB::raw('qty_issued'),
                ]);
        }

        // ===============================
        // 3) Pastikan gudang WH-TRANSIT ada
        // ===============================
        // Idempotent (ramah produksi): cek dulu sebelum insert
        $exists = DB::table('warehouses')->where('code', 'WH-TRANSIT')->exists();

        if (!$exists) {
            $now = now();
            DB::table('warehouses')->insert([
                'code' => 'WH-TRANSIT',
                'name' => 'Transit PRD → RTS',
                // Kalau tabel kamu punya kolom wajib lain, tambah di sini:
                // 'type' => 'transit',
                // 'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // NOTE: untuk produksi biasanya kita jarang rollback migration data.
        // Tapi tetap disediakan agar konsisten.

        Schema::table('stock_request_lines', function (Blueprint $table) {
            if (Schema::hasColumn('stock_request_lines', 'qty_received')) {
                $table->dropColumn('qty_received');
            }
            if (Schema::hasColumn('stock_request_lines', 'qty_dispatched')) {
                $table->dropColumn('qty_dispatched');
            }

            // OPTIONAL:
            // if (Schema::hasColumn('stock_request_lines', 'received_at')) $table->dropColumn('received_at');
            // if (Schema::hasColumn('stock_request_lines', 'dispatched_at')) $table->dropColumn('dispatched_at');
        });

        // Warehouse WH-TRANSIT sengaja tidak dihapus pada down (lebih aman di produksi).
        // Kalau kamu ingin, boleh hapus manual atau aktifkan ini:
        // DB::table('warehouses')->where('code', 'WH-TRANSIT')->delete();
    }
};
