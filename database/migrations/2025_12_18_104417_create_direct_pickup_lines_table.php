<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_pickup_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('direct_pickup_id')
                ->constrained('direct_pickups')
                ->cascadeOnDelete();

            // item FG yang dipickup ke RTS
            $table->foreignId('item_id')->constrained('items');

            $table->decimal('qty', 14, 2);

            // === sumber fakta (terkunci) ===
            // refer ke sewing_return_lines, karena qty OK ada per-line
            $table->foreignId('source_sewing_return_line_id')
                ->constrained('sewing_return_lines');

            // snapshot penjahit/operator dari sewing_returns.operator_id (employees.id)
            $table->foreignId('sewer_employee_id')
                ->constrained('employees');

            // optional snapshot untuk audit tambahan
            $table->foreignId('sewing_return_id')
                ->constrained('sewing_returns');

            // biaya per unit saat pickup (carry-cost WIP-FIN -> RTS)
            $table->decimal('unit_cost', 14, 4)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['item_id']);
            $table->index(['sewer_employee_id']);
            $table->index(['sewing_return_id']);
            $table->unique(['direct_pickup_id', 'source_sewing_return_line_id'], 'dp_unique_srline_per_doc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_pickup_lines');
    }
};
