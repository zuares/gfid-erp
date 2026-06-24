<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PieceRateSeeder extends Seeder
{
    public function run(): void
    {
        $empId = DB::table('employees')->where('code', 'MYD')->value('id');
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2025-11-30'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 5000, 'effective_from' => '2025-11-30', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'MRF')->value('id');
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'cutting', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2025-11-30'],
            ['module' => 'cutting', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 800, 'effective_from' => '2025-11-30', 'effective_to' => '2026-12-30', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'MRF')->value('id');
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'cutting', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2025-11-30'],
            ['module' => 'cutting', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 1000, 'effective_from' => '2025-11-30', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'BBI')->value('id');
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2025-11-30'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 6000, 'effective_from' => '2025-11-30', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'BBI')->value('id');
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2025-11-30'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 5000, 'effective_from' => '2025-11-30', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'MYD')->value('id');
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2025-11-30'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 6000, 'effective_from' => '2025-11-30', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'MRF')->value('id');
        $catId = DB::table('item_categories')->where('code', 'CRG')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'cutting', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2025-11-30'],
            ['module' => 'cutting', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 1000, 'effective_from' => '2025-11-30', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'MRF')->value('id');
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'cutting', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2025-11-30'],
            ['module' => 'cutting', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 1000, 'effective_from' => '2025-11-30', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'RDN')->value('id');
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2026-01-01'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 5000, 'effective_from' => '2026-01-01', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'RDN')->value('id');
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2026-01-01'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 6000, 'effective_from' => '2026-01-01', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'RDN')->value('id');
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2026-02-02'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 7000, 'effective_from' => '2026-02-02', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'MYD')->value('id');
        $catId = DB::table('item_categories')->where('code', 'LCG')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2026-02-02'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 7000, 'effective_from' => '2026-02-02', 'effective_to' => '2026-12-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'MRF')->value('id');
        $catId = DB::table('item_categories')->where('code', 'TJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'cutting', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2026-05-03'],
            ['module' => 'cutting', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 1000, 'effective_from' => '2026-05-03', 'effective_to' => '2026-10-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'OJN')->value('id');
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2026-05-31'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 5000, 'effective_from' => '2026-05-31', 'effective_to' => '2026-07-30', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'TNY')->value('id');
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2026-05-31'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 5500, 'effective_from' => '2026-05-31', 'effective_to' => '2026-06-30', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'GFR')->value('id');
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2026-06-04'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 5000, 'effective_from' => '2026-06-04', 'effective_to' => '2026-08-31', 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'ANG')->value('id');
        $catId = DB::table('item_categories')->where('code', 'LJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2026-06-11'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 5000, 'effective_from' => '2026-06-11', 'effective_to' => null, 'notes' => null]
        );
        $empId = DB::table('employees')->where('code', 'ANG')->value('id');
        $catId = DB::table('item_categories')->where('code', 'SJR')->value('id');
        $itemId = DB::table('items')->where('code', null)->value('id');
        if ($empId) DB::table('piece_rates')->updateOrInsert(
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'effective_from' => '2026-06-13'],
            ['module' => 'sewing', 'employee_id' => $empId, 'item_category_id' => $catId, 'item_id' => $itemId, 'rate_per_pcs' => 5000, 'effective_from' => '2026-06-13', 'effective_to' => '2026-12-31', 'notes' => null]
        );

    }
}
