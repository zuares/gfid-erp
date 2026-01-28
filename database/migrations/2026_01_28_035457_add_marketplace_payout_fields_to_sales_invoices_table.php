<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            // identitas marketplace
            if (!Schema::hasColumn('sales_invoices', 'channel')) {
                $table->string('channel', 30)->nullable()->index(); // shopee/tiktok/reseller
            }
            if (!Schema::hasColumn('sales_invoices', 'channel_order_no')) {
                $table->string('channel_order_no', 80)->nullable()->index(); // No. Pesanan
            }
            if (!Schema::hasColumn('sales_invoices', 'marketplace_status')) {
                $table->string('marketplace_status', 30)->nullable(); // shipping/completed
            }
            if (!Schema::hasColumn('sales_invoices', 'awb')) {
                $table->string('awb', 80)->nullable();
            }

            // waktu
            if (!Schema::hasColumn('sales_invoices', 'paid_at')) {
                $table->dateTime('paid_at')->nullable();
            }
            if (!Schema::hasColumn('sales_invoices', 'completed_at')) {
                $table->dateTime('completed_at')->nullable();
            }

            // payout shopee (real)
            if (!Schema::hasColumn('sales_invoices', 'released_at')) {
                $table->dateTime('released_at')->nullable()->index(); // Tanggal Dana Dilepaskan
            }
            if (!Schema::hasColumn('sales_invoices', 'platform_fee_total')) {
                $table->decimal('platform_fee_total', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('sales_invoices', 'refund_total')) {
                $table->decimal('refund_total', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('sales_invoices', 'net_payout_actual')) {
                $table->decimal('net_payout_actual', 12, 2)->default(0); // Total Penghasilan
            }

            // unique biar upsert aman per toko+order
            $table->unique(['store_id', 'channel', 'channel_order_no'], 'uniq_sales_inv_channel_order');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique('uniq_sales_inv_channel_order');

            $table->dropColumn([
                'channel', 'channel_order_no', 'marketplace_status', 'awb',
                'paid_at', 'completed_at',
                'released_at', 'platform_fee_total', 'refund_total', 'net_payout_actual',
            ]);
        });
    }
};
