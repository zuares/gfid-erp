<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packing_job_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('packing_job_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // qty yang diambil dari FG (biasanya = qty_packed)
            $table->decimal('qty_fg', 12, 2);

            // qty yang benar-benar dipacking ke gudang PACKED
            $table->decimal('qty_packed', 12, 2);

            $table->timestamp('packed_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_job_lines');
    }
};
