<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_financial_transactions', function (Blueprint $table): void {
            $table->index(['store_id', 'created_at'], 'mp_financial_transactions_store_created_idx');
            $table->index(['store_id', 'released_at'], 'mp_financial_transactions_store_released_idx');
            $table->index('order_sn', 'mp_financial_transactions_order_sn_idx');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_financial_transactions', function (Blueprint $table): void {
            $table->dropIndex('mp_financial_transactions_store_created_idx');
            $table->dropIndex('mp_financial_transactions_store_released_idx');
            $table->dropIndex('mp_financial_transactions_order_sn_idx');
        });
    }
};
