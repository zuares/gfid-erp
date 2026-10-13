<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_product_id')
                ->constrained('marketplace_products')
                ->cascadeOnDelete();
            $table->string('shopee_image_id', 160);
            $table->text('image_url')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->string('status', 24)->default('active');
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_product_id', 'shopee_image_id'], 'mp_images_product_image_unique');
            $table->index(['marketplace_product_id', 'status'], 'mp_images_product_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_images');
    }
};
