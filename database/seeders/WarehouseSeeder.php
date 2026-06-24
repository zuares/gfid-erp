<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
            DB::table('warehouses')->updateOrInsert(['code' => 'WH-TRANSIT'], [
                'code' => 'WH-TRANSIT',
                'name' => 'Transit PRD → RTS',
                'type' => 'internal',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'RM'], [
                'code' => 'RM',
                'name' => 'Bahan Baku',
                'type' => 'raw_material',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'WIP-CUT'], [
                'code' => 'WIP-CUT',
                'name' => 'Sedang Cutting',
                'type' => 'wip',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'WIP-SEW'], [
                'code' => 'WIP-SEW',
                'name' => 'Sedang Jahit',
                'type' => 'wip',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'WIP-FIN'], [
                'code' => 'WIP-FIN',
                'name' => 'Sedang Finishing',
                'type' => 'wip',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'WIP-PACK'], [
                'code' => 'WIP-PACK',
                'name' => 'Sedang Packing',
                'type' => 'wip',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'FG'], [
                'code' => 'FG',
                'name' => 'Finished Goods Warehouse',
                'type' => 'fg',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'WH-RTS'], [
                'code' => 'WH-RTS',
                'name' => 'Gudang Rumah',
                'type' => 'ready_to_sell',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'WH-PRD'], [
                'code' => 'WH-PRD',
                'name' => 'Gudang Produksi',
                'type' => 'production',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'REJ-CUT'], [
                'code' => 'REJ-CUT',
                'name' => 'Reject Cutting',
                'type' => 'reject',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'REJ-SEW'], [
                'code' => 'REJ-SEW',
                'name' => 'Reject Sewing',
                'type' => 'reject',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'REJ-FIN'], [
                'code' => 'REJ-FIN',
                'name' => 'Reject Finishing',
                'type' => 'reject',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
            DB::table('warehouses')->updateOrInsert(['code' => 'REJECT'], [
                'code' => 'REJECT',
                'name' => 'General Reject / Defect Warehouse',
                'type' => 'reject',
                'active' => 1,
                'address' => null,
                'notes' => null,
            ]);
    }
}
