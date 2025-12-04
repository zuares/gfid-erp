<?php

namespace Database\Seeders;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceStore;
use Illuminate\Database\Seeder;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $shopee = MarketplaceChannel::create([
            'code' => 'shopee',
            'name' => 'Shopee',
            'is_active' => true,
        ]);

        $tokped = MarketplaceChannel::create([
            'code' => 'tokopedia',
            'name' => 'Tokopedia',
            'is_active' => true,
        ]);

        MarketplaceStore::create([
            'channel_id' => $shopee->id,
            'external_store_id' => 'shop12345',
            'name' => 'Toko Shopee Utama',
            'short_code' => 'SHP-MAIN',
            'default_warehouse_id' => null,
            'is_active' => true,
        ]);

        MarketplaceStore::create([
            'channel_id' => $tokped->id,
            'external_store_id' => 'tp67890',
            'name' => 'Toko Tokopedia Official',
            'short_code' => 'TP-MAIN',
            'default_warehouse_id' => null,
            'is_active' => true,
        ]);
    }
}
