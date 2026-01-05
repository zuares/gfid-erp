<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_progress_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date');

            $table->foreignId('sewing_pickup_id')
                ->constrained('sewing_pickups');

            $table->foreignId('operator_id')
                ->nullable()
                ->constrained('employees');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_progress_adjustments');
    }
};
