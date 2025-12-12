<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom penerimaan RTS (layer ke-2).
     *
     * Catatan safety:
     * - Semua kolom baru dibuat nullable → aman untuk data lama.
     * - Tidak menghapus / mengubah kolom yang sudah ada.
     * - Tidak menambah foreign key constraint yang bisa mengunci tabel.
     */
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            // user RTS yang melakukan penerimaan fisik
            $table->unsignedBigInteger('received_by_user_id')
                ->nullable()
                ->after('requested_by_user_id');

            // kapan penerimaan fisik RTS dilakukan
            $table->timestamp('received_at')
                ->nullable()
                ->after('received_by_user_id');

            // ⚠️ Kalau kamu belum punya kolom requested_by_user_id,
            // ganti "after('requested_by_user_id')" dengan after kolom lain
            // atau hapus saja ->after(...) untuk kompatibel lintas DB.
        });

        Schema::table('stock_request_lines', function (Blueprint $table) {
            // qty fisik yang benar-benar diterima di WH-RTS
            $table->decimal('qty_received_rts', 12, 3)
                ->nullable()
                ->after('qty_issued');

            // ⚠️ Kalau kolom qty_issued belum ada / beda nama,
            // ganti after('qty_issued') sesuai strukturmu
            // atau hapus ->after(...) supaya aman di semua DB.
        });
    }

    /**
     * Rollback perubahan.
     * Hanya menghapus kolom baru; data lama tetap utuh.
     */
    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            if (Schema::hasColumn('stock_requests', 'received_by_user_id')) {
                $table->dropColumn('received_by_user_id');
            }

            if (Schema::hasColumn('stock_requests', 'received_at')) {
                $table->dropColumn('received_at');
            }
        });

        Schema::table('stock_request_lines', function (Blueprint $table) {
            if (Schema::hasColumn('stock_request_lines', 'qty_received_rts')) {
                $table->dropColumn('qty_received_rts');
            }
        });
    }
};
