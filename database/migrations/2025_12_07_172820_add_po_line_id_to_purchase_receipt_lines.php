<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            $table->foreignId('purchase_order_line_id')
                ->nullable()
                ->after('purchase_receipt_id')
                ->constrained('purchase_order_lines')
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_receipt_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_order_line_id');
        });
    }
};
