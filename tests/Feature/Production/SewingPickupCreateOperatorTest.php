<?php

namespace Tests\Feature\Production;

use App\Models\Employee;
use App\Models\PieceRate;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SewingPickupCreateOperatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_modal_lists_only_operators_configured_for_sewing_piece_rates(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'SEW-UI-OWNER',
        ]);

        foreach ([['WIP-CUT', 'Cutting WIP'], ['WIP-SEW', 'Sewing WIP']] as [$code, $name]) {
            Warehouse::create([
                'code' => $code,
                'name' => $name,
                'type' => 'internal',
                'active' => true,
            ]);
        }

        $configured = Employee::create([
            'code' => 'SEW-CONFIGURED',
            'name' => 'Operator Sewing Terdaftar',
            'role' => 'other',
            'payment_type' => 'variable',
            'active' => true,
        ]);

        $unconfigured = Employee::create([
            'code' => 'SEW-NOT-CONFIGURED',
            'name' => 'Operator Sewing Belum Terdaftar',
            'role' => 'sewing',
            'payment_type' => 'variable',
            'active' => true,
        ]);

        PieceRate::create([
            'module' => 'sewing',
            'employee_id' => $configured->id,
            'rate_per_pcs' => 5000,
            'effective_from' => '2026-01-01',
        ]);

        $this->actingAs($owner)
            ->get(route('production.sewing.pickups.create'))
            ->assertOk()
            ->assertSee('SEW-CONFIGURED - Operator Sewing Terdaftar')
            ->assertDontSee('SEW-NOT-CONFIGURED - Operator Sewing Belum Terdaftar');
    }

    public function test_auto_bom_pilot_uses_inline_operator_and_skips_confirmation_modal(): void
    {
        config([
            'accounting.standalone_dual_posting_production_sewing_pickup_enabled' => true,
            'accounting.standalone_production_sewing_pickup_auto_bom_negative_enabled' => true,
        ]);

        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'SEW-AUTO-OWNER',
        ]);

        foreach ([['WIP-CUT', 'Cutting WIP'], ['WIP-SEW', 'Sewing WIP'], ['RM', 'Raw Material']] as [$code, $name]) {
            Warehouse::create([
                'code' => $code,
                'name' => $name,
                'type' => 'internal',
                'active' => true,
            ]);
        }

        $operator = Employee::create([
            'code' => 'SEW-AUTO-OP',
            'name' => 'Operator Auto BOM',
            'role' => 'other',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        PieceRate::create([
            'module' => 'sewing',
            'employee_id' => $operator->id,
            'rate_per_pcs' => 5000,
            'effective_from' => '2026-01-01',
        ]);

        $this->actingAs($owner)
            ->get(route('production.sewing.pickups.create'))
            ->assertOk()
            ->assertSee('id="operator_id_auto"', false)
            ->assertSee('Simpan & Potong BOM', false)
            ->assertDontSee('id="confirmSubmitModal"', false);
    }
}
