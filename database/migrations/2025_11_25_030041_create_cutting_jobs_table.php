<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutting_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // CUT-YYYYMMDD-###
            $table->date('date'); // tanggal cutting

            $table->foreignId('warehouse_id') // gudang CUT
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('lot_id') // dari LOT kain mana
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('fabric_item_id') // optional, refer ke Item kain
                ->nullable()
                ->constrained('items')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('operator_id')
                ->nullable()
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->unsignedInteger('total_bundles')->default(0);
            $table->decimal('total_qty_pcs', 12, 2)->default(0);

            $table->string('status')->default('draft');
            // draft / cut / posted (nanti saat siap ke QC)

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutting_jobs');
    }
};
