<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_product_model_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('marketplace_product_id')->constrained('marketplace_products')->cascadeOnDelete();
            $table->string('model_id');
            $table->string('model_name')->nullable();
            $table->string('model_sku')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('is_available')->default(true);
            $table->date('date');
            $table->timestamps();

            $table->unique(['marketplace_product_id', 'model_id', 'date'], 'marketplace_model_daily_unique');
            $table->index(['store_id', 'date']);
            $table->index(['marketplace_product_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_model_daily');
    }
};
