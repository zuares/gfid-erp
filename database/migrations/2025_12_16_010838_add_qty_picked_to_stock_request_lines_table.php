<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_request_lines', function (Blueprint $table) {
            // SQLite friendly: numeric -> decimal
            $table->decimal('qty_picked', 18, 4)->default(0)->after('qty_received');
        });
    }

    public function down(): void
    {
        Schema::table('stock_request_lines', function (Blueprint $table) {
            $table->dropColumn('qty_picked');
        });
    }
};
