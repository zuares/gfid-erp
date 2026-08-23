<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Account;
use App\Models\ItemCategory;
use App\Models\ItemPurchaseTreatment;
use App\Models\ItemTypeOption;
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
        $this->assertSame('CRUD-NEW-001', $item->sku);
        $response->assertRedirect(route('master.items.show', $item));

        $this->actingAs($this->owner)
            ->get(route('master.items.show', $item))
            ->assertOk()
            ->assertSee('Tambah Item Lagi');
    }

    public function test_admin_can_access_master_item_crud_without_access_to_other_master_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ITEM-ADMIN',
        ]);

        $this->actingAs($admin)
            ->get(route('master.items.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('master.items.create'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('master.item_boms.create'))
            ->assertForbidden();

        $response = $this->actingAs($admin)->post(route('master.items.store'), [
            'code' => 'admin crud 001',
            'name' => 'Item Admin CRUD',
            'unit' => 'pcs',
            'type' => 'material',
            'item_category_id' => '',
            'default_allocation' => 'hpp',
            'supplier_ids' => [],
            'primary_supplier_id' => '',
            'active' => 1,
            'barcodes' => [],
        ]);

        $item = Item::where('code', 'ADMIN-CRUD-001')->firstOrFail();
        $response->assertRedirect(route('master.items.show', $item));

        $this->actingAs($admin)
            ->put(route('master.items.update', $item), [
                'code' => 'ADMIN-CRUD-001',
                'sku' => 'ADMIN-CRUD-001',
                'name' => 'Item Admin CRUD Updated',
                'unit' => 'pcs',
                'type' => 'material',
                'item_category_id' => '',
                'default_allocation' => 'hpp',
                'supplier_ids' => [],
                'primary_supplier_id' => '',
                'active' => 1,
                'barcodes' => [],
            ])
            ->assertRedirect(route('master.items.show', $item));

        $this->assertSame('Item Admin CRUD Updated', $item->fresh()->name);

        $this->actingAs($admin)
            ->delete(route('master.items.destroy', $item))
            ->assertRedirect(route('master.items.index'));

        $this->actingAs($admin)
            ->get(route('master.suppliers.index'))
            ->assertForbidden();
    }

    public function test_quick_supplier_fields_do_not_override_item_fields(): void
    {
        $response = $this->actingAs($this->owner)->get(route('master.items.create'));

        $response->assertOk()
            ->assertSee('data-open-category-modal', false)
            ->assertSee('id="quick-supplier-code" data-quick-field="code"', false)
            ->assertSee('id="quick-supplier-name" data-quick-field="name"', false)
            ->assertDontSee('id="quick-supplier-code" name="code"', false)
            ->assertDontSee('id="quick-supplier-name" name="name"', false);
    }

    public function test_category_can_be_created_inline_from_item_form(): void
    {
        $response = $this->actingAs($this->owner)->postJson(route('master.items.quick_categories.store'), [
            'code' => 'acc-inline',
            'name' => 'Accessories Inline',
            'kind' => 'accessory',
        ]);

        $response->assertCreated()
            ->assertJsonPath('category.code', 'ACC-INLINE')
            ->assertJsonPath('category.name', 'Accessories Inline')
            ->assertJsonPath('category.kind', 'accessory');
        $this->assertDatabaseHas('item_categories', [
            'code' => 'ACC-INLINE',
            'name' => 'Accessories Inline',
            'kind' => 'accessory',
            'active' => true,
        ]);
    }

    public function test_type_purchase_treatment_and_expense_account_can_be_created_inline(): void
    {
        $typeResponse = $this->actingAs($this->owner)->postJson(route('master.items.quick_type_options.store'), [
            'code' => 'service-material',
            'name' => 'Bahan Jasa',
            'base_type' => 'material',
        ]);
        $typeResponse->assertCreated()->assertJsonPath('option.name', 'Bahan Jasa');
        $typeId = $typeResponse->json('option.id');

        $accountResponse = $this->actingAs($this->owner)->postJson(route('master.items.quick_expense_accounts.store'), [
            'code' => '6999',
            'name' => 'Biaya Bahan Jasa',
        ]);
        $accountResponse->assertCreated()->assertJsonPath('account.name', 'Biaya Bahan Jasa');
        $accountId = $accountResponse->json('account.id');

        $treatmentResponse = $this->actingAs($this->owner)->postJson(route('master.items.quick_purchase_treatments.store'), [
            'code' => 'service-expense',
            'name' => 'Biaya Bahan Jasa',
            'allocation' => 'expense',
            'default_expense_account_id' => $accountId,
        ]);
        $treatmentResponse->assertCreated()->assertJsonPath('option.name', 'Biaya Bahan Jasa');
        $treatmentId = $treatmentResponse->json('option.id');

        $response = $this->actingAs($this->owner)->post(route('master.items.store'), [
            'code' => 'custom-option-001',
            'name' => 'Item Dengan Master Custom',
            'unit' => 'pcs',
            'type' => 'material',
            'item_type_option_id' => $typeId,
            'item_category_id' => '',
            'default_allocation' => 'hpp',
            'purchase_treatment_id' => $treatmentId,
            'default_expense_account_id' => $accountId,
            'supplier_ids' => [],
            'primary_supplier_id' => '',
            'active' => 1,
            'barcodes' => [],
        ]);

        $item = Item::where('code', 'CUSTOM-OPTION-001')->firstOrFail();
        $this->assertSame((int) $typeId, (int) $item->item_type_option_id);
        $this->assertSame('material', $item->type);
        $this->assertSame((int) $treatmentId, (int) $item->purchase_treatment_id);
        $this->assertSame('expense', $item->default_allocation);
        $this->assertSame((int) $accountId, (int) $item->default_expense_account_id);
        $response->assertRedirect(route('master.items.show', $item));
    }

    public function test_finished_good_detail_has_bom_setup_action(): void
    {
        $item = Item::create([
            'code' => 'FG-BOM-CTA-001',
            'name' => 'FG BOM CTA',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'can_buy' => true,
            'can_make' => true,
            'default_supply_source' => 'make',
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(route('master.items.show', $item))
            ->assertOk()
            ->assertSee('Atur BOM')
            ->assertSee('Tambah BOM Sekarang');
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
            'sku' => 'CUSTOM-EDIT-SKU',
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
        $this->assertSame('CUSTOM-EDIT-SKU', $item->fresh()->sku);
        $response->assertRedirect(route('master.items.show', $item));
    }

    public function test_edit_form_is_populated_with_custom_type_and_purchase_treatment(): void
    {
        $account = Account::create([
            'code' => '6998',
            'name' => 'Biaya Custom Edit',
            'type' => 'expense',
            'is_cash' => false,
            'is_active' => true,
        ]);
        $typeOption = ItemTypeOption::create([
            'code' => 'edit-material',
            'name' => 'Material Edit Custom',
            'base_type' => 'material',
            'active' => true,
            'is_system' => false,
        ]);
        $treatment = ItemPurchaseTreatment::create([
            'code' => 'edit-expense',
            'name' => 'Expense Edit Custom',
            'allocation' => 'expense',
            'default_expense_account_id' => $account->id,
            'active' => true,
            'is_system' => false,
        ]);
        $item = Item::create([
            'code' => 'EDIT-CUSTOM-001',
            'name' => 'Item Edit Custom',
            'unit' => 'pcs',
            'type' => 'material',
            'item_type_option_id' => $typeOption->id,
            'purchase_treatment_id' => $treatment->id,
            'default_allocation' => 'expense',
            'default_expense_account_id' => $account->id,
            'item_role' => 'raw_material',
            'is_stocked' => false,
            'hpp_behavior' => 'expense',
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(route('master.items.edit', $item))
            ->assertOk()
            ->assertSee('value="'.$typeOption->id.'" data-base-type="material" selected', false)
            ->assertSee('value="'.$treatment->id.'" data-allocation="expense"', false)
            ->assertSee('Material Edit Custom')
            ->assertSee('Expense Edit Custom')
            ->assertSee('Tambah tipe item')
            ->assertSee('Tambah perlakuan pembelian')
            ->assertSee('Tambah akun biaya');
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

    public function test_duplicate_item_code_is_rejected_on_create_and_edit(): void
    {
        $existing = Item::create([
            'code' => 'DUP-CODE-001',
            'name' => 'Item Sudah Ada',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $createResponse = $this->actingAs($this->owner)->from(route('master.items.create'))
            ->post(route('master.items.store'), [
                'code' => 'dup code 001',
                'name' => 'Duplikat Create',
                'unit' => 'pcs',
                'type' => 'material',
                'default_allocation' => 'hpp',
                'supplier_ids' => [],
                'primary_supplier_id' => '',
                'active' => 1,
                'barcodes' => [],
            ]);

        $createResponse->assertRedirect(route('master.items.create'))
            ->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('items', ['name' => 'Duplikat Create']);

        $other = Item::create([
            'code' => 'DUP-CODE-002',
            'name' => 'Item Yang Diedit',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $updateResponse = $this->actingAs($this->owner)->from(route('master.items.edit', $other))
            ->put(route('master.items.update', $other), [
                'code' => ' dup code 001 ',
                'name' => 'Tidak Boleh Tersimpan',
                'unit' => 'pcs',
                'type' => 'material',
                'default_allocation' => 'hpp',
                'supplier_ids' => [],
                'primary_supplier_id' => '',
                'active' => 1,
                'barcodes' => [],
            ]);

        $updateResponse->assertRedirect(route('master.items.edit', $other))
            ->assertSessionHasErrors('code');
        $this->assertSame('DUP-CODE-002', $other->fresh()->code);
        $this->assertSame('Item Yang Diedit', $other->fresh()->name);
        $this->assertSame($existing->id, Item::where('code', 'DUP-CODE-001')->value('id'));
    }
}
