<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_sku_recipes', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20)->nullable(); // null = berlaku untuk semua
            $table->string('mp_sku_parent', 50)->nullable();
            $table->string('mp_sku_code', 50)->nullable();
            $table->unsignedBigInteger('item_id');
            $table->integer('multiplier')->default(1);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['channel', 'mp_sku_parent']);
            $table->index(['channel', 'mp_sku_code']);
            $table->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_sku_recipes');
    }
};
