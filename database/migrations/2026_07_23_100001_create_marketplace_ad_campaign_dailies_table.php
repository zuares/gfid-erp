<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fakta harian per campaign — grain terkecil dari Shopee Ads API.
 * Semua KPI (rentang apapun, per item, per grup) diagregasi dari sini.
 * Rasio (ROAS/CTR/ACOS/CVR) TIDAK disimpan di sini — dihitung saat agregasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_ad_campaign_dailies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('channel_campaign_id', 80)->index();
            $table->date('date');
            $table->string('ad_type', 40)->nullable(); // product / shop / dll

            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->decimal('expense', 15, 2)->default(0);      // spend hari itu
            $table->integer('broad_order')->default(0);
            $table->decimal('broad_gmv', 15, 2)->default(0);
            $table->integer('direct_order')->default(0);
            $table->decimal('direct_gmv', 15, 2)->default(0);
            $table->decimal('cpc', 10, 4)->nullable();          // raw dari API (opsional)

            $table->json('raw_json')->nullable();
            $table->timestamps();

            // Idempoten: 1 campaign, 1 tanggal → aman re-sync
            $table->unique(['store_id', 'channel_campaign_id', 'date'], 'uniq_mp_ad_camp_daily');
            $table->index(['store_id', 'date'], 'idx_mp_ad_camp_daily_store_date');
            $table->index(['channel_campaign_id', 'date'], 'idx_mp_ad_camp_daily_camp_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_ad_campaign_dailies');
    }
};
