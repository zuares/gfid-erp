<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Lot;
use App\Models\PieceRate;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuttingJobCreateOperatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_modal_lists_only_operators_configured_for_cutting_piece_rates(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'CUT-UI-OWNER',
        ]);

        Warehouse::create([
            'code' => 'RM',
            'name' => 'Raw Materials',
            'type' => 'internal',
            'active' => true,
        ]);

        $configured = Employee::create([
            'code' => 'CUT-CONFIGURED',
            'name' => 'Operator Cutting Terdaftar',
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
        ]);

        $unconfigured = Employee::create([
            'code' => 'CUT-NOT-CONFIGURED',
            'name' => 'Operator Cutting Belum Terdaftar',
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
        ]);

        $configuredWithoutCuttingRole = Employee::create([
            'code' => 'CUT-CONFIGURED-OTHER-ROLE',
            'name' => 'Operator Cutting Role Lain',
            'role' => 'sewing',
            'payment_type' => 'variable',
            'active' => true,
        ]);

        PieceRate::create([
            'module' => 'cutting',
            'employee_id' => $configured->id,
            'rate_per_pcs' => 1000,
            'effective_from' => '2026-01-01',
        ]);

        PieceRate::create([
            'module' => 'cutting',
            'employee_id' => $configuredWithoutCuttingRole->id,
            'rate_per_pcs' => 1000,
            'effective_from' => '2026-01-01',
        ]);

        $this->actingAs($owner)
            ->get(route('production.cutting_jobs.create'))
            ->assertOk()
            ->assertSee('CUT-CONFIGURED - Operator Cutting Terdaftar')
            ->assertSee('CUT-CONFIGURED-OTHER-ROLE - Operator Cutting Role Lain')
            ->assertDontSee('CUT-NOT-CONFIGURED - Operator Cutting Belum Terdaftar');
    }

    public function test_edit_form_lists_configured_operator_even_when_employee_role_is_not_cutting(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'CUT-UI-EDIT-OWNER',
        ]);

        $warehouse = Warehouse::create([
            'code' => 'RM',
            'name' => 'Raw Materials',
            'type' => 'internal',
            'active' => true,
        ]);

        $category = ItemCategory::create([
            'code' => 'CUT-UI-EDIT-CAT',
            'name' => 'Cutting UI Edit Test',
        ]);

        $fabric = Item::create([
            'code' => 'CUT-UI-EDIT-FABRIC',
            'name' => 'Fabric Test',
            'type' => 'raw_material',
            'item_category_id' => $category->id,
        ]);

        $finished = Item::create([
            'code' => 'CUT-UI-EDIT-FINISHED',
            'name' => 'Finished Test',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
        ]);

        $lot = Lot::create([
            'code' => 'CUT-UI-EDIT-LOT',
            'item_id' => $fabric->id,
            'initial_qty' => 10,
            'initial_cost' => 10,
            'qty_onhand' => 10,
            'total_cost' => 10,
            'avg_cost' => 1,
        ]);

        $configuredOwner = Employee::create([
            'code' => 'CUT-UI-EDIT-OWN',
            'name' => 'Owner Cutting Test',
            'role' => 'owner',
            'payment_type' => 'variable',
            'active' => true,
        ]);

        PieceRate::create([
            'module' => 'cutting',
            'employee_id' => $configuredOwner->id,
            'item_category_id' => $category->id,
            'rate_per_pcs' => 1,
            'effective_from' => '2026-01-01',
        ]);

        $job = CuttingJob::create([
            'code' => 'CUT-UI-EDIT-JOB',
            'date' => '2026-09-03',
            'warehouse_id' => $warehouse->id,
            'lot_id' => $lot->id,
            'fabric_item_id' => $fabric->id,
            'operator_id' => $configuredOwner->id,
            'total_bundles' => 1,
            'total_qty_pcs' => 1,
            'status' => 'draft',
        ]);

        CuttingJobBundle::create([
            'cutting_job_id' => $job->id,
            'bundle_code' => 'CUT-UI-EDIT-BUNDLE',
            'bundle_no' => 1,
            'lot_id' => $lot->id,
            'finished_item_id' => $finished->id,
            'item_category_id' => $category->id,
            'qty_pcs' => 1,
            'qty_used_fabric' => 1,
            'operator_id' => $configuredOwner->id,
            'status' => 'cut',
        ]);

        $this->actingAs($owner)
            ->get(route('production.cutting_jobs.edit', $job))
            ->assertOk()
            ->assertSee('CUT-UI-EDIT-OWN - Owner Cutting Test');
    }
}
