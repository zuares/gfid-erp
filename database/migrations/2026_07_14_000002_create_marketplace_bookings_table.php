<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penyimpanan "Pesanan Kilat" (booking / fulfillment gudang Shopee).
     * Berbeda dari marketplace_orders: booking bisa BELUM punya order_sn,
     * diidentifikasi lewat booking_sn. Anti-duplikat via booking_sn unik.
     */
    public function up(): void
    {
        Schema::create('marketplace_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('booking_sn')->unique();
            $table->string('order_sn')->nullable()->index();
            $table->string('booking_status')->nullable()->index();
            $table->string('shipping_carrier')->nullable();
            $table->string('tracking_number')->nullable()->index();
            $table->string('package_number')->nullable();
            $table->string('shipping_document_status')->nullable();
            $table->integer('create_time')->nullable()->index();
            $table->integer('update_time')->nullable()->index();
            $table->json('items')->nullable();
            $table->json('raw_json')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_bookings');
    }
};
