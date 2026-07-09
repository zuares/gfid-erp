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
        Schema::table('shipment_lines', function (Blueprint $table) {
            $table->integer('allocated_qty')->default(0)->after('qty_scanned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_lines', function (Blueprint $table) {
            $table->dropColumn('allocated_qty');
        });
    }
};
