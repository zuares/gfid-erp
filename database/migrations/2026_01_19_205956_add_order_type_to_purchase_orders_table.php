<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // jenis PO: material / finished_good
            $table
                ->string('order_type', 20)
                ->default('material')
                ->after('supplier_id')
                ->comment('material | finished_good');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('order_type');
        });
    }
};
