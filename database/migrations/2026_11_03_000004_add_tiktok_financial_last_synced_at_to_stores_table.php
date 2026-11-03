<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'tiktok_financial_last_synced_at')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->timestamp('tiktok_financial_last_synced_at')
                    ->nullable()
                    ->after('last_synced_at');
            });
        }

        if (! Schema::hasTable('marketplace_tiktok_financial_transactions')) {
            return;
        }

        DB::table('marketplace_tiktok_financial_transactions')
            ->select('store_id')
            ->whereNotNull('synced_at')
            ->distinct()
            ->pluck('store_id')
            ->each(function (int $storeId): void {
                $lastSyncedAt = DB::table('marketplace_tiktok_financial_transactions')
                    ->where('store_id', $storeId)
                    ->max('synced_at');

                if ($lastSyncedAt !== null) {
                    DB::table('stores')
                        ->where('id', $storeId)
                        ->update(['tiktok_financial_last_synced_at' => $lastSyncedAt]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'tiktok_financial_last_synced_at')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->dropColumn('tiktok_financial_last_synced_at');
            });
        }
    }
};
