<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->string('purpose')->nullable()->after('warehouse_id')->index();
            // contoh: manual | stock_opname | wip

            $table->string('wip_stage')->nullable()->after('purpose')->index();
            // cut | sew | fin

            $table->string('reference_type')->nullable()->after('wip_stage')->index();
            // cutting_bundle | sewing_pickup_line | finishing_line

            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type')->index();

            $table->unsignedBigInteger('operator_id')->nullable()->after('reference_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropIndex(['purpose']);
            $table->dropIndex(['wip_stage']);
            $table->dropIndex(['reference_type']);
            $table->dropIndex(['reference_id']);
            $table->dropIndex(['operator_id']);

            $table->dropColumn([
                'purpose',
                'wip_stage',
                'reference_type',
                'reference_id',
                'operator_id',
            ]);
        });
    }
};
