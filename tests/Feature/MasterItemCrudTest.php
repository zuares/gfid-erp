<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterItemCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'ITEM-OWNER',
        ]);
    }

    public function test_create_item_can_be_submitted_without_opening_quick_supplier_panel(): void
    {
        $response = $this->actingAs($this->owner)->post(route('master.items.store'), [
            'code' => 'CRUD-NEW-001',
            'name' => 'Item CRUD Baru',
            'unit' => 'pcs',
            'type' => 'material',
            'item_category_id' => '',
            'default_allocation' => 'hpp',
            'supplier_ids' => [],
            'primary_supplier_id' => '',
            'active' => 1,
            'barcodes' => [],
        ]);

        $item = Item::where('code', 'CRUD-NEW-001')->first();

        $this->assertNotNull($item);
        $response->assertRedirect(route('master.items.edit', $item));
    }

    public function test_quick_supplier_fields_do_not_override_item_fields(): void
    {
        $response = $this->actingAs($this->owner)->get(route('master.items.create'));

        $response->assertOk()
            ->assertSee('id="quick-supplier-code" data-quick-field="code"', false)
            ->assertSee('id="quick-supplier-name" data-quick-field="name"', false)
            ->assertDontSee('id="quick-supplier-code" name="code"', false)
            ->assertDontSee('id="quick-supplier-name" name="name"', false);
    }

    public function test_edit_item_can_be_submitted_without_opening_quick_supplier_panel(): void
    {
        $item = Item::create([
            'code' => 'CRUD-EDIT-001',
            'name' => 'Item Sebelum Edit',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'raw_material',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $response = $this->actingAs($this->owner)->put(route('master.items.update', $item), [
            'code' => 'CRUD-EDIT-001',
            'name' => 'Item Setelah Edit',
            'unit' => 'pcs',
            'type' => 'material',
            'item_category_id' => '',
            'default_allocation' => 'hpp',
            'supplier_ids' => [],
            'primary_supplier_id' => '',
            'active' => 1,
            'barcodes' => [],
        ]);

        $this->assertSame('Item Setelah Edit', $item->fresh()->name);
        $response->assertRedirect(route('master.items.index'));
    }
}
