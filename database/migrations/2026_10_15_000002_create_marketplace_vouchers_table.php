<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('channel_code', 50)->nullable()->index();
            $table->unsignedBigInteger('voucher_id')->index();
            $table->string('voucher_code')->nullable();
            $table->string('voucher_name')->nullable();
            $table->string('voucher_status', 50)->nullable()->index();
            $table->unsignedTinyInteger('voucher_type')->nullable();
            $table->unsignedTinyInteger('reward_type')->nullable();
            $table->unsignedInteger('usage_quantity')->nullable();
            $table->unsignedInteger('current_usage')->nullable();
            $table->unsignedBigInteger('start_time')->nullable()->index();
            $table->unsignedBigInteger('end_time')->nullable()->index();
            $table->unsignedBigInteger('display_start_time')->nullable();
            $table->decimal('min_basket_price', 18, 2)->nullable();
            $table->decimal('discount_amount', 18, 2)->nullable();
            $table->decimal('percentage', 8, 2)->nullable();
            $table->decimal('max_price', 18, 2)->nullable();
            $table->json('display_channel_list')->nullable();
            $table->json('item_id_list')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('raw_json')->nullable();
            $table->json('detail_cache_json')->nullable();
            $table->timestamp('detail_cached_at')->nullable();
            $table->string('sync_status', 50)->default('synced')->index();
            $table->text('sync_error')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'voucher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_vouchers');
    }
};
