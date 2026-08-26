<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_ad_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('external_transaction_id', 100);
            $table->string('transaction_type', 50);
            // Signed amount normalized to the seller wallet perspective:
            // charge = negative, refund = positive.
            $table->decimal('amount', 15, 2);
            $table->string('money_flow', 20)->nullable();
            $table->string('wallet_type', 50)->nullable();
            $table->string('order_sn', 100)->nullable();
            $table->string('status', 30)->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('transaction_created_at');
            $table->json('source_payload')->nullable();
            $table->timestamps();

            $table->unique(
                ['store_id', 'external_transaction_id'],
                'marketplace_ad_wallet_store_transaction_unique'
            );
            $table->index(
                ['store_id', 'transaction_created_at'],
                'marketplace_ad_wallet_store_created_index'
            );
            $table->index(
                ['store_id', 'transaction_type'],
                'marketplace_ad_wallet_store_type_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_ad_wallet_transactions');
    }
};
