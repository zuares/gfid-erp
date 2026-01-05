<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_progress_adjustment_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sewing_progress_adjustment_id')
                ->constrained('sewing_progress_adjustments')
                ->cascadeOnDelete();

            $table->foreignId('sewing_pickup_line_id')
                ->constrained('sewing_pickup_lines');

            $table->decimal('qty_adjust', 14, 4)->default(0);

            $table->string('reason', 120)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_progress_adjustment_lines');
    }
};
