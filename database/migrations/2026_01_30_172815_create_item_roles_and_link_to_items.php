<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Master table item_roles
        Schema::create('item_roles', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique(); // RM, SUP, PKG, FG
            $t->string('name', 100);
            $t->boolean('active')->default(true);

            // flags perilaku (penting buat Tahap 0)
            $t->boolean('is_stocked_default')->default(true);
            $t->boolean('is_wip_consumable')->default(false);
            $t->boolean('is_lot_tracked')->default(false);

            $t->timestamps();
        });

        // 2) Tambah FK di items
        Schema::table('items', function (Blueprint $t) {
            if (!Schema::hasColumn('items', 'item_role_id')) {
                $t->unsignedBigInteger('item_role_id')->nullable()->after('item_role');
                $t->index('item_role_id');
            }
        });

        Schema::table('items', function (Blueprint $t) {
            $t->foreign('item_role_id')->references('id')->on('item_roles');
        });

        // 3) Seed default roles
        $now = now();
        DB::table('item_roles')->insert([
            [
                'code' => 'RM',
                'name' => 'Raw Material',
                'is_stocked_default' => 1,
                'is_wip_consumable' => 1,
                'is_lot_tracked' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'SUP',
                'name' => 'Production Supply',
                'is_stocked_default' => 1,
                'is_wip_consumable' => 1,
                'is_lot_tracked' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'PKG',
                'name' => 'Packaging / Shipping',
                'is_stocked_default' => 0,
                'is_wip_consumable' => 0,
                'is_lot_tracked' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'FG',
                'name' => 'Finished Goods',
                'is_stocked_default' => 1,
                'is_wip_consumable' => 0,
                'is_lot_tracked' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 4) Mapping dari item_role lama (string) → item_role_id
        $rmId = DB::table('item_roles')->where('code', 'RM')->value('id');
        $supId = DB::table('item_roles')->where('code', 'SUP')->value('id');
        $pkgId = DB::table('item_roles')->where('code', 'PKG')->value('id');
        $fgId = DB::table('item_roles')->where('code', 'FG')->value('id');

        DB::table('items')->where('item_role', 'raw_material')->update(['item_role_id' => $rmId]);
        DB::table('items')->where('item_role', 'production_supply')->update(['item_role_id' => $supId]);
        DB::table('items')->where('item_role', 'shipping_supply')->update(['item_role_id' => $pkgId]);
        DB::table('items')->where('item_role', 'finished_good')->update(['item_role_id' => $fgId]);
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $t) {
            if (Schema::hasColumn('items', 'item_role_id')) {
                $t->dropForeign(['item_role_id']);
                $t->dropIndex(['item_role_id']);
                $t->dropColumn('item_role_id');
            }
        });

        Schema::dropIfExists('item_roles');
    }
};
