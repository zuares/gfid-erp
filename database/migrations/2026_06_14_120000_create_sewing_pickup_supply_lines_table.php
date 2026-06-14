<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_pickup_supply_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sewing_pickup_id')->constrained('sewing_pickups')->cascadeOnDelete();
            $table->foreignId('material_item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('required_qty', 14, 4)->default(0);
            $table->decimal('issued_qty', 14, 4)->default(0);
            $table->string('uom', 20)->nullable();
            $table->decimal('stock_available_snapshot', 14, 4)->default(0);
            $table->timestamps();

            $table->unique(['sewing_pickup_id', 'material_item_id'], 'swp_supply_unique');
            $table->index(['material_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_pickup_supply_lines');
    }
};
