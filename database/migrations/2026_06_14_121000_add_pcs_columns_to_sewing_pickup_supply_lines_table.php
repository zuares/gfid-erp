<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_pickup_supply_lines', function (Blueprint $table) {
            $table->decimal('required_pcs', 14, 4)->default(0)->after('issued_qty');
            $table->decimal('issued_pcs', 14, 4)->default(0)->after('required_pcs');
        });
    }

    public function down(): void
    {
        Schema::table('sewing_pickup_supply_lines', function (Blueprint $table) {
            $table->dropColumn(['required_pcs', 'issued_pcs']);
        });
    }
};
