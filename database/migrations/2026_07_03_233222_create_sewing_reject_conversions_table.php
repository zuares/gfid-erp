<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sewing_reject_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date')->index();
            $table->string('status', 20)->default('posted');

            $table->foreignId('source_reject_return_line_id')
                ->nullable()
                ->constrained('sewing_return_lines')
                ->nullOnDelete();

            $table->foreignId('source_finishing_job_line_id')
                ->nullable()
                ->constrained('finishing_job_lines')
                ->nullOnDelete();

            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('reject_item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('cutting_job_bundle_id')->nullable()->constrained('cutting_job_bundles')->nullOnDelete();
            $table->decimal('qty', 12, 3);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['source_reject_return_line_id']);
            $table->index(['source_finishing_job_line_id']);
            $table->index(['item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sewing_reject_conversions');
    }
};
