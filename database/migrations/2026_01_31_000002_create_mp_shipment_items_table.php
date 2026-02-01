<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_shipment_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('mp_shipment_id')->index();

            // marketplace SKU identity (belum mapping ke items ERP)
            $table->string('sku_code', 80)->nullable()->index(); // Shopee: Nomor Referensi SKU; TikTok: Seller SKU
            $table->string('sku_parent', 80)->nullable(); // optional: SKU Induk / parent sku
            $table->string('product_name', 255)->nullable();
            $table->string('variant_name', 255)->nullable();

            $table->unsignedInteger('qty')->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);

            $table->json('raw_line')->nullable();

            $table->timestamps();

            $table->foreign('mp_shipment_id')
                ->references('id')->on('mp_shipments')
                ->onDelete('cascade');

            // prevent duplicate same SKU line re-import within the same mp_shipment (soft)
            $table->index(['mp_shipment_id', 'sku_code'], 'mp_items_shipment_sku_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_shipment_items');
    }
};
