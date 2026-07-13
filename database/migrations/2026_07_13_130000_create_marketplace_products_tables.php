<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('item_id');                    // item_id Shopee (int64 → string)
            $table->string('item_name')->nullable();
            $table->string('item_sku')->nullable();
            $table->string('item_status')->nullable();    // NORMAL | BANNED | UNLIST | SELLER_DELETE
            $table->string('category_id')->nullable();
            $table->string('image_url')->nullable();
            $table->decimal('price_min', 15, 2)->nullable();
            $table->decimal('price_max', 15, 2)->nullable();
            $table->integer('stock_total')->default(0);
            $table->boolean('has_model')->default(false);
            $table->integer('sales')->nullable();         // dari get_item_extra_info
            $table->integer('views')->nullable();
            $table->decimal('rating_star', 3, 2)->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'item_id']);
            $table->index('item_status');
            $table->index('item_sku');
        });

        Schema::create('marketplace_product_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_product_id')->constrained('marketplace_products')->cascadeOnDelete();
            $table->string('model_id');                   // model_id Shopee (0 = tanpa varian)
            $table->string('model_name')->nullable();
            $table->string('model_sku')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_product_id', 'model_id']);
            $table->index('model_sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_models');
        Schema::dropIfExists('marketplace_products');
    }
};
