<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_bom_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_bom_id')->constrained('item_boms')->cascadeOnDelete();

            // bahan baku juga dari items (FLC280BLK, RIB280BLK, dst)
            $table->foreignId('material_item_id')->constrained('items')->restrictOnDelete();

            $table->decimal('qty', 12, 2); // qty per 1 pcs FG (atau per 1 unit produksi)
            $table->string('uom', 20)->default('pcs');

            $table->decimal('scrap_pct', 12, 2)->default(0); // waste %
            $table->boolean('is_optional')->default(false);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['item_bom_id', 'sort_order']);
            $table->index(['material_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_bom_lines');
    }
};
