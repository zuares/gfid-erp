<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_pickup_supply_lines', function (Blueprint $table) {
            $table->boolean('pending_cost')->default(false)->after('stock_available_snapshot');
            $table->decimal('issued_unit_cost', 14, 4)->nullable()->after('pending_cost');
        });
    }

    public function down(): void
    {
        Schema::table('sewing_pickup_supply_lines', function (Blueprint $table) {
            $table->dropColumn(['pending_cost', 'issued_unit_cost']);
        });
    }
};
