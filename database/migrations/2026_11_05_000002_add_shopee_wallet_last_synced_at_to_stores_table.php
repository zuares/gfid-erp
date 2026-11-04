<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'shopee_wallet_last_synced_at')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->timestamp('shopee_wallet_last_synced_at')
                    ->nullable()
                    ->after('tiktok_financial_last_synced_at');
            });
        }

        if (! Schema::hasTable('marketplace_shopee_wallet_transactions')) {
            return;
        }

        DB::table('marketplace_shopee_wallet_transactions')
            ->select('store_id')
            ->whereNotNull('synced_at')
            ->distinct()
            ->pluck('store_id')
            ->each(function (int $storeId): void {
                $lastSyncedAt = DB::table('marketplace_shopee_wallet_transactions')
                    ->where('store_id', $storeId)
                    ->max('synced_at');

                if ($lastSyncedAt !== null) {
                    DB::table('stores')
                        ->where('id', $storeId)
                        ->update(['shopee_wallet_last_synced_at' => $lastSyncedAt]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'shopee_wallet_last_synced_at')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->dropColumn('shopee_wallet_last_synced_at');
            });
        }
    }
};
