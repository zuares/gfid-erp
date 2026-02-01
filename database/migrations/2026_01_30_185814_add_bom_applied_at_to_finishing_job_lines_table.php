<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finishing_job_lines', function (Blueprint $table) {
            $table->timestamp('bom_applied_at')->nullable()->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('finishing_job_lines', function (Blueprint $table) {
            $table->dropColumn('bom_applied_at');
        });
    }
};
