<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_ads_rows', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('import_id');
            $table->integer('row_no')->nullable(); // "Urutan"

            // Identitas iklan / produk
            $table->string('ad_name')->nullable(); // "Nama Iklan"
            $table->string('ad_status', 40)->nullable(); // "Status"
            $table->string('product_code', 80)->nullable(); // "Kode Produk"
            $table->string('bidding_mode', 40)->nullable(); // "Mode Bidding"
            $table->string('placement', 120)->nullable(); // "Penempatan Iklan"

            // Keyword/placement detail
            $table->string('search_term', 255)->nullable(); // "Kata Pencarian/Penempatan"
            $table->string('match_type', 40)->nullable(); // "Tipe Pencocokan"

            // Tanggal
            $table->dateTime('start_at')->nullable(); // "Tanggal Mulai" (di file ada time)
            $table->dateTime('end_at')->nullable(); // null kalau "Tidak Terbatas"
            $table->string('end_at_raw', 60)->nullable(); // simpan raw: "Tidak Terbatas"

            // Metrics utama
            $table->integer('impressions')->nullable(); // "Dilihat"
            $table->integer('clicks')->nullable(); // "Jumlah Klik"

            // Persen kita simpan 0..1 (contoh 4.47% => 0.0447)
            $table->decimal('ctr', 12, 6)->nullable(); // "Persentase Klik"
            $table->integer('conversions')->nullable(); // "Konversi"
            $table->integer('conversions_direct')->nullable(); // "Konversi Langsung"
            $table->decimal('cvr', 12, 6)->nullable(); // "Tingkat konversi"
            $table->decimal('cvr_direct', 12, 6)->nullable(); // "Tingkat Konversi Langsung"

            $table->decimal('cpa', 18, 6)->nullable(); // "Biaya per Konversi"
            $table->decimal('cpa_direct', 18, 6)->nullable(); // "Biaya per Konversi Langsung"

            $table->integer('items_sold')->nullable(); // "Produk Terjual"
            $table->integer('items_sold_direct')->nullable(); // "Terjual Langsung"

            $table->decimal('gmv', 18, 2)->nullable(); // "Omzet Penjualan"
            $table->decimal('gmv_direct', 18, 2)->nullable(); // "Penjualan Langsung (GMV Langsung)"
            $table->decimal('spend', 18, 2)->nullable(); // "Biaya"

            $table->decimal('roas', 18, 6)->nullable(); // "Efektifitas Iklan"
            $table->decimal('roas_direct', 18, 6)->nullable(); // "Efektivitas Langsung"

            // ACOS simpan 0..1 (contoh 13.62% => 0.1362)
            $table->decimal('acos', 12, 6)->nullable(); // "ACOS"
            $table->decimal('acos_direct', 12, 6)->nullable(); // "ACOS Langsung"

            // Anti duplikat row dalam 1 import (fingerprint dari field identitas)
            $table->string('row_fingerprint', 64);

            // Simpan data original kalau ada kolom tambahan / beda versi report
            $table->text('raw_json')->nullable();

            $table->timestamps();

            $table->foreign('import_id')
                ->references('id')
                ->on('mp_ads_imports')
                ->onDelete('cascade');

            // Unique untuk mencegah row dobel di import yang sama
            $table->unique(['import_id', 'row_fingerprint'], 'uniq_mp_ads_rows_import_fingerprint');

            $table->index(['import_id'], 'idx_mp_ads_rows_import_id');
            $table->index(['import_id', 'product_code'], 'idx_mp_ads_rows_import_product');
            $table->index(['import_id', 'search_term'], 'idx_mp_ads_rows_import_search_term');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_ads_rows');
    }
};
