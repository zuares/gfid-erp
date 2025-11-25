<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_job_bundles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cutting_job_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // contoh: BND-LOT-20251125-001-001
            $table->string('bundle_code')->unique();
            $table->unsignedInteger('bundle_no')->nullable(); // 1,2,3,... per job

            // LOT kain asal bundle ini
            $table->foreignId('lot_id')
                ->constrained() // default ke tabel "lots"
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // item jadi: K7BLK, K5BLK, dst
            $table->foreignId('finished_item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('qty_pcs', 12, 2); // isi bundle dalam pcs

            // qty kain yang dipakai untuk bundle ini (meter/yard/whatever)
            $table->decimal('qty_used_fabric', 12, 2)->default(0);

            // siapa yang motong bundle ini (opsional, tapi schema sudah siap)
            $table->foreignId('operator_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            // status dipakai di QC Cutting
            $table->string('status')->default('cut');
            // nilai: cut / qc_ok / qc_reject / qc_mixed

            $table->text('notes')->nullable();

            $table->timestamps();

            // QUALITY OF LIFE INDEX
            $table->unique(['cutting_job_id', 'bundle_no']); // 1 job: no bundle tidak boleh dobel
            $table->index(['lot_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_job_bundles');
    }
};
