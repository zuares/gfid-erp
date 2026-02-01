<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mp_incomes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 20); // shopee|tiktok|...
            $table->string('platform_order_id', 80); // No. Pesanan / Order ID

            $table->dateTime('released_at')->nullable();

            // store as numeric to match your current schema style
            $table->decimal('platform_fee_total', 18, 2)->default(0);
            $table->decimal('refund_total', 18, 2)->default(0);
            $table->decimal('net_payout_actual', 18, 2)->default(0);

            $table->string('currency', 10)->default('IDR');

            $table->string('source_file')->nullable();
            $table->uuid('import_batch_id')->nullable();

            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->unique(['store_id', 'channel', 'platform_order_id'], 'mp_incomes_uq');
            $table->index(['channel', 'platform_order_id'], 'mp_incomes_channel_order_idx');
            $table->index(['released_at'], 'mp_incomes_released_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mp_incomes');
    }
};
