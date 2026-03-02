<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_ads_imports', function (Blueprint $table) {
            $table->id();

            $table->string('channel', 20); // shopee/tiktok/meta/google
            $table->string('report_type', 80); // contoh: product_ads_search_term_ranking

            // Shopee: "ID Toko" dari metadata report
            $table->string('shop_platform_id', 40)->nullable();
            $table->string('shop_name', 120)->nullable();

            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->dateTime('report_generated_at')->nullable(); // "Waktu Laporan Dibuat"

            $table->string('file_name')->nullable();
            $table->string('file_hash', 64)->nullable(); // sha1=40, sha256=64 (kita sediakan 64)

            $table->string('status', 20)->default('committed'); // draft/previewed/committed/failed

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Anti duplikat file sama persis
            $table->unique(['file_hash'], 'uniq_mp_ads_imports_file_hash');

            // Anti duplikat dataset periode yang sama (1 toko, 1 report_type, 1 periode)
            $table->unique(
                ['channel', 'shop_platform_id', 'report_type', 'period_start', 'period_end'],
                'uniq_mp_ads_imports_dataset'
            );

            $table->index(['channel', 'report_type', 'period_start', 'period_end'], 'idx_mp_ads_imports_period');
            $table->index(['shop_platform_id'], 'idx_mp_ads_imports_shop_platform_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_ads_imports');
    }
};
