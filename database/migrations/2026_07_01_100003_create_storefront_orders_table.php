<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique(); // GF-20260701-001
            $table->string('visitor_token', 64)->nullable()->index();
            // Data customer
            $table->string('customer_name', 100);
            $table->string('customer_phone', 30);
            // Alamat
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->text('address_detail')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('address_note', 200)->nullable();
            // Produk & harga
            $table->json('items'); // snapshot cart saat order
            $table->unsignedInteger('subtotal')->default(0); // dalam rupiah
            $table->unsignedInteger('shipping_cost')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            // Pengiriman & pembayaran
            $table->string('shipping_courier', 50)->nullable();
            $table->string('shipping_service', 50)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_proof_url', 500)->nullable();
            // Status
            $table->string('status', 30)->default('pending');
            // pending → confirmed → processing → shipped → done | cancelled
            $table->timestamp('wa_sent_at')->nullable(); // saat user klik tombol WA
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_orders');
    }
};
