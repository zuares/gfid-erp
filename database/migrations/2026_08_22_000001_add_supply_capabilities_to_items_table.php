<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('items', 'can_buy')) {
            Schema::table('items', function (Blueprint $table) {
                $table->boolean('can_buy')->default(false)->after('production_source');
            });
        }

        if (!Schema::hasColumn('items', 'can_make')) {
            Schema::table('items', function (Blueprint $table) {
                $table->boolean('can_make')->default(false)->after('can_buy');
            });
        }

        if (!Schema::hasColumn('items', 'default_supply_source')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('default_supply_source', 16)->nullable()->after('can_make');
            });
        }

        DB::table('items')
            ->whereIn('type', ['finished_good', 'wip'])
            ->whereNull('default_supply_source')
            ->update([
                'can_buy' => DB::raw("CASE WHEN production_source = 'buy' THEN 1 ELSE 0 END"),
                'can_make' => DB::raw("CASE WHEN production_source = 'in_house' THEN 1 ELSE 0 END"),
                'default_supply_source' => DB::raw("CASE
                    WHEN production_source = 'in_house' THEN 'make'
                    WHEN production_source = 'outsource' THEN 'outsource'
                    ELSE 'buy'
                END"),
                'updated_at' => now(),
            ]);

        DB::table('items')
            ->whereNotIn('type', ['finished_good', 'wip'])
            ->update([
                'can_buy' => false,
                'can_make' => false,
                'default_supply_source' => null,
                'updated_at' => now(),
            ]);

        Schema::table('items', function (Blueprint $table) {
            $table->index(['can_buy', 'can_make'], 'items_can_buy_can_make_index');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('items', 'can_buy') || Schema::hasColumn('items', 'can_make')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropIndex('items_can_buy_can_make_index');
            });
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('items', 'default_supply_source') ? 'default_supply_source' : null,
            Schema::hasColumn('items', 'can_make') ? 'can_make' : null,
            Schema::hasColumn('items', 'can_buy') ? 'can_buy' : null,
        ]));

        if ($columns) {
            Schema::table('items', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
