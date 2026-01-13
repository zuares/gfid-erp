<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_adjustment_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('cutting_job_bundle_id')
                ->nullable()
                ->after('lot_id')
                ->index();

            $table->unsignedBigInteger('sewing_pickup_line_id')
                ->nullable()
                ->after('cutting_job_bundle_id')
                ->index();

            $table->unsignedBigInteger('finishing_job_line_id')
                ->nullable()
                ->after('sewing_pickup_line_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_adjustment_lines', function (Blueprint $table) {
            $table->dropIndex(['cutting_job_bundle_id']);
            $table->dropIndex(['sewing_pickup_line_id']);
            $table->dropIndex(['finishing_job_line_id']);

            $table->dropColumn([
                'cutting_job_bundle_id',
                'sewing_pickup_line_id',
                'finishing_job_line_id',
            ]);
        });
    }
};
