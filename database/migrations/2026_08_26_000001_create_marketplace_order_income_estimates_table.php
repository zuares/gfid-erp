<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_order_income_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('marketplace_order_id')->constrained('marketplace_orders')->cascadeOnDelete();
            $table->string('channel_order_id', 100);
            $table->unsignedTinyInteger('income_status')->default(2);
            $table->decimal('estimated_escrow_amount', 18, 2)->nullable();
            $table->dateTime('estimated_payout_at')->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->string('status_description', 255)->nullable();
            $table->string('currency', 10)->nullable();
            $table->dateTime('source_created_at')->nullable();
            $table->dateTime('synced_at');
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->unique('marketplace_order_id', 'mp_income_est_order_unique');
            $table->unique(['store_id', 'channel_order_id'], 'mp_income_est_store_order_unique');
            $table->index(['income_status', 'estimated_payout_at'], 'mp_income_est_status_payout_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_income_estimates');
    }
};
