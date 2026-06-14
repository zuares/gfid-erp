<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finishing_repairs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date');
            $table->string('status')->default('posted');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('finishing_repair_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finishing_repair_id')->constrained('finishing_repairs')->cascadeOnDelete();
            $table->foreignId('finishing_job_line_id')->constrained('finishing_job_lines')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('cutting_job_bundle_id')->nullable()->constrained('cutting_job_bundles')->nullOnDelete();
            $table->decimal('qty_ok', 12, 3);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['finishing_job_line_id']);
            $table->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finishing_repair_lines');
        Schema::dropIfExists('finishing_repairs');
    }
};
