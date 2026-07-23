<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 — simpan setting campaign dari get_product_level_campaign_setting_info
 * (info_type_list=1,2,3,4). Semua kolom NULLABLE & aditif → aman untuk DB berisi
 * data. Tidak menghapus/mengubah kolom existing.
 *
 * Sumber field (terverifikasi dari API nyata campaign 477707399):
 *   common_info.ad_type, .bidding_method, .campaign_status, .campaign_placement,
 *   .campaign_budget, .campaign_duration.{start_time,end_time}, .item_id_list[0]
 *   auto_bidding_info.roas_target
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_ad_campaigns', function (Blueprint $table) {
            $table->string('ad_type', 40)->nullable()->after('channel_item_id');            // manual / auto
            $table->string('bidding_method', 40)->nullable()->after('ad_type');             // auto (GMV Max) / manual
            $table->decimal('target_roas', 10, 4)->nullable()->after('bidding_method');     // auto_bidding_info.roas_target (0 = tak diset)
            $table->decimal('campaign_budget', 15, 2)->nullable()->after('target_roas');     // asumsi satuan IDR mayor (25000 = Rp25.000)
            $table->string('campaign_status', 40)->nullable()->after('campaign_budget');     // closed/ongoing/paused/scheduled (nilai asli Shopee)
            $table->string('campaign_placement', 40)->nullable()->after('campaign_status');  // all / search / discovery
            $table->timestamp('started_at')->nullable()->after('campaign_placement');        // campaign_duration.start_time
            $table->timestamp('ended_at')->nullable()->after('started_at');                  // end_time>0; end_time=0 → null
            $table->json('raw_setting_payload')->nullable()->after('raw_json');              // node setting (tanpa credential)
            $table->timestamp('setting_synced_at')->nullable()->after('raw_setting_payload'); // hanya diisi bila setting berhasil dibaca
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_ad_campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'ad_type', 'bidding_method', 'target_roas', 'campaign_budget',
                'campaign_status', 'campaign_placement', 'started_at', 'ended_at',
                'raw_setting_payload', 'setting_synced_at',
            ]);
        });
    }
};
