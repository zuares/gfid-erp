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

            // Stage proses QC: cutting / sewing / finishing / ...
            $table->string('stage', 20); // 'cutting', 'sewing', 'finishing', ...

            // Anchor utama: bundle dari Cutting
            $table->foreignId('cutting_job_bundle_id')
                ->nullable()
                ->constrained('cutting_job_bundles')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Optional: referensi dokumen per stage
            $table->foreignId('cutting_job_id')
                ->nullable()
                ->constrained('cutting_jobs')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Disiapkan untuk modul sewing
            $table->foreignId('sewing_job_id')
                ->nullable();

            // Disiapkan untuk modul finishing
            $table->foreignId('finishing_job_id')
                ->nullable()
                ->constrained('finishing_jobs')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Tanggal QC
            $table->date('qc_date');

            // Hasil QC
            $table->decimal('qty_ok', 12, 2)->default(0);
            $table->decimal('qty_reject', 12, 2)->default(0);

            // Ringkas alasan reject (opsional)
            $table->string('reject_reason', 100)->nullable(); // mis: 'bolong', 'kotor', 'oil', 'miss_stitch'

            // Petugas QC
            $table->foreignId('operator_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            // Status ringkas: ok / reject / mixed (opsional)
            $table->string('status', 20)->nullable();

            // Catatan tambahan
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index untuk report / filter cepat
            $table->index(['stage', 'cutting_job_bundle_id']);
            $table->index(['stage', 'cutting_job_id']);
            $table->index(['stage', 'sewing_job_id']);
            $table->index(['stage', 'finishing_job_id']);
            $table->index(['qc_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_results');
    }
};
