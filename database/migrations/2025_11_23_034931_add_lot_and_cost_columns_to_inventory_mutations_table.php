<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_mutations', function (Blueprint $table) {
            $table->foreignId('lot_id')
                ->nullable()
                ->after('item_id')
                ->constrained('lots')
                ->nullOnDelete();

            $table->decimal('unit_cost', 15, 4)
                ->nullable()
                ->after('qty_change');

            $table->decimal('total_cost', 15, 2)
                ->nullable()
                ->after('unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_mutations', function (Blueprint $table) {
            $table->dropForeign(['lot_id']);
            $table->dropColumn(['lot_id', 'unit_cost', 'total_cost']);
        });
    }
};
