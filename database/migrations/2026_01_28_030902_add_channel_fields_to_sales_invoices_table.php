<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'channel')) {
                $table->string('channel', 30)->nullable()->index();
            }
            if (!Schema::hasColumn('sales_invoices', 'channel_order_no')) {
                $table->string('channel_order_no', 120)->nullable()->index();
            }
            if (!Schema::hasColumn('sales_invoices', 'channel_invoice_no')) {
                $table->string('channel_invoice_no', 120)->nullable()->index();
            }
            if (!Schema::hasColumn('sales_invoices', 'paid_at')) {
                $table->dateTime('paid_at')->nullable()->index();
            }
            if (!Schema::hasColumn('sales_invoices', 'completed_at')) {
                $table->dateTime('completed_at')->nullable()->index();
            }
            if (!Schema::hasColumn('sales_invoices', 'marketplace_status')) {
                $table->string('marketplace_status', 30)->nullable()->index();
            }
            if (!Schema::hasColumn('sales_invoices', 'awb')) {
                $table->string('awb', 80)->nullable()->index();
            }

            // NOTE: SQLite tidak enak untuk add unique composite dengan conditional check.
            // Kita tambahkan index biasa dulu. Unique bisa ditambah kalau kamu yakin datanya bersih.
            // $table->unique(['store_id', 'channel', 'channel_order_no'], 'si_store_channel_orderno_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            foreach ([
                'channel', 'channel_order_no', 'channel_invoice_no',
                'paid_at', 'completed_at', 'marketplace_status', 'awb',
            ] as $col) {
                if (Schema::hasColumn('sales_invoices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
