<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_results', function (Blueprint $table) {
            $table->id();

            // stage proses: cutting / sewing (bisa diperluas)
            $table->string('stage'); // 'cutting', 'sewing', ...

            // Cutting
            $table->foreignId('cutting_job_id')
                ->nullable()
                ->constrained('cutting_jobs')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('cutting_job_bundle_id')
                ->nullable()
                ->constrained('cutting_job_bundles')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Sewing (disiapkan untuk nanti)
            $table->foreignId('sewing_job_id')->nullable();

            $table->date('qc_date');

            $table->decimal('qty_ok', 12, 2)->default(0);
            $table->decimal('qty_reject', 12, 2)->default(0);

            $table->foreignId('operator_id') // petugas QC
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            // status ringkas: ok / reject / mixed (opsional)
            $table->string('status')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['stage', 'cutting_job_id']);
            $table->index(['stage', 'cutting_job_bundle_id']);
            $table->index(['stage', 'sewing_job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_results');
    }
};
