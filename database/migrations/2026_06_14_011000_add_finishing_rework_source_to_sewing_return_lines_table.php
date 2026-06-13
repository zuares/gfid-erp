<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_return_lines', function (Blueprint $table) {
            $table->foreignId('source_finishing_job_line_id')
                ->nullable()
                ->after('source_reject_return_line_id')
                ->constrained('finishing_job_lines')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sewing_return_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_finishing_job_line_id');
        });
    }
};
