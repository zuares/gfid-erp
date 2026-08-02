<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketplace_orders') && ! Schema::hasColumn('marketplace_orders', 'settlement_sync_last_attempt_at')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                $table->timestamp('settlement_sync_last_attempt_at')
                    ->nullable()
                    ->after('settlement_sync_failed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketplace_orders') && Schema::hasColumn('marketplace_orders', 'settlement_sync_last_attempt_at')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                $table->dropColumn('settlement_sync_last_attempt_at');
            });
        }
    }
};
