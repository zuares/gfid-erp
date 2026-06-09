<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_order_settlements', function (Blueprint $table) {
            // Biaya iklan (Shopee Ads / TikTok Ads) — input manual atau di-import
            $table->decimal('ad_cost', 15, 2)->default(0)->after('escrow_tax')
                ->comment('Biaya iklan yang dialokasikan ke order ini');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_order_settlements', function (Blueprint $table) {
            $table->dropColumn('ad_cost');
        });
    }
};
