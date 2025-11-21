<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_mutations', function (Blueprint $table) {
            $table->id();

            $table->date('date');

            $table->foreignId('warehouse_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('qty_change', 15, 3);
            $table->string('direction')->default('in');

            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['item_id', 'warehouse_id', 'date'], 'inventory_mutations_main_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_mutations');
    }
};
