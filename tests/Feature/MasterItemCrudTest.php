<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Account;
use App\Models\ItemCategory;
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
            'code' => 'crud new 001',
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
        $response->assertRedirect(route('master.items.show', $item));

        $this->actingAs($this->owner)
            ->get(route('master.items.show', $item))
            ->assertOk()
            ->assertSee('Tambah Item Lagi');
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
        $response->assertRedirect(route('master.items.show', $item));
    }

    public function test_maintenance_category_forces_expense_account(): void
    {
        $category = ItemCategory::where('code', 'MNT')->firstOrFail();

        $response = $this->actingAs($this->owner)->post(route('master.items.store'), [
            'code' => 'MNT-001',
            'name' => 'Refill Pisau Mesin Potong',
            'unit' => 'pcs',
            'type' => 'material',
            'item_category_id' => $category->id,
            'default_allocation' => 'hpp',
            'default_expense_account_id' => '',
            'supplier_ids' => [],
            'primary_supplier_id' => '',
            'active' => 1,
            'barcodes' => [],
        ]);

        $item = Item::where('code', 'MNT-001')->firstOrFail();
        $maintenanceAccount = Account::where('code', '6105')->firstOrFail();

        $this->assertSame('expense', $item->default_allocation);
        $this->assertSame($maintenanceAccount->id, $item->default_expense_account_id);
        $this->assertFalse((bool) $item->is_stocked);
        $response->assertRedirect(route('master.items.show', $item));
    }

    public function test_item_code_suggestions_show_existing_codes(): void
    {
        $item = Item::create([
            'code' => 'ACC-ZIP-001',
            'name' => 'Resleting Hitam',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->getJson(route('master.items.code_suggestions', ['q' => 'acc zip']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $item->id,
                'code' => 'ACC-ZIP-001',
                'name' => 'Resleting Hitam',
            ]);
    }
}
