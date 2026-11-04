<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_shopee_wallet_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('external_transaction_id', 120);
            $table->string('transaction_type', 80)->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->string('money_flow', 20)->nullable();
            $table->string('wallet_type', 60)->nullable();
            $table->decimal('current_balance', 18, 2)->nullable();
            $table->string('order_sn', 120)->nullable();
            $table->string('buyer_name', 190)->nullable();
            $table->string('status', 40)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('transaction_created_at')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['store_id', 'external_transaction_id'],
                'shopee_wallet_store_transaction_unique'
            );
            $table->index(
                ['store_id', 'transaction_created_at'],
                'shopee_wallet_store_created_index'
            );
            $table->index(
                ['store_id', 'transaction_type'],
                'shopee_wallet_store_type_index'
            );
            $table->index(
                ['store_id', 'money_flow'],
                'shopee_wallet_store_flow_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_shopee_wallet_transactions');
    }
};
