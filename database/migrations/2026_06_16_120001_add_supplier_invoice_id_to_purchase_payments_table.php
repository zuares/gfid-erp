<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            // FK opsional ke supplier_invoices — nullable agar backward compatible
            $table->unsignedBigInteger('supplier_invoice_id')
                ->nullable()
                ->after('purchase_order_id');

            $table->index('supplier_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropIndex(['supplier_invoice_id']);
            $table->dropColumn('supplier_invoice_id');
        });
    }
};
