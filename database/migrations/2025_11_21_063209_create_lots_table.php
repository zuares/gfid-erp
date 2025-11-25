<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();

            $table->decimal('initial_qty', 15, 2)->default(0);
            $table->decimal('initial_cost', 15, 2)->default(0);

            $table->decimal('qty_onhand', 15, 3)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('avg_cost', 15, 4)->default(0);

            $table->string('status')->default('open');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
