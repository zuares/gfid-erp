<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_shipments', function (Blueprint $table) {
            $table->id();

            // scope/store
            $table->unsignedBigInteger('store_id')->nullable()->index();

            // identity
            $table->string('channel', 32)->index(); // shopee|tiktok|...
            $table->string('platform_order_id', 80)->index(); // No. Pesanan / Order ID
            $table->string('platform_shipment_id', 80)->nullable(); // if platform provides package/shipment id
            $table->string('tracking_no', 80)->nullable()->index(); // resi/awb (boleh kosong)

            // status + lifecycle times (marketplace meaning)
            $table->string('marketplace_status', 60)->nullable(); // raw status from platform
            $table->string('status_norm', 32)->nullable()->index(); // optional: in_transit|delivered|canceled|...

            $table->dateTime('order_created_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('shipped_at')->nullable()->index();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            // metrics (buat reconcile & reporting)
            $table->unsignedInteger('total_qty')->nullable()->index();
            $table->decimal('order_subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('shipping_fee', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->string('currency', 8)->default('IDR');

            // payout/fees (opsional tapi future-proof)
            $table->decimal('platform_fee_total', 18, 2)->default(0);
            $table->decimal('refund_total', 18, 2)->default(0);
            $table->decimal('net_payout_actual', 18, 2)->default(0);
            $table->dateTime('released_at')->nullable();

            // import metadata
            $table->uuid('import_batch_id')->nullable()->index();
            $table->string('source_file')->nullable();
            $table->dateTime('imported_at')->nullable()->index();

            // raw as insurance
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            // anti-duplicate import safeguard
            $table->unique(['channel', 'platform_order_id', 'platform_shipment_id'], 'mp_shipments_unique_order_pkg');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_shipments');
    }
};
