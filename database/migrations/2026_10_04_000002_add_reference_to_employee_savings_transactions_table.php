<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_savings_transactions', function (Blueprint $table) {
            $table->string('reference', 100)->nullable()->after('cash_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('employee_savings_transactions', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
};
