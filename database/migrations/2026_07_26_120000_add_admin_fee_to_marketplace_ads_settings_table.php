<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_ads_settings', function (Blueprint $table) {
            // auto  = rasio cair dihitung dari data settlement per item
            // manual = user set sendiri persen potongan (admin_fee_pct)
            $table->string('admin_fee_mode', 10)->default('auto')->after('target_roas');
            $table->decimal('admin_fee_pct', 5, 2)->nullable()->after('admin_fee_mode');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_ads_settings', function (Blueprint $table) {
            $table->dropColumn(['admin_fee_mode', 'admin_fee_pct']);
        });
    }
};
