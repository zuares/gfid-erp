<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $chanId = DB::table('channels')->where('code', 'SHP')->value('id');
        DB::table('stores')->updateOrInsert(
            ['code' => 'SHP-INSIGHT'],
            ['code' => 'SHP-INSIGHT', 'name' => 'Insight', 'channel_id' => $chanId, 'is_active' => 1, 'external_shop_id' => null]
        );
        $chanId = DB::table('channels')->where('code', 'TTK')->value('id');
        DB::table('stores')->updateOrInsert(
            ['code' => 'TTK-GFID'],
            ['code' => 'TTK-GFID', 'name' => 'Gfid', 'channel_id' => $chanId, 'is_active' => 1, 'external_shop_id' => null]
        );
        $chanId = DB::table('channels')->where('code', 'OFFL')->value('id');
        DB::table('stores')->updateOrInsert(
            ['code' => 'OFFL-OFFLINE'],
            ['code' => 'OFFL-OFFLINE', 'name' => 'Offline', 'channel_id' => $chanId, 'is_active' => 1, 'external_shop_id' => null]
        );

    }
}
