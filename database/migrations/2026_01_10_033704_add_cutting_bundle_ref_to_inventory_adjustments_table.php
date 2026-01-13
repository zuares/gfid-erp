<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_adjustments', 'cutting_job_bundle_id')) {
                $table->unsignedBigInteger('cutting_job_bundle_id')->nullable()->after('module');
                $table->index(['module', 'cutting_job_bundle_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_adjustments', 'cutting_job_bundle_id')) {
                $table->dropIndex(['module', 'cutting_job_bundle_id']);
                $table->dropColumn('cutting_job_bundle_id');
            }
        });
    }
};
