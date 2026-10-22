<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MasterItemCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'CATEGORY-OWNER',
        ]);
    }

    public function test_kind_filter_can_be_opened_without_a_search_term(): void
    {
        ItemCategory::create([
            'code' => 'CAT-PRODUCT',
            'name' => 'Produk jadi',
            'kind' => 'product',
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(route('master.item_categories.index', ['kind' => 'product']))
            ->assertOk()
            ->assertSee('Item dalam kelompok')
            ->assertSee('Kategori Produk Jadi')
            ->assertSee('id="itemFormModal"', false)
            ->assertSee('id="btnAddItem"', false);
    }

    public function test_item_can_be_created_from_a_category_context_and_returns_to_that_context(): void
    {
        $category = ItemCategory::create([
            'code' => 'CAT-PRODUCT-CONTEXT',
            'name' => 'Produk untuk konteks',
            'kind' => 'product',
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->post(route('master.items.store'), [
                'return_to_category' => '1',
                'return_kind' => 'product',
                'code' => 'context item 001',
                'name' => 'Item dari konteks kategori',
                'type' => 'finished_good',
                'item_category_id' => $category->id,
                'unit' => 'pcs',
                'stock_unit' => 'pcs',
                'purchase_unit' => 'pcs',
                'purchase_conversion_factor' => '1',
                'default_allocation' => 'hpp',
                'active' => '1',
            ])
            ->assertRedirect(route('master.item_categories.index', ['kind' => 'product']));

        $this->assertDatabaseHas('items', [
            'code' => 'CONTEXT-ITEM-001',
            'item_category_id' => $category->id,
            'type' => 'finished_good',
        ]);
    }

    public function test_category_codes_are_normalized_when_created_and_updated(): void
    {
        $this->actingAs($this->owner)
            ->post(route('master.item_categories.store'), [
                'code' => '  cat-new  ',
                'name' => '  Kategori Baru  ',
                'kind' => 'material',
                'active' => '1',
            ])
            ->assertRedirect(route('master.item_categories.index'));

        $category = ItemCategory::where('code', 'CAT-NEW')->firstOrFail();
        $this->assertSame('Kategori Baru', $category->name);

        $this->actingAs($this->owner)
            ->put(route('master.item_categories.update', $category), [
                'code' => ' cat-updated ',
                'name' => ' Nama Diubah ',
                'kind' => 'support',
                'active' => '0',
            ])
            ->assertRedirect(route('master.item_categories.index'));

        $this->assertDatabaseHas('item_categories', [
            'id' => $category->id,
            'code' => 'CAT-UPDATED',
            'name' => 'Nama Diubah',
            'kind' => 'support',
            'active' => false,
        ]);
    }

    public function test_category_with_supplier_mapping_cannot_be_deleted(): void
    {
        $category = ItemCategory::create([
            'code' => 'CAT-MAPPED',
            'name' => 'Kategori dengan mapping',
            'kind' => 'material',
            'active' => true,
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-CAT-REF',
            'name' => 'Supplier referensi kategori',
            'active' => true,
        ]);
        DB::table('supplier_category_mappings')->insert([
            'item_category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->delete(route('master.item_categories.destroy', $category))
            ->assertRedirect(route('master.item_categories.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('item_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('supplier_category_mappings', ['item_category_id' => $category->id]);
    }
}
