<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_fulfillment_lines', function (Blueprint $table) {
            // Waktu item ini di-pick oleh picker (null = belum dipick)
            $table->timestamp('picked_at')->nullable()->after('notes');

            // Alasan masalah saat picking (null = tidak ada masalah)
            // Contoh: "Stok kosong", "Item tidak ditemukan di rak", dll
            $table->string('pick_problem')->nullable()->after('picked_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_fulfillment_lines', function (Blueprint $table) {
            $table->dropColumn(['picked_at', 'pick_problem']);
        });
    }
};
