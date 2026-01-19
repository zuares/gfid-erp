<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('last_price', 18, 2)->default(0);
            $table->timestamps();

            // indexes + unique
            $table->unique(['supplier_id', 'item_id'], 'supplier_items_unique');
            $table->index('supplier_id');
            $table->index('item_id');

            // FK: SQLite kadang rewel, tapi tetap oke kalau foreign_keys on
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_items');
    }
};
