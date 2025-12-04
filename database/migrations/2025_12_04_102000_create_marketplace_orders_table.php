<?php

// database/migrations/2025_12_04_102000_create_marketplace_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('marketplace_stores')
                ->cascadeOnDelete();

            $table->string('external_order_id', 100)->index(); // nomor order marketplace
            $table->string('external_invoice_no', 100)->nullable();

            $table->dateTime('order_date')->index();
            $table->string('status', 30)->default('new'); // new, packed, shipped, completed, cancelled

            // buyer & pengiriman (snapshot)
            $table->string('buyer_name', 150)->nullable();
            $table->string('buyer_phone', 50)->nullable();

            $table->text('shipping_address')->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('shipping_province', 100)->nullable();
            $table->string('shipping_postal_code', 20)->nullable();
            $table->string('shipping_courier_code', 50)->nullable();
            $table->string('shipping_awb_no', 100)->nullable();

            // angka-angka
            $table->decimal('subtotal_items', 15, 2)->default(0);
            $table->decimal('shipping_fee_customer', 15, 2)->default(0);
            $table->decimal('shipping_discount_platform', 15, 2)->default(0);
            $table->decimal('voucher_discount', 15, 2)->default(0);
            $table->decimal('other_discount', 15, 2)->default(0);
            $table->decimal('total_paid_customer', 15, 2)->default(0);

            $table->decimal('platform_fee_total', 15, 2)->default(0); // komisi + admin
            $table->decimal('net_payout_estimated', 15, 2)->default(0);

            $table->string('payment_status', 30)->default('unpaid'); // unpaid, paid
            $table->dateTime('payment_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->foreignId('customer_id')->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->text('remarks')->nullable();
            $table->longText('raw_payload_json')->nullable(); // dump json dari API / CSV

            $table->timestamps();

            $table->index(['store_id', 'order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_orders');
    }
};
