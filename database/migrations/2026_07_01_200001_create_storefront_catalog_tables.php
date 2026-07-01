<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Produk Website ────────────────────────────────────────────────────
        Schema::create('storefront_products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('product_type', 20)->default('regular'); // regular | jumbo
            $table->unsignedBigInteger('base_price')->default(0);
            $table->string('label', 40)->nullable();   // "Best Seller", "New", dll
            $table->string('image_url')->nullable();   // thumbnail utama
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->timestamps();
        });

        // ── Variant Warna (tiap warna punya foto sendiri) ─────────────────────
        Schema::create('storefront_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained('storefront_products')
                  ->cascadeOnDelete();
            $table->string('color_name', 60);
            $table->string('hex_color', 20)->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedBigInteger('price_override')->nullable(); // null = pakai base_price
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Ukuran yang dijual ────────────────────────────────────────────────
        Schema::create('storefront_product_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained('storefront_products')
                  ->cascadeOnDelete();
            $table->string('size_label', 20);          // S, M, L, XL, 3XL, dll
            $table->unsignedBigInteger('price_override')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_product_sizes');
        Schema::dropIfExists('storefront_product_variants');
        Schema::dropIfExists('storefront_products');
    }
};
