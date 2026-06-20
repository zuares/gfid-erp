<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_category_mappings', function (Blueprint $table) {
            $table->dropUnique(['item_category_id']);
            $table->boolean('is_primary')->default(false)->after('supplier_id');
            $table->unique(
                ['item_category_id', 'supplier_id'],
                'supplier_category_mappings_category_supplier_unique'
            );
        });

        DB::table('supplier_category_mappings')->update(['is_primary' => true]);
    }

    public function down(): void
    {
        $duplicates = DB::table('supplier_category_mappings')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get()
            ->groupBy('item_category_id');

        foreach ($duplicates as $rows) {
            DB::table('supplier_category_mappings')
                ->whereIn('id', $rows->skip(1)->pluck('id'))
                ->delete();
        }

        Schema::table('supplier_category_mappings', function (Blueprint $table) {
            $table->dropUnique('supplier_category_mappings_category_supplier_unique');
            $table->dropColumn('is_primary');
            $table->unique('item_category_id');
        });
    }
};
