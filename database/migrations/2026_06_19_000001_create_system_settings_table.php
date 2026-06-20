<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * System Settings — tabel key-value untuk konfigurasi global.
 *
 * Keys yang dipakai saat ini:
 *  - system_cutoff_date  : YYYY-MM-DD — tanggal cut-off produksi baru
 *  - system_cutoff_notes : catatan teks bebas tentang alasan cut-off
 *
 * ROLLBACK: php artisan migrate:rollback --step=1
 * Tidak ada data lama yang dihapus. Aman untuk di-rollback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
