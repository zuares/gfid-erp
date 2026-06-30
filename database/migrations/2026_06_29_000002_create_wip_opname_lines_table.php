<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wip_opname_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wip_opname_period_id')
                ->constrained('wip_opname_periods')
                ->cascadeOnDelete();

            $table->foreignId('cutting_job_bundle_id')
                ->constrained('cutting_job_bundles')
                ->restrictOnDelete();

            // Snapshot saat period dibuka
            $table->string('bundle_code');
            $table->string('item_code')->nullable();
            $table->string('item_name')->nullable();
            $table->string('cutting_job_code')->nullable();
            $table->decimal('qty_system', 12, 2); // snapshot cut_wip_qty

            // Diisi saat counting
            $table->decimal('qty_physical', 12, 2)->nullable();
            $table->decimal('difference', 12, 2)->nullable(); // physical - system

            $table->boolean('is_counted')->default(false);
            $table->text('notes')->nullable();

            $table->foreignIdFor(User::class, 'counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();

            $table->timestamps();

            $table->unique(['wip_opname_period_id', 'cutting_job_bundle_id']);
            $table->index(['wip_opname_period_id', 'is_counted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_opname_lines');
    }
};
