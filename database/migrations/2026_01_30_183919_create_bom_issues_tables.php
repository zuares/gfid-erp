<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_bom_id')->constrained('item_boms')->restrictOnDelete();

            // referensi proses: cutting_job / sewing_job / dll
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');

            $table->date('date')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete(); // RM warehouse (optional)

            $table->decimal('fg_qty', 12, 4)->default(0);

            $table->string('status', 20)->default('posted'); // posted/void (opsional)
            $table->timestamps();

            $table->unique(['source_type', 'source_id']); // ✅ idempotent
            $table->index(['item_bom_id']);
        });

        Schema::create('bom_issue_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_issue_id')->constrained('bom_issues')->cascadeOnDelete();

            $table->foreignId('material_item_id')->constrained('items')->restrictOnDelete();

            $table->decimal('bom_qty', 12, 4)->default(0); // qty BOM per 1 FG
            $table->decimal('scrap_pct', 12, 4)->default(0);
            $table->decimal('need_qty', 12, 4)->default(0); // qty yang di-issue (sudah termasuk scrap)

            $table->string('uom', 20)->default('pcs');

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['bom_issue_id', 'sort_order']);
            $table->index(['material_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_issue_lines');
        Schema::dropIfExists('bom_issues');
    }
};
