<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_bom_lines', function (Blueprint $table) {
            $table->string('usage_stage', 30)->default('main_material')->after('material_item_id');
            $table->index(['usage_stage']);
        });

        $sewingLineIds = DB::table('item_bom_lines as bl')
            ->join('items as i', 'i.id', '=', 'bl.material_item_id')
            ->where(function ($q) {
                $q->where('i.code', 'like', 'RIB%')
                    ->orWhere('i.code', 'like', 'KRT%');
            })
            ->pluck('bl.id');

        if ($sewingLineIds->isNotEmpty()) {
            DB::table('item_bom_lines')
                ->whereIn('id', $sewingLineIds)
                ->update(['usage_stage' => 'sewing_supply']);
        }

        $packingLineIds = DB::table('item_bom_lines as bl')
            ->join('items as i', 'i.id', '=', 'bl.material_item_id')
            ->where(function ($q) {
                $q->where('i.code', 'like', 'TLK%')
                    ->orWhere('i.code', 'like', 'OPP%')
                    ->orWhere('i.code', 'like', 'PACK%');
            })
            ->pluck('bl.id');

        if ($packingLineIds->isNotEmpty()) {
            DB::table('item_bom_lines')
                ->whereIn('id', $packingLineIds)
                ->update(['usage_stage' => 'packing_supply']);
        }
    }

    public function down(): void
    {
        Schema::table('item_bom_lines', function (Blueprint $table) {
            $table->dropIndex(['usage_stage']);
            $table->dropColumn('usage_stage');
        });
    }
};
