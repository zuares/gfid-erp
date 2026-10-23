<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_tiktok_financial_transactions', function (Blueprint $table): void {
            $table->string('source', 40)->default('withdrawals')->after('external_transaction_id');
            $table->string('statement_id', 120)->nullable()->after('transaction_created_at');
            $table->string('order_id', 120)->nullable()->after('statement_id');
            $table->string('adjustment_id', 120)->nullable()->after('order_id');
            $table->decimal('settlement_amount', 18, 2)->nullable()->after('adjustment_id');
            $table->decimal('revenue_amount', 18, 2)->nullable()->after('settlement_amount');
            $table->decimal('shipping_cost_amount', 18, 2)->nullable()->after('revenue_amount');
            $table->decimal('fee_tax_amount', 18, 2)->nullable()->after('shipping_cost_amount');
            $table->decimal('adjustment_amount', 18, 2)->nullable()->after('fee_tax_amount');
            $table->decimal('reserve_amount', 18, 2)->nullable()->after('adjustment_amount');
        });

        Schema::table('marketplace_tiktok_financial_transactions', function (Blueprint $table): void {
            $table->dropUnique('tiktok_financial_store_transaction_unique');
            $table->unique(
                ['store_id', 'source', 'external_transaction_id'],
                'tiktok_financial_store_source_transaction_unique'
            );
            $table->index(
                ['store_id', 'source', 'statement_id'],
                'tiktok_financial_store_source_statement_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_tiktok_financial_transactions', function (Blueprint $table): void {
            $table->dropIndex('tiktok_financial_store_source_statement_index');
            $table->dropUnique('tiktok_financial_store_source_transaction_unique');
            $table->unique(
                ['store_id', 'external_transaction_id'],
                'tiktok_financial_store_transaction_unique'
            );
            $table->dropColumn([
                'source',
                'statement_id',
                'order_id',
                'adjustment_id',
                'settlement_amount',
                'revenue_amount',
                'shipping_cost_amount',
                'fee_tax_amount',
                'adjustment_amount',
                'reserve_amount',
            ]);
        });
    }
};
