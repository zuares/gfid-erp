<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->string('process'); // cut|sew|fin
            $table->date('date');
            $table->decimal('qty', 14, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['production_order_id', 'process', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_activities');
    }
};
