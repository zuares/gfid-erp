<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('channel_code', 50)->nullable()->index();
            $table->unsignedBigInteger('discount_id')->nullable()->index();
            $table->unsignedBigInteger('source_discount_id')->nullable()->index();
            $table->string('discount_name')->nullable();
            $table->string('discount_status', 50)->nullable()->index();
            $table->string('sync_status', 50)->default('synced')->index();
            $table->text('sync_error')->nullable();
            $table->unsignedBigInteger('start_time')->nullable()->index();
            $table->unsignedBigInteger('end_time')->nullable()->index();
            $table->unsignedInteger('item_count')->default(0);
            $table->json('item_list_json')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('create_response')->nullable();
            $table->json('items_response')->nullable();
            $table->json('update_response')->nullable();
            $table->json('end_response')->nullable();
            $table->json('delete_response')->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'discount_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_promotions');
    }
};
