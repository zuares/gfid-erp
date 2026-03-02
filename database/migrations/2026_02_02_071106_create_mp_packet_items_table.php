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

            // Packet identity
            $table->string('channel')->nullable()->index(); // shopee/tiktok/etc
            $table->string('mp_shipment_id')->index(); // id paket marketplace (string biar aman lintas channel)

            // Raw SKU that produced this row (audit)
            $table->string('source_mp_sku_code')->nullable(); // mp_sku_code asal
            $table->string('source_mp_sku_parent')->nullable(); // mp_sku_parent (kalau ada)

            // Mapped internal item
            $table->foreignId('item_id')->constrained('items');

            // Final qty after applying recipe multiplier
            $table->unsignedInteger('qty')->default(0);

            // Optional audit
            $table->string('notes')->nullable();

            $table->timestamps();

            // Idempotent key:
            // one packet can generate multiple items per source sku,
            // but avoid duplicates on rerun
            $table->unique(
                ['channel', 'mp_shipment_id', 'item_id', 'source_mp_sku_code'],
                'mp_packet_items_packet_item_source_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_packet_items');
    }
};
