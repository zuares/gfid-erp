<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_financial_transactions', function (Blueprint $table): void {
            // The existing store-prefixed indexes are not useful when the
            // Finance screen is opened for all stores.
            $table->index(['created_at', 'id'], 'mp_financial_transactions_created_id_idx');
            $table->index(['released_at', 'id'], 'mp_financial_transactions_released_id_idx');
        });

        Schema::table('marketplace_finance_settlements', function (Blueprint $table): void {
            $table->index(['settlement_date', 'id'], 'mp_finance_settlements_date_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_finance_settlements', function (Blueprint $table): void {
            $table->dropIndex('mp_finance_settlements_date_id_idx');
        });

        Schema::table('marketplace_financial_transactions', function (Blueprint $table): void {
            $table->dropIndex('mp_financial_transactions_created_id_idx');
            $table->dropIndex('mp_financial_transactions_released_id_idx');
        });
    }
};
