<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('order_date');
            $table->string('status')->default('draft'); // draft|open|in_progress|done|cancelled
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('production_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items'); // FG item
            $table->decimal('qty_target', 14, 2);
            $table->timestamps();

            $table->index(['production_order_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_lines');
        Schema::dropIfExists('production_orders');
    }
};
