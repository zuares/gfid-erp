<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bundle_assemblies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date');
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->decimal('qty', 18, 6);
            $table->decimal('unit_cost', 18, 6)->nullable();
            $table->decimal('total_cost', 18, 2)->nullable();
            $table->string('status')->default('draft'); // draft|posted|void
            $table->dateTime('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'date']);
            $table->index(['warehouse_id', 'date']);
            $table->index(['status', 'date']);
        });

        Schema::create('bundle_assembly_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_assembly_id')->constrained('bundle_assemblies')->cascadeOnDelete();
            $table->foreignId('material_item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('qty_per_unit', 18, 8);
            $table->decimal('scrap_pct', 8, 4)->default(0);
            $table->decimal('qty_required', 18, 8);
            $table->decimal('qty_consumed', 18, 8)->nullable();
            $table->string('uom', 20)->default('pcs');
            $table->decimal('unit_cost', 18, 6)->nullable();
            $table->decimal('total_cost', 18, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['bundle_assembly_id', 'material_item_id'], 'bundle_assembly_lines_unique_material');
            $table->index(['material_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_assembly_lines');
        Schema::dropIfExists('bundle_assemblies');
    }
};
