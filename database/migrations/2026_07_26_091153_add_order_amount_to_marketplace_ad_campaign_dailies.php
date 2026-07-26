<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marketplace_ad_campaign_dailies', function (Blueprint $table) {
            $table->integer('broad_order_amount')->default(0)->after('broad_order');
            $table->integer('direct_order_amount')->default(0)->after('direct_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_ad_campaign_dailies', function (Blueprint $table) {
            $table->dropColumn(['broad_order_amount', 'direct_order_amount']);
        });
    }
};
