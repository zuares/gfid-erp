<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Override manual mapping produk iklan → item internal.
 * Resolusi default sudah otomatis (channel_item_id → sku_mappings /
 * marketplace_order_items). Tabel ini hanya untuk override / kasus yang
 * tak ketangkap otomatis ("beberapa judul produk, item internal sama").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_ad_item_maps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('channel_code', 20)->default('shopee');
            $table->unsignedBigInteger('channel_item_id')->nullable();       // kunci utama product ads
            $table->string('channel_campaign_id', 80)->nullable();           // fallback kalau item_id tak ada
            $table->foreignId('internal_item_id')->constrained('items')->cascadeOnDelete();
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['channel_code', 'channel_item_id'], 'uniq_mp_ad_item_map_item');
            $table->unique(['channel_code', 'channel_campaign_id'], 'uniq_mp_ad_item_map_campaign');
            $table->index('internal_item_id', 'idx_mp_ad_item_map_internal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_ad_item_maps');
    }
};
