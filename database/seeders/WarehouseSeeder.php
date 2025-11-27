<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::create([
            'code' => 'RM',
            'name' => 'Raw Material Warehouse',
            'type' => 'internal',
            'active' => 1,
        ]);

        Warehouse::create([
            'code' => 'WIP-CUT',
            'name' => 'WIP Cutting Warehouse',
            'type' => 'wip',
            'active' => 1,
        ]);

        Warehouse::create([
            'code' => 'WIP-SEW',
            'name' => 'WIP Sewing Warehouse',
            'type' => 'wip',
            'active' => 1,
        ]);

        Warehouse::create([
            'code' => 'WIP-FIN',
            'name' => 'WIP Finishing Warehouse',
            'type' => 'wip',
            'active' => 1,
        ]);

        Warehouse::create([
            'code' => 'FG',
            'name' => 'Finished Goods Warehouse',
            'type' => 'internal',
            'active' => 1,
        ]);
        Warehouse::create([
            'code' => 'REJECT',
            'name' => 'WIP Reject / Defact',
            'type' => 'internal',
            'active' => 1,
        ]);
        Warehouse::create([
            'code' => 'REJ-CUT',
            'name' => 'WIP Reject Cutting',
            'type' => 'internal',
            'active' => 1,
        ]);
        Warehouse::create([
            'code' => 'REJ-SEW',
            'name' => 'WIP Reject Jahit',
            'type' => 'internal',
            'active' => 1,
        ]);
        Warehouse::create([
            'code' => 'PACKDED',
            'name' => 'WIP Packing',
            'type' => 'internal',
            'active' => 1,
        ]);
    }
}
