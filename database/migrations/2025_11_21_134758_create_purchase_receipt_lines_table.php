<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_receipt_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_receipt_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('lot_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->decimal('qty_received', 15, 3)->default(0);
            $table->decimal('qty_reject', 15, 3)->default(0);

            $table->string('unit')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);

            $table->string('notes')->nullable();

            $table->timestamps();

            $table->index(['purchase_receipt_id', 'item_id'], 'pr_lines_main_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_lines');
    }
};
