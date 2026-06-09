<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            // marketplace_sku — cached dari model_sku ?? item_sku ?? external_sku
            if (! Schema::hasColumn('marketplace_order_items', 'marketplace_sku')) {
                $table->string('marketplace_sku', 100)->nullable()
                    ->comment('Cached: model_sku ?? item_sku ?? external_sku — untuk query & display')
                    ->after('model_sku');
            }
            // data_status — agregat akhir: valid jika mapping + hpp lengkap, incomplete jika ada masalah
            if (! Schema::hasColumn('marketplace_order_items', 'data_status')) {
                $table->string('data_status', 20)->nullable()
                    ->comment('valid | incomplete')
                    ->after('issue_reason');
            }
        });

        // Index
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            try { $table->index('marketplace_sku', 'idx_moi_mp_sku'); } catch (\Throwable) {}
            try { $table->index('data_status',     'idx_moi_data_status'); } catch (\Throwable) {}
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            try { $table->dropIndex('idx_moi_mp_sku'); }      catch (\Throwable) {}
            try { $table->dropIndex('idx_moi_data_status'); } catch (\Throwable) {}
            foreach (['marketplace_sku', 'data_status'] as $col) {
                if (Schema::hasColumn('marketplace_order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
