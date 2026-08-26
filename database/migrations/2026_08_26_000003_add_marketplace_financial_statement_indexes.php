<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketplace_orders')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                try {
                    $table->index(['ordered_at', 'order_status'], 'idx_mo_financial_statement_scope');
                } catch (\Throwable) {
                    // Index may already exist on installations with a manual fix.
                }
            });
        }

        if (Schema::hasTable('marketplace_order_settlements')) {
            Schema::table('marketplace_order_settlements', function (Blueprint $table) {
                try {
                    $table->index(['data_status', 'order_id'], 'idx_mos_financial_statement_scope');
                } catch (\Throwable) {
                    // Index may already exist on installations with a manual fix.
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketplace_order_settlements')) {
            Schema::table('marketplace_order_settlements', function (Blueprint $table) {
                try { $table->dropIndex('idx_mos_financial_statement_scope'); } catch (\Throwable) {}
            });
        }

        if (Schema::hasTable('marketplace_orders')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                try { $table->dropIndex('idx_mo_financial_statement_scope'); } catch (\Throwable) {}
            });
        }
    }
};
