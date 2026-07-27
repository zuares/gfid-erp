<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SATSET perf ads-dashboard: dua kolom yang di-query per kampanye di
 * AdsDashboardController ternyata belum ber-index:
 *
 * - marketplace_order_items.external_item_id — dipakai computeNetRevenueRatio()
 *   dan query kategori per kampanye (catByChan). Tanpa index = full table scan
 *   per item setiap cache dingin (tiap 30 menit).
 * - marketplace_order_settlements.order_id — kolom FK; MySQL membuat index
 *   otomatis untuk FK, tapi SQLite TIDAK. Ditambahkan eksplisit agar aman
 *   di kedua driver (try/catch menelan error "sudah ada" di MySQL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            try { $table->index('external_item_id', 'idx_moi_external_item_id'); } catch (\Throwable) {}
        });

        Schema::table('marketplace_order_settlements', function (Blueprint $table) {
            try { $table->index('order_id', 'idx_mos_order_id'); } catch (\Throwable) {}
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            try { $table->dropIndex('idx_moi_external_item_id'); } catch (\Throwable) {}
        });

        Schema::table('marketplace_order_settlements', function (Blueprint $table) {
            try { $table->dropIndex('idx_mos_order_id'); } catch (\Throwable) {}
        });
    }
};
