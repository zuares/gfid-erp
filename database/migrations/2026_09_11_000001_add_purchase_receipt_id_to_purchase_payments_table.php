<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('purchase_payments', 'purchase_receipt_id')) {
            return;
        }

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->foreignId('purchase_receipt_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('purchase_receipts')
                ->nullOnDelete();

            $table->index(['purchase_receipt_id', 'type', 'voided_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('purchase_payments', 'purchase_receipt_id')) {
            return;
        }

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropIndex(['purchase_receipt_id', 'type', 'voided_at']);
            $table->dropConstrainedForeignId('purchase_receipt_id');
        });
    }
};
