<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_packet_items', function (Blueprint $table) {
            $table->id();

            // identitas packet marketplace
            $table->string('channel', 32)->default('shopee'); // opsional
            $table->string('store', 64)->nullable(); // opsional (nama toko / code)
            $table->string('mp_shipment_id', 64); // kunci utama packet

            // item detail dari marketplace
            $table->string('sku', 96);
            $table->string('name', 255)->nullable();
            $table->unsignedInteger('qty')->default(0);

            // mapping ke master item internal (optional)
            $table->foreignId('item_id')->nullable()->constrained('items');

            // audit
            $table->timestamp('mapped_at')->nullable();
            $table->unsignedBigInteger('mapped_by')->nullable();

            $table->timestamps();

            // index buat query cepat
            $table->index(['mp_shipment_id']);
            $table->index(['sku']);
            $table->index(['item_id']);

            // idempotent key: 1 SKU per packet
            $table->unique(['mp_shipment_id', 'sku'], 'mp_packet_items_mp_ship_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_packet_items');
    }
};
