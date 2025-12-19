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
        Schema::create('shipment_return_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_return_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('item_id')
                ->constrained()
                ->cascadeOnUpdate();

            // Opsional: link ke line shipment asal (agar bisa validasi qty retur)
            $table->foreignId('shipment_line_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->integer('qty')->default(0);
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index(['shipment_return_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_return_lines');
    }
};
