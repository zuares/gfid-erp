<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            UserEmployeeSeeder::class,
            ItemCategorySeeder::class,
            SupplierSeeder::class,
            ItemSeeder::class,
            EmployeeSeeder::class,
            WarehouseSeeder::class,
            // DemoCuttingPayrollSeeder::class,
            PieceRateSeeder::class,
            ProductionCostPeriodSeeder::class,
            ChannelsSeeder::class,
            StoreSeeder::class,
            // InventoryTransferDemoSeeder::class,
            AccountSeeder::class,

        ]);
    }
}
