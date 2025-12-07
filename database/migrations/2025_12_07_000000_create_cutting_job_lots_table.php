<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_job_lots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cutting_job_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('lot_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Opsional: kalau suatu saat mau catat rencana pemakaian per LOT
            $table->decimal('planned_fabric_qty', 18, 2)->default(0);
            $table->decimal('used_fabric_qty', 18, 4)
                ->default(0)
                ->after('planned_fabric_qty');

            $table->timestamps();

            $table->unique(['cutting_job_id', 'lot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_job_lots');
    }
};
