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
        if (Schema::hasTable('marketplace_orders') && !Schema::hasColumn('marketplace_orders', 'settlement_sync_error_code')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                $table->string('settlement_sync_error_code')->nullable()->after('raw_json');
                $table->timestamp('settlement_sync_failed_at')->nullable()->after('settlement_sync_error_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('marketplace_orders') && Schema::hasColumn('marketplace_orders', 'settlement_sync_error_code')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                $table->dropColumn(['settlement_sync_error_code', 'settlement_sync_failed_at']);
            });
        }
    }
};
