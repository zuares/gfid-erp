<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_voucher_automations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name_prefix', 100)->default('GFID Flash Sale');
            $table->string('code_prefix', 20)->default('GFID');
            $table->unsignedSmallInteger('interval_hours')->default(4);
            $table->unsignedSmallInteger('duration_hours')->default(2);
            $table->unsignedInteger('usage_quantity')->default(25);
            $table->unsignedTinyInteger('voucher_type')->default(1);
            $table->unsignedTinyInteger('reward_type')->default(1);
            $table->decimal('min_basket_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->nullable();
            $table->decimal('percentage', 8, 2)->nullable();
            $table->decimal('max_price', 18, 2)->nullable();
            $table->json('display_channel_list')->nullable();
            $table->json('item_id_list')->nullable();
            $table->unsignedBigInteger('next_run_at')->index();
            $table->unsignedBigInteger('last_run_at')->nullable();
            $table->unsignedBigInteger('last_voucher_id')->nullable();
            $table->string('last_slot_key', 64)->nullable();
            $table->unsignedInteger('run_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_voucher_automations');
    }
};
