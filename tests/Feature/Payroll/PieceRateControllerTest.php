<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\PieceRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PieceRateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_piece_rate_item_must_belong_to_selected_category(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'PR-OWNER-001',
        ]);
        $employee = Employee::create([
            'code' => 'PR-EMP-001',
            'name' => 'Operator Piece Rate',
            'role' => 'sewing',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $category = ItemCategory::create([
            'code' => 'PR-CAT-A',
            'name' => 'Kategori A',
            'active' => true,
        ]);
        $otherCategory = ItemCategory::create([
            'code' => 'PR-CAT-B',
            'name' => 'Kategori B',
            'active' => true,
        ]);
        $item = Item::create([
            'code' => 'PR-ITEM-001',
            'name' => 'Item Kategori A',
            'unit' => 'pcs',
            'type' => 'material',
            'item_category_id' => $category->id,
            'active' => true,
        ]);

        $payload = [
            'module' => 'sewing',
            'employee_id' => $employee->id,
            'item_category_id' => $otherCategory->id,
            'item_id' => $item->id,
            'rate_per_pcs' => 1500,
            'effective_from' => '2026-11-05',
        ];

        $this->actingAs($owner)
            ->post(route('payroll.piece_rates.store'), $payload)
            ->assertSessionHasErrors('item_id');

        $this->assertDatabaseCount('piece_rates', 0);
    }

    public function test_piece_rate_can_be_saved_for_an_item_inside_selected_category(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'PR-OWNER-002',
        ]);
        $employee = Employee::create([
            'code' => 'PR-EMP-002',
            'name' => 'Operator Piece Rate Valid',
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $category = ItemCategory::create([
            'code' => 'PR-CAT-C',
            'name' => 'Kategori C',
            'active' => true,
        ]);
        $item = Item::create([
            'code' => 'PR-ITEM-002',
            'name' => 'Item Kategori C',
            'unit' => 'pcs',
            'type' => 'material',
            'item_category_id' => $category->id,
            'active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('payroll.piece_rates.store'), [
                'module' => 'cutting',
                'employee_id' => $employee->id,
                'item_category_id' => $category->id,
                'item_id' => $item->id,
                'rate_per_pcs' => 1250,
                'effective_from' => '2026-11-05',
            ])
            ->assertRedirect(route('payroll.piece_rates.index'));

        $this->assertDatabaseHas('piece_rates', [
            'employee_id' => $employee->id,
            'item_category_id' => $category->id,
            'item_id' => $item->id,
            'rate_per_pcs' => 1250,
        ]);
    }
}
