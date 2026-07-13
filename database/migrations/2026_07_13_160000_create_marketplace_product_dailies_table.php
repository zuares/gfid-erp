<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot harian metrik produk — ramah scale-up:
        // kolom numerik ringkas (tanpa JSON), unique per produk per hari,
        // index tanggal untuk query range, FK cascade.
        Schema::create('marketplace_product_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('marketplace_product_id')->constrained('marketplace_products')->cascadeOnDelete();
            $table->date('date');
            $table->string('item_status', 20)->nullable();
            $table->decimal('price_min', 15, 2)->nullable();
            $table->decimal('price_max', 15, 2)->nullable();
            $table->integer('stock_total')->default(0);
            $table->integer('sales')->nullable();          // kumulatif dari Shopee
            $table->integer('sales_delta')->nullable();     // penjualan hari itu (selisih vs kemarin)
            $table->integer('views')->nullable();
            $table->decimal('rating_star', 3, 2)->nullable();
            $table->timestamps();

            $table->unique(['marketplace_product_id', 'date'], 'uq_product_date');
            $table->index('date');
            $table->index(['store_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_dailies');
    }
};
