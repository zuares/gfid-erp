<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_qc_cancellations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cutting_job_id');
            $table->unsignedBigInteger('cutting_job_bundle_id');
            $table->unsignedBigInteger('qc_result_id')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();

            $table->decimal('qty_ok', 12, 2)->default(0);
            $table->decimal('qty_reject', 12, 2)->default(0);
            $table->decimal('ok_cost', 15, 2)->default(0);
            $table->decimal('reject_cost', 15, 2)->default(0);
            $table->decimal('raw_cost_share', 15, 2)->default(0);
            $table->decimal('labor_cost_share', 15, 2)->default(0);

            $table->text('reason');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->unsignedBigInteger('reversal_journal_id')->nullable();
            $table->unsignedBigInteger('reversal_cutoff_mutation_id')->nullable();
            $table->unsignedBigInteger('repost_journal_id')->nullable();
            $table->unsignedBigInteger('reprocessed_qc_result_id')->nullable();
            $table->timestamp('reprocessed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['cutting_job_id', 'cutting_job_bundle_id']);
            $table->index(['cutting_job_bundle_id', 'reprocessed_at']);
            $table->index('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_qc_cancellations');
    }
};
