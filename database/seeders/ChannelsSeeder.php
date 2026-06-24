<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChannelsSeeder extends Seeder
{
    public function run(): void
    {
            DB::table('channels')->updateOrInsert(['code' => 'SHP'], [
                'code' => 'SHP',
                'name' => 'Shopee',
                'is_active' => 1,
                'status' => 'active',
                'meta' => null,
            ]);
            DB::table('channels')->updateOrInsert(['code' => 'TTK'], [
                'code' => 'TTK',
                'name' => 'Tiktok',
                'is_active' => 1,
                'status' => 'active',
                'meta' => null,
            ]);
            DB::table('channels')->updateOrInsert(['code' => 'OFFL'], [
                'code' => 'OFFL',
                'name' => 'Offline',
                'is_active' => 1,
                'status' => 'active',
                'meta' => null,
            ]);
    }
}
