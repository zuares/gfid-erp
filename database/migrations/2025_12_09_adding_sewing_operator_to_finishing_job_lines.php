<?php
// database/migrations/2025_12_09_adding_sewing_operator_to_finishing_job_lines.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finishing_job_lines', function (Blueprint $table) {
            // nullable FK to employees
            $table->unsignedBigInteger('sewing_operator_id')->nullable()->after('operator_id');
            $table->string('sewing_operator_name')->nullable()->after('sewing_operator_id');

            // optional index + FK constraint
            $table->index('sewing_operator_id');
            if (Schema::hasTable('employees')) {
                $table->foreign('sewing_operator_id')->references('id')->on('employees')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('finishing_job_lines', function (Blueprint $table) {
            if (Schema::hasColumn('finishing_job_lines', 'sewing_operator_id')) {
                // drop FK if exists (guarded)
                try {
                    $table->dropForeign(['sewing_operator_id']);
                } catch (\Throwable $e) {
                    // ignore if not exists
                }
                $table->dropIndex(['sewing_operator_id']);
                $table->dropColumn('sewing_operator_id');
            }
            if (Schema::hasColumn('finishing_job_lines', 'sewing_operator_name')) {
                $table->dropColumn('sewing_operator_name');
            }
        });
    }
};
