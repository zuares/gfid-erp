<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jadwal boost per produk pada jam tetap (harian berulang).
        // Contoh: Produk A dinaikkan tiap hari jam 08:00 & 20:00.
        Schema::create('marketplace_boost_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('marketplace_product_id')->constrained('marketplace_products')->cascadeOnDelete();
            $table->time('boost_time');                       // HH:MM:SS, dijalankan tiap hari
            $table->unsignedTinyInteger('priority')->default(0); // makin kecil makin diutamakan saat slot penuh
            $table->boolean('is_active')->default(true);
            $table->date('last_fired_on')->nullable();         // tanggal terakhir slot ini benar-benar jalan (anti dobel + catch-up)
            $table->timestamp('last_boosted_at')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_product_id', 'boost_time'], 'uq_boost_prod_time');
            $table->index(['is_active', 'boost_time']);
            $table->index(['store_id', 'is_active']);
        });

        // Antrian auto-rotasi: kumpulan produk yang digilir mengisi slot boost kosong tiap 4 jam.
        Schema::create('marketplace_boost_pool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('marketplace_product_id')->constrained('marketplace_products')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('last_boosted_at')->nullable();  // giliran = yang paling lama belum di-boost
            $table->timestamps();

            $table->unique(['store_id', 'marketplace_product_id'], 'uq_pool_store_prod');
            $table->index(['store_id', 'is_active', 'last_boosted_at'], 'idx_pool_rotation');
        });

        // Riwayat eksekusi boost — audit, hitung cooldown 4 jam, & tampilkan sisa waktu di UI.
        Schema::create('marketplace_boost_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('marketplace_product_id')->nullable()
                ->constrained('marketplace_products')->nullOnDelete();
            $table->string('item_id')->nullable();             // item_id Shopee (jaga2 kalau produk lokal terhapus)
            $table->string('source', 20);                      // schedule | pool | manual
            $table->boolean('success')->default(true);
            $table->text('message')->nullable();
            $table->timestamp('boosted_at');
            $table->timestamp('expires_at')->nullable();       // boosted_at + 4 jam (durasi boost Shopee)
            $table->timestamps();

            $table->index(['store_id', 'boosted_at']);
            $table->index(['marketplace_product_id', 'boosted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_boost_logs');
        Schema::dropIfExists('marketplace_boost_pool');
        Schema::dropIfExists('marketplace_boost_schedules');
    }
};
