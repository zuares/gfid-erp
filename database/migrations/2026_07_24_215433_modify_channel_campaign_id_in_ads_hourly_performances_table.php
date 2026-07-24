<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update existing data to use '-' instead of null
        DB::table('marketplace_ads_hourly_performances')
            ->whereNull('channel_campaign_id')
            ->update(['channel_campaign_id' => '-']);

        Schema::table('marketplace_ads_hourly_performances', function (Blueprint $table) {
            $table->dropUnique('uniq_mp_ads_hourly');
        });
        
        Schema::table('marketplace_ads_hourly_performances', function (Blueprint $table) {
            $table->string('channel_campaign_id', 80)->default('-')->nullable(false)->change();
            
            $table->unique(['store_id', 'channel_campaign_id', 'performance_date', 'performance_hour'], 'uniq_mp_ads_hourly');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_ads_hourly_performances', function (Blueprint $table) {
            $table->dropUnique('uniq_mp_ads_hourly');
        });

        Schema::table('marketplace_ads_hourly_performances', function (Blueprint $table) {
            $table->string('channel_campaign_id', 80)->nullable()->change();
            $table->unique(['store_id', 'channel_campaign_id', 'performance_date', 'performance_hour'], 'uniq_mp_ads_hourly');
        });
        
        DB::table('marketplace_ads_hourly_performances')
            ->where('channel_campaign_id', '-')
            ->update(['channel_campaign_id' => null]);
    }
};
