<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $channelId = DB::table('channels')
            ->whereIn('code', ['TTK', 'tiktok'])
            ->orderByRaw("CASE WHEN code = 'TTK' THEN 0 ELSE 1 END")
            ->value('id');

        if (! $channelId) {
            $channelId = DB::table('channels')->insertGetId([
                'code' => 'TTK',
                'name' => 'Tiktok',
                'status' => 'active',
                'is_active' => 1,
                'meta' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('channels')->where('id', $channelId)->update([
                'name' => DB::raw("CASE WHEN name IS NULL OR name = '' THEN 'Tiktok' ELSE name END"),
                'status' => 'active',
                'is_active' => 1,
                'updated_at' => now(),
            ]);
        }

        $store = DB::table('stores')->where('code', 'TTK-GFID')->first();

        if ($store) {
            DB::table('stores')->where('id', $store->id)->update([
                'channel_id' => $channelId,
                'is_active' => 1,
                'status' => 'active',
                'updated_at' => now(),
            ]);
        } else {
            DB::table('stores')->insert([
                'code' => 'TTK-GFID',
                'name' => 'Gfid',
                'channel_id' => $channelId,
                'is_active' => 1,
                'status' => 'active',
                'external_shop_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Data seed/configuration sengaja tidak dinonaktifkan saat rollback.
    }
};
