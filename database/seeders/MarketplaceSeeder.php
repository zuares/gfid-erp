<?php

namespace Database\Seeders;

use App\Models\MarketplaceChannel;
use App\Models\MarketplaceStore;
use Illuminate\Database\Seeder;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        // CHANNELS
        $shopee = MarketplaceChannel::updateOrCreate(
            ['code' => 'shopee'],
            [
                'name' => 'Shopee',
                'is_active' => true,
            ]
        );

        $tokped = MarketplaceChannel::updateOrCreate(
            ['code' => 'tokopedia'],
            [
                'name' => 'Tokopedia',
                'is_active' => true,
            ]
        );

        // STORES
        MarketplaceStore::updateOrCreate(
            [
                'short_code' => 'SHP-MAIN',
            ],
            [
                'channel_id' => $shopee->id,
                'external_store_id' => 'shop12345',
                'name' => 'Toko Shopee Utama',
                'default_warehouse_id' => null,
                'is_active' => true,
            ]
        );

        MarketplaceStore::updateOrCreate(
            [
                'short_code' => 'TP-MAIN',
            ],
            [
                'channel_id' => $tokped->id,
                'external_store_id' => 'tp67890',
                'name' => 'Toko Tokopedia Official',
                'default_warehouse_id' => null,
                'is_active' => true,
            ]
        );
    }
}
