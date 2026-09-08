<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_payments', 'supplier_loan_id')) {
                $table->foreignId('supplier_loan_id')
                    ->nullable()
                    ->after('purchase_order_id')
                    ->constrained('supplier_loans')
                    ->nullOnDelete();

                $table->index('supplier_loan_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_payments', 'supplier_loan_id')) {
                $table->dropConstrainedForeignId('supplier_loan_id');
            }
        });
    }
};
