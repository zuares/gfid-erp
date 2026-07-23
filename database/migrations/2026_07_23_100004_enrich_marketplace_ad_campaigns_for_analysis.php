<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah makna marketplace_ad_campaigns: dari "agregat 1 rentang" → master
 * "1 baris per campaign" + mapping item internal + grup. Angka riil harian
 * pindah ke marketplace_ad_campaign_dailies.
 *
 * Aman: tabel ini kosong (0 baris) saat migrasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Tambah kolom mapping & grup
        Schema::table('marketplace_ad_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('channel_item_id')->nullable()->after('channel_campaign_id');
            $table->foreignId('internal_item_id')->nullable()->after('channel_item_id')
                ->constrained('items')->nullOnDelete();
            $table->foreignId('ad_group_id')->nullable()->after('internal_item_id')
                ->constrained('marketplace_ad_groups')->nullOnDelete();
            $table->string('mapping_status', 20)->default('unmapped')->after('ad_group_id'); // unmapped/auto/manual
            $table->string('mapping_source', 20)->nullable()->after('mapping_status');        // sku_mappings/order_items/manual
            $table->date('last_synced_range_from')->nullable()->after('mapping_source');
            $table->date('last_synced_range_to')->nullable()->after('last_synced_range_from');
        });

        // 2) Buang unique lama yang berbasis rentang tanggal (sebelum drop kolomnya)
        Schema::table('marketplace_ad_campaigns', function (Blueprint $table) {
            $table->dropUnique('uniq_mp_ad_campaigns_period');
            $table->dropIndex('idx_mp_ad_campaigns_store_period');
        });

        // 3) Buang kolom rentang lama (angka riil sekarang di tabel harian)
        Schema::table('marketplace_ad_campaigns', function (Blueprint $table) {
            $table->dropColumn(['report_date_from', 'report_date_to']);
        });

        // 4) Unique baru: 1 campaign per toko
        Schema::table('marketplace_ad_campaigns', function (Blueprint $table) {
            $table->unique(['store_id', 'channel_campaign_id'], 'uniq_mp_ad_campaigns_campaign');
            $table->index('internal_item_id', 'idx_mp_ad_campaigns_internal_item');
            $table->index('ad_group_id', 'idx_mp_ad_campaigns_group');
            $table->index('channel_item_id', 'idx_mp_ad_campaigns_channel_item');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_ad_campaigns', function (Blueprint $table) {
            $table->dropUnique('uniq_mp_ad_campaigns_campaign');
            $table->dropIndex('idx_mp_ad_campaigns_internal_item');
            $table->dropIndex('idx_mp_ad_campaigns_group');
            $table->dropIndex('idx_mp_ad_campaigns_channel_item');

            $table->dropConstrainedForeignId('internal_item_id');
            $table->dropConstrainedForeignId('ad_group_id');
            $table->dropColumn([
                'channel_item_id', 'mapping_status', 'mapping_source',
                'last_synced_range_from', 'last_synced_range_to',
            ]);

            $table->date('report_date_from')->nullable();
            $table->date('report_date_to')->nullable();
            $table->unique(
                ['store_id', 'channel_campaign_id', 'report_date_from', 'report_date_to'],
                'uniq_mp_ad_campaigns_period'
            );
            $table->index(['store_id', 'report_date_from', 'report_date_to'], 'idx_mp_ad_campaigns_store_period');
        });
    }
};
