<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->string('allocation', 16)->default('hpp')->after('unit_price'); // hpp|expense
            $table->unsignedBigInteger('expense_account_id')->nullable()->after('allocation');

            // FK optional (kalau tabel accounts ada)
            // $table->foreign('expense_account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            // $table->dropForeign(['expense_account_id']);
            $table->dropColumn(['expense_account_id', 'allocation']);
        });
    }
};
