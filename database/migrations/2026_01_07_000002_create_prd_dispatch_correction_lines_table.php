<?php

// database/migrations/2026_01_07_000002_create_prd_dispatch_correction_lines_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prd_dispatch_correction_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prd_dispatch_correction_id');
            $table->unsignedBigInteger('stock_request_line_id');
            $table->unsignedBigInteger('item_id');

            // signed qty:
            // + => tambah dispatch (PRD->TRANSIT)
            // - => balikin (TRANSIT->PRD)
            $table->decimal('qty_adjust', 18, 6);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('prd_dispatch_correction_id')
                ->references('id')->on('prd_dispatch_corrections')
                ->onDelete('cascade');

            $table->foreign('stock_request_line_id')
                ->references('id')->on('stock_request_lines');

            $table->foreign('item_id')->references('id')->on('items');

            $table->index(['stock_request_line_id']);
            $table->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prd_dispatch_correction_lines');
    }
};
