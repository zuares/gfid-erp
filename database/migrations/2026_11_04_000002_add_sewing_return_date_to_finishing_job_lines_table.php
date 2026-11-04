<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('finishing_job_lines', 'sewing_return_date')) {
            return;
        }

        Schema::table('finishing_job_lines', function (Blueprint $table): void {
            $table->date('sewing_return_date')
                ->nullable()
                ->after('sewing_operator_name')
                ->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('finishing_job_lines', 'sewing_return_date')) {
            return;
        }

        Schema::table('finishing_job_lines', function (Blueprint $table): void {
            $table->dropIndex(['sewing_return_date']);
            $table->dropColumn('sewing_return_date');
        });
    }
};
