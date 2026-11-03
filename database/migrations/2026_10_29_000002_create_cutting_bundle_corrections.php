<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_bundle_corrections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cutting_job_id')->constrained('cutting_jobs')->cascadeOnUpdate();
            $table->foreignId('cutting_job_bundle_id')->constrained('cutting_job_bundles')->cascadeOnUpdate();
            $table->foreignId('original_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('replacement_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('return_fabric_qty', 18, 4);
            $table->decimal('unit_cost', 18, 6);
            $table->decimal('return_cost', 18, 2);
            $table->string('status', 30)->default('returned');
            $table->text('reason');
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('corrected_at')->nullable();
            $table->unsignedBigInteger('qc_cancellation_id')->nullable();
            $table->unsignedBigInteger('material_return_mutation_id')->nullable();
            $table->unsignedBigInteger('material_return_journal_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['cutting_job_id', 'cutting_job_bundle_id']);
            $table->index(['cutting_job_bundle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_bundle_corrections');
    }
};
