<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mp_shipments', function (Blueprint $table) {
            $table->string('income_status')->default('none')->after('imported_at')->index();
            $table->dateTime('income_applied_at')->nullable()->after('income_status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('mp_shipments', function (Blueprint $table) {
            $table->dropColumn(['income_status', 'income_applied_at']);
        });
    }
};
