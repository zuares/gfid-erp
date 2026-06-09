<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_order_settlements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('marketplace_orders')
                ->nullOnDelete();

            $table->string('channel_order_id', 100)->index(); // order_sn

            // ── Pembayaran customer ───────────────────────────────────────────
            $table->decimal('buyer_payment_amount', 15, 2)->default(0);  // total bayar customer

            // ── Fee marketplace ───────────────────────────────────────────────
            $table->decimal('commission_fee', 15, 2)->default(0);         // biaya admin / komisi
            $table->decimal('service_fee', 15, 2)->default(0);            // biaya layanan (payment gateway)
            $table->decimal('transaction_fee', 15, 2)->default(0);        // biaya transaksi tambahan

            // ── Voucher & diskon seller ───────────────────────────────────────
            $table->decimal('seller_voucher', 15, 2)->default(0);         // voucher ditanggung seller
            $table->decimal('seller_coin_cash_back', 15, 2)->default(0);  // koin shopee cashback

            // ── Ongkir ────────────────────────────────────────────────────────
            $table->decimal('actual_shipping_fee', 15, 2)->default(0);    // ongkir yang dibayar customer
            $table->decimal('shipping_fee_subsidy', 15, 2)->default(0);   // subsidi ongkir dari platform
            $table->decimal('reverse_shipping_fee', 15, 2)->default(0);   // ongkir return

            // ── Campaign & lainnya ────────────────────────────────────────────
            $table->decimal('activity_fee', 15, 2)->default(0);           // potongan campaign
            $table->decimal('drc_adjustable_refund', 15, 2)->default(0);  // refund / adjustment
            $table->decimal('escrow_tax', 15, 2)->default(0);             // pajak (PPN dsb)

            // ── Dana cair ─────────────────────────────────────────────────────
            $table->decimal('final_income', 15, 2)->default(0);           // dana cair / net payout
            $table->timestamp('settlement_time')->nullable();              // tanggal pencairan

            // ── Meta ─────────────────────────────────────────────────────────
            $table->timestamp('synced_at')->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->unique('channel_order_id'); // satu settlement per order
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_settlements');
    }
};
