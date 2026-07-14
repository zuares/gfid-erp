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
        Schema::create('marketplace_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('return_sn')->unique();
            $table->string('order_sn')->index();
            $table->string('status')->nullable()->index();
            $table->string('reason')->nullable();
            $table->string('reason_text_code')->nullable();
            $table->tinyInteger('return_solution')->nullable()->comment('Shopee: 0 = RETURN_REFUND (Retur & Refund), 1 = REFUND (Refund saja)');
            $table->decimal('amount_before_discount', 15, 2)->default(0);
            $table->boolean('needs_logistics')->default(false);
            $table->string('tracking_number')->nullable()->index();
            $table->integer('create_time')->nullable()->index();
            $table->integer('update_time')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('marketplace_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_return_id')->constrained('marketplace_returns')->onDelete('cascade');
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('item_name')->nullable();
            $table->string('variation_name')->nullable();
            $table->string('item_sku')->nullable()->index();
            $table->string('variation_sku')->nullable()->index();
            $table->integer('return_item_quantity')->default(1);
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_return_items');
        Schema::dropIfExists('marketplace_returns');
    }
};
