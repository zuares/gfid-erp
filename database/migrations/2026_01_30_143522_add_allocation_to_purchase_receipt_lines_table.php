<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            // allocation sudah ada, tapi kita jaga-jaga
            if (!Schema::hasColumn('purchase_receipt_lines', 'allocation')) {
                $table->string('allocation', 20)
                    ->default('hpp')
                    ->after('line_total');
            }

            // ✅ INI YANG KURANG
            if (!Schema::hasColumn('purchase_receipt_lines', 'expense_account_id')) {
                $table->foreignId('expense_account_id')
                    ->nullable()
                    ->after('allocation')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }

            $table->index(['allocation']);
            $table->index(['expense_account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_receipt_lines', 'expense_account_id')) {
                $table->dropForeign(['expense_account_id']);
                $table->dropColumn('expense_account_id');
            }

            if (Schema::hasColumn('purchase_receipt_lines', 'allocation')) {
                $table->dropColumn('allocation');
            }
        });
    }
};
