<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_finance_sync_runs', function (Blueprint $table) {
            $table->longText('output')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_finance_sync_runs', function (Blueprint $table) {
            $table->dropColumn('output');
        });
    }
};
