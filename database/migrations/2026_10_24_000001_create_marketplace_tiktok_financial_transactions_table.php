<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_tiktok_financial_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('external_transaction_id', 120);
            $table->string('transaction_type', 30);
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->string('money_flow', 20)->nullable();
            $table->string('status', 40)->nullable();
            $table->timestamp('transaction_created_at')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['store_id', 'external_transaction_id'],
                'tiktok_financial_store_transaction_unique'
            );
            $table->index(
                ['store_id', 'transaction_created_at'],
                'tiktok_financial_store_created_index'
            );
            $table->index(
                ['store_id', 'transaction_type'],
                'tiktok_financial_store_type_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_tiktok_financial_transactions');
    }
};
