<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Insight', 'channel_code' => 'SHP'],
            ['name' => 'Gfid', 'channel_code' => 'TTK'],
            ['name' => 'Offline', 'channel_code' => 'OFFL'],
        ];

        foreach ($data as $row) {
            $channel = Channel::where('code', $row['channel_code'])->first();

            if (!$channel) {
                continue;
            }

            $code = strtoupper($channel->code . '-' . Str::slug($row['name'], '-'));

            Store::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $row['name'],
                    'channel_id' => $channel->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
