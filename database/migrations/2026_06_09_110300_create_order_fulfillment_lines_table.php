<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_fulfillment_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fulfillment_id')
                ->constrained('order_fulfillments')
                ->cascadeOnDelete();

            // Referensi ke item order marketplace
            $table->foreignId('marketplace_order_item_id')
                ->nullable()
                ->constrained('marketplace_order_items')
                ->nullOnDelete();

            // SKU asli dari marketplace (disimpan sebagai snapshot)
            $table->string('marketplace_sku')->nullable();
            $table->string('marketplace_item_name')->nullable();

            // Item internal hasil mapping (nullable = belum ditemukan / perlu diisi manual)
            $table->foreignId('item_id')
                ->nullable()
                ->constrained('items')
                ->nullOnDelete();

            // Lot stok yang akan dipotong
            $table->foreignId('lot_id')
                ->nullable()
                ->constrained('lots')
                ->nullOnDelete();

            $table->integer('qty_ordered')->default(0);
            $table->integer('qty_fulfilled')->default(0);

            // Apakah item ini diganti manual oleh owner (bukan auto-mapping)
            $table->boolean('substituted')->default(false);

            // Stok tersedia saat fulfillment dibuat (snapshot untuk UI)
            $table->integer('stock_available')->default(0);

            $table->string('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fulfillment_lines');
    }
};
