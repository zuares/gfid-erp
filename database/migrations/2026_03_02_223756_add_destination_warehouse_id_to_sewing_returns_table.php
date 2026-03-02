<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sewing_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('sewing_returns', 'destination_warehouse_id')) {
                $table->unsignedBigInteger('destination_warehouse_id')
                    ->nullable()
                    ->after('warehouse_id');

                $table->index('destination_warehouse_id', 'idx_sr_destination_wh');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sewing_returns', function (Blueprint $table) {
            if (Schema::hasColumn('sewing_returns', 'destination_warehouse_id')) {
                $table->dropIndex('idx_sr_destination_wh');
                $table->dropColumn('destination_warehouse_id');
            }
        });
    }
};
