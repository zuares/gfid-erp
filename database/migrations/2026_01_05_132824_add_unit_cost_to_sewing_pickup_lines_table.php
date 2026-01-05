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
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            $table->decimal('unit_cost', 18, 6)->default(0)->after('qty_bundle');
        });
    }

    public function down(): void
    {
        Schema::table('sewing_pickup_lines', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }

};
