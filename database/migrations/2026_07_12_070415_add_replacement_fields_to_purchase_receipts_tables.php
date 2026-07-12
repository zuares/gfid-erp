<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            $table->boolean('is_replacement')->default(false)->after('status');
            $table->foreignId('purchase_return_id')->nullable()->after('is_replacement')->constrained('purchase_returns')->nullOnDelete();
        });

        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            $table->foreignId('purchase_return_line_id')->nullable()->after('purchase_order_line_id')->constrained('purchase_return_lines')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_id']);
            $table->dropColumn(['is_replacement', 'purchase_return_id']);
        });

        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_line_id']);
            $table->dropColumn('purchase_return_line_id');
        });
    }
};
