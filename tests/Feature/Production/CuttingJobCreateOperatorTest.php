<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
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
}
