<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_boms', function (Blueprint $table) {
            $table->id();

            // 1 BOM per SKU
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();

            $table->string('name')->nullable(); // opsional: "BOM C5BLK"
            $table->boolean('active')->default(true);

            $table->timestamps();

            // 1 SKU = 1 BOM (paling simpel)
            $table->unique('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_boms');
    }
};
