<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_ads_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_ads_settings', 'target_profit_pct')) {
                $table->decimal('target_profit_pct', 5, 2)->nullable()->after('target_roas');
            }
            if (! Schema::hasColumn('marketplace_ads_settings', 'target_roas_mode')) {
                $table->string('target_roas_mode', 10)->default('auto')->after('target_profit_pct');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_ads_settings', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_ads_settings', 'target_roas_mode')) {
                $table->dropColumn('target_roas_mode');
            }
            if (Schema::hasColumn('marketplace_ads_settings', 'target_profit_pct')) {
                $table->dropColumn('target_profit_pct');
            }
        });
    }
};
