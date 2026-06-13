<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finishing_job_lines', function (Blueprint $table) {
            $table->string('reject_cause', 20)
                ->default('finishing')
                ->after('reject_reason');
        });
    }

    public function down(): void
    {
        Schema::table('finishing_job_lines', function (Blueprint $table) {
            $table->dropColumn('reject_cause');
        });
    }
};
