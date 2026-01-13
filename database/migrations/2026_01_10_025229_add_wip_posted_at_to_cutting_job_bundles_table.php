<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            // marker: bundle sudah pernah posting WIP dari QC
            $table->dateTime('wip_posted_at')
                ->nullable()
                ->after('wip_qty')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('cutting_job_bundles', function (Blueprint $table) {
            $table->dropColumn('wip_posted_at');
        });
    }
};
