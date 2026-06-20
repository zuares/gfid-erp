<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finishing_job_lines', function (Blueprint $table) {
            $table->boolean('bom_has_gaps')->default(false)->after('bom_applied_at')
                ->comment('true jika ada BOM material yang di-skip saat apply (cost=0/belum GRN)');
        });
    }

    public function down(): void
    {
        Schema::table('finishing_job_lines', function (Blueprint $table) {
            $table->dropColumn('bom_has_gaps');
        });
    }
};
