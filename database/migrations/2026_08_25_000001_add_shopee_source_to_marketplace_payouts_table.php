<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_payouts', function (Blueprint $table) {
            $table->foreignId('store_id')
                ->nullable()
                ->after('marketplace_name')
                ->constrained('stores')
                ->nullOnDelete();
            $table->string('source', 30)->default('manual')->after('store_id');
            $table->string('external_transaction_id', 100)->nullable()->after('reference');
            $table->string('transaction_type', 50)->nullable()->after('external_transaction_id');
            $table->timestamp('transaction_created_at')->nullable()->after('transaction_type');
            $table->json('source_payload')->nullable()->after('transaction_created_at');

            $table->unique(
                ['store_id', 'external_transaction_id'],
                'marketplace_payouts_store_external_transaction_unique'
            );
            $table->index(['store_id', 'transaction_created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_payouts', function (Blueprint $table) {
            $table->dropUnique('marketplace_payouts_store_external_transaction_unique');
            $table->dropForeign(['store_id']);
            $table->dropIndex(['store_id', 'transaction_created_at']);
            $table->dropColumn([
                'store_id',
                'source',
                'external_transaction_id',
                'transaction_type',
                'transaction_created_at',
                'source_payload',
            ]);
        });
    }
};
