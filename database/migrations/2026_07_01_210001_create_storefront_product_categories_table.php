<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 80);
            $table->text('description')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('storefront_products', function (Blueprint $table) {
            $table->foreignId('category_id')
                  ->nullable()
                  ->after('item_id')
                  ->constrained('storefront_product_categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('storefront_products', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\StorefrontProductCategory::class);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('storefront_product_categories');
    }
};
