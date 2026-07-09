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
        Schema::table('order_fulfillment_lines', function (Blueprint $table) {
            $table->decimal('allocated_qty', 15, 2)->default(0)->after('qty_fulfilled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_fulfillment_lines', function (Blueprint $table) {
            $table->dropColumn('allocated_qty');
        });
    }
};
