<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\ItemCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterItemBomTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'BOM-OWNER',
        ]);
    }

    public function test_accessory_material_can_be_selected_and_saved_in_bom(): void
    {
        $category = ItemCategory::create([
            'code' => 'ACC-TEST',
            'name' => 'Accessories Test',
            'kind' => 'accessory',
            'active' => true,
        ]);

        $finishedGood = Item::create([
            'code' => 'FG-BOM-001',
            'name' => 'Produk BOM Test',
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

        $accessory = Item::create([
            'code' => 'ZIP-BOM-001',
            'name' => 'Resleting Accessories Test',
            'unit' => 'pcs',
            'type' => 'material',
            'item_category_id' => $category->id,
            'item_role' => 'production_supply',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $searchResponse = $this->actingAs($this->owner)->get(route('master.item_boms.ajax_items', [
            'type' => 'material',
            'q' => 'ZIP-BOM-001',
        ]));

        $searchResponse->assertOk()->assertJsonFragment([
            'id' => $accessory->id,
            'text' => 'ZIP-BOM-001 — Resleting Accessories Test',
            'stock_unit' => 'pcs',
            'purchase_unit' => 'pcs',
            'purchase_conversion_factor' => 1,
        ]);

        $response = $this->actingAs($this->owner)->post(route('master.item_boms.store'), [
            'item_id' => $finishedGood->id,
            'name' => 'BOM Produk Test',
            'active' => 1,
            'lines' => [[
                'material_item_id' => $accessory->id,
                'usage_stage' => ItemBomLine::STAGE_SEWING_SUPPLY,
                'qty' => '1',
                'uom' => 'pack',
                'scrap_pct' => '0',
                'is_optional' => 0,
                'sort_order' => 10,
            ]],
        ]);

        $bom = $finishedGood->boms()->firstOrFail();

        $response->assertRedirect(route('master.item_boms.edit', $bom));
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bom->id,
            'material_item_id' => $accessory->id,
            'usage_stage' => ItemBomLine::STAGE_SEWING_SUPPLY,
        ]);
    }

    public function test_bom_can_save_multiple_main_materials(): void
    {
        $finishedGood = Item::create([
            'code' => 'FG-BOM-MULTI-001',
            'name' => 'Produk BOM Multi Material Test',
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

        $fabricA = Item::create([
            'code' => 'FAB-MULTI-A',
            'name' => 'Bahan Baku A',
            'unit' => 'kg',
            'type' => 'material',
            'item_role' => 'raw_material',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $fabricB = Item::create([
            'code' => 'FAB-MULTI-B',
            'name' => 'Bahan Baku B',
            'unit' => 'kg',
            'type' => 'material',
            'item_role' => 'raw_material',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $response = $this->actingAs($this->owner)->post(route('master.item_boms.store'), [
            'item_id' => $finishedGood->id,
            'name' => 'BOM Multi Material Test',
            'active' => 1,
            'lines' => [
                [
                    'material_item_id' => $fabricA->id,
                    'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
                    'qty' => '0.7',
                    'uom' => 'kg',
                    'scrap_pct' => '0',
                    'is_optional' => 0,
                    'sort_order' => 10,
                ],
                [
                    'material_item_id' => $fabricB->id,
                    'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
                    'qty' => '0.3',
                    'uom' => 'kg',
                    'scrap_pct' => '0',
                    'is_optional' => 0,
                    'sort_order' => 20,
                ],
            ],
        ]);

        $bom = $finishedGood->boms()->firstOrFail();

        $response->assertRedirect(route('master.item_boms.edit', $bom));
        $this->assertDatabaseCount('item_bom_lines', 2);
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bom->id,
            'material_item_id' => $fabricA->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
        ]);
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bom->id,
            'material_item_id' => $fabricB->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
        ]);
    }

    public function test_bom_rejects_the_parent_item_as_a_component(): void
    {
        $finishedGood = Item::create([
            'code' => 'FG-BOM-SELF-001',
            'name' => 'Produk BOM Self Reference',
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

        $response = $this->actingAs($this->owner)
            ->from(route('master.item_boms.create'))
            ->post(route('master.item_boms.store'), [
                'item_id' => $finishedGood->id,
                'name' => 'BOM Self Reference',
                'active' => 1,
                'lines' => [[
                    'material_item_id' => $finishedGood->id,
                    'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
                    'qty' => '1',
                    'uom' => 'pcs',
                    'scrap_pct' => '0',
                    'is_optional' => 0,
                    'sort_order' => 10,
                ]],
            ]);

        $response->assertRedirect(route('master.item_boms.create'));
        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseCount('item_boms', 0);
    }

    public function test_bom_search_matches_item_sku_and_bom_name(): void
    {
        $finishedGood = Item::create([
            'code' => 'FG-BOM-SEARCH-001',
            'sku' => 'SHOP-SKU-SEARCH-001',
            'name' => 'Produk Pencarian BOM',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'default_supply_source' => 'make',
            'active' => true,
        ]);

        $bom = ItemBom::create([
            'item_id' => $finishedGood->id,
            'name' => 'Resep khusus pencarian',
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(route('master.item_boms.index', ['q' => 'SHOP-SKU-SEARCH-001']))
            ->assertOk()
            ->assertSee($finishedGood->code)
            ->assertSee($finishedGood->name);

        $this->actingAs($this->owner)
            ->get(route('master.item_boms.index', ['q' => 'khusus pencarian']))
            ->assertOk()
            ->assertSee($finishedGood->code);

        $this->actingAs($this->owner)
            ->get(route('master.item_boms.ajax_items', [
                'type' => 'finished_good',
                'q' => 'SHOP-SKU-SEARCH-001',
            ]))
            ->assertOk()
            ->assertJsonFragment(['id' => $finishedGood->id]);
    }

    public function test_bom_api_returns_filtered_results_and_pagination_metadata(): void
    {
        $category = ItemCategory::create([
            'code' => 'BOM-API-CAT',
            'name' => 'Kategori API BOM',
            'kind' => 'product',
            'active' => true,
        ]);
        $finishedGood = Item::create([
            'code' => 'FG-BOM-API-001',
            'name' => 'Produk API BOM',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
            'active' => true,
        ]);
        ItemBom::create([
            'item_id' => $finishedGood->id,
            'name' => 'BOM API Test',
            'active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson(route('master.item_boms.api', [
                'q' => 'FG-BOM-API-001',
                'item_category_id' => $category->id,
            ]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('filters.q', 'FG-BOM-API-001')
            ->assertJsonPath('filters.item_category_id', $category->id)
            ->assertJsonPath('summary.total', 1);

        $this->assertStringContainsString($finishedGood->code, (string) $response->json('html'));

        $this->actingAs($this->owner)
            ->getJson(route('master.item_boms.api', ['q' => '%']))
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_bom_index_can_filter_by_finished_good_item_category(): void
    {
        $categoryA = ItemCategory::create([
            'code' => 'BOM-FILTER-A',
            'name' => 'Kategori Filter A',
            'kind' => 'product',
            'active' => true,
        ]);
        $categoryB = ItemCategory::create([
            'code' => 'BOM-FILTER-B',
            'name' => 'Kategori Filter B',
            'kind' => 'product',
            'active' => true,
        ]);
        $finishedGoodA = Item::create([
            'code' => 'FG-BOM-FILTER-A',
            'name' => 'Produk Filter A',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $categoryA->id,
            'active' => true,
        ]);
        $finishedGoodB = Item::create([
            'code' => 'FG-BOM-FILTER-B',
            'name' => 'Produk Filter B',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $categoryB->id,
            'active' => true,
        ]);

        ItemBom::create(['item_id' => $finishedGoodA->id, 'name' => 'BOM Filter A', 'active' => true]);
        ItemBom::create(['item_id' => $finishedGoodB->id, 'name' => 'BOM Filter B', 'active' => true]);

        $this->actingAs($this->owner)
            ->get(route('master.item_boms.index', ['item_category_id' => $categoryA->id]))
            ->assertOk()
            ->assertSee($finishedGoodA->code)
            ->assertDontSee($finishedGoodB->code)
            ->assertSee($categoryA->name);
    }

    public function test_bom_index_category_filter_only_lists_finished_good_categories(): void
    {
        $finishedGoodCategory = ItemCategory::create([
            'code' => 'BOM-FILTER-FG',
            'name' => 'Kategori Finished Good',
            'kind' => 'product',
            'active' => true,
        ]);
        $materialCategory = ItemCategory::create([
            'code' => 'BOM-FILTER-MATERIAL',
            'name' => 'Kategori Material',
            'kind' => 'material',
            'active' => true,
        ]);
        $finishedGood = Item::create([
            'code' => 'FG-BOM-FILTER-ONLY',
            'name' => 'Produk Finished Good Filter',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $finishedGoodCategory->id,
            'active' => true,
        ]);
        $material = Item::create([
            'code' => 'MAT-BOM-FILTER-ONLY',
            'name' => 'Material Filter',
            'unit' => 'kg',
            'type' => 'material',
            'item_category_id' => $materialCategory->id,
            'active' => true,
        ]);

        ItemBom::create([
            'item_id' => $finishedGood->id,
            'name' => 'BOM Finished Good Filter',
            'active' => true,
        ]);
        $materialBom = ItemBom::create([
            'item_id' => $material->id,
            'name' => 'BOM Material Filter',
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(route('master.item_boms.index'))
            ->assertOk()
            ->assertSee($finishedGoodCategory->name)
            ->assertDontSee($materialCategory->name);

        $this->actingAs($this->owner)
            ->get(route('master.item_boms.index', ['item_category_id' => $materialCategory->id]))
            ->assertOk()
            ->assertDontSee($materialBom->name)
            ->assertDontSee($material->code);
    }

    public function test_bulk_update_applies_a_component_to_all_boms_in_a_category_without_duplicates(): void
    {
        $category = ItemCategory::create([
            'code' => 'BOM-BULK-CAT',
            'name' => 'Kategori Bulk BOM',
            'kind' => 'product',
            'active' => true,
        ]);
        $finishedGoodA = Item::create([
            'code' => 'FG-BOM-BULK-A',
            'name' => 'Produk Bulk A',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
            'active' => true,
        ]);
        $finishedGoodB = Item::create([
            'code' => 'FG-BOM-BULK-B',
            'name' => 'Produk Bulk B',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
            'active' => true,
        ]);
        $material = Item::create([
            'code' => 'OPP-BOM-BULK',
            'name' => 'OPP Bulk BOM',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'purchase_unit' => 'pack',
            'type' => 'material',
            'active' => true,
        ]);
        $bomA = ItemBom::create(['item_id' => $finishedGoodA->id, 'name' => 'BOM Bulk A', 'active' => true]);
        $bomB = ItemBom::create(['item_id' => $finishedGoodB->id, 'name' => 'BOM Bulk B', 'active' => true]);
        ItemBomLine::create([
            'item_bom_id' => $bomA->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
            'qty' => '1',
            'uom' => 'pcs',
            'scrap_pct' => '0',
            'is_optional' => false,
            'sort_order' => 10,
        ]);

        $this->actingAs($this->owner)
            ->post(route('master.item_boms.bulk_update'), [
                'item_category_id' => $category->id,
                'material_item_id' => $material->id,
                'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
                'qty' => '2',
                'uom' => 'pack',
                'scrap_pct' => '5',
                'is_optional' => '0',
            ])
            ->assertRedirect(route('master.item_boms.bulk_update_form'));

        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bomA->id,
            'material_item_id' => $material->id,
            'qty' => '2.00000000',
            'uom' => 'pcs',
            'scrap_pct' => '5.000',
        ]);
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bomB->id,
            'material_item_id' => $material->id,
            'qty' => '2.00000000',
            'uom' => 'pcs',
            'scrap_pct' => '5.000',
        ]);
        $this->assertSame(1, ItemBomLine::query()
            ->where('item_bom_id', $bomA->id)
            ->where('material_item_id', $material->id)
            ->count());
    }

    public function test_bulk_update_applies_to_multiple_selected_finished_good_categories(): void
    {
        $categoryA = ItemCategory::create([
            'code' => 'BOM-BULK-MULTI-A',
            'name' => 'Kategori Bulk Multi A',
            'kind' => 'product',
            'active' => true,
        ]);
        $categoryB = ItemCategory::create([
            'code' => 'BOM-BULK-MULTI-B',
            'name' => 'Kategori Bulk Multi B',
            'kind' => 'product',
            'active' => true,
        ]);
        $finishedGoodA = Item::create([
            'code' => 'FG-BOM-BULK-MULTI-A',
            'name' => 'Produk Bulk Multi A',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $categoryA->id,
            'active' => true,
        ]);
        $finishedGoodB = Item::create([
            'code' => 'FG-BOM-BULK-MULTI-B',
            'name' => 'Produk Bulk Multi B',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $categoryB->id,
            'active' => true,
        ]);
        $component = Item::create([
            'code' => 'OPP-BOM-BULK-MULTI',
            'name' => 'Komponen Bulk Multi',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);
        $bomA = ItemBom::create(['item_id' => $finishedGoodA->id, 'name' => 'BOM Multi A', 'active' => true]);
        $bomB = ItemBom::create(['item_id' => $finishedGoodB->id, 'name' => 'BOM Multi B', 'active' => true]);

        $this->actingAs($this->owner)
            ->post(route('master.item_boms.bulk_update'), [
                'all_categories' => '0',
                'item_category_ids' => [$categoryA->id, $categoryB->id],
                'material_item_id' => $component->id,
                'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
                'qty' => '1',
                'scrap_pct' => '0',
                'is_optional' => '0',
            ])
            ->assertRedirect(route('master.item_boms.bulk_update_form'));

        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bomA->id,
            'material_item_id' => $component->id,
        ]);
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bomB->id,
            'material_item_id' => $component->id,
        ]);
    }

    public function test_bulk_update_form_exposes_multiple_finished_good_category_choices(): void
    {
        $finishedGoodCategory = ItemCategory::create([
            'code' => 'BOM-BULK-UI-FG',
            'name' => 'Kategori UI Finished Good',
            'kind' => 'product',
            'active' => true,
        ]);
        $materialCategory = ItemCategory::create([
            'code' => 'BOM-BULK-UI-MAT',
            'name' => 'Kategori UI Material',
            'kind' => 'material',
            'active' => true,
        ]);
        $finishedGood = Item::create([
            'code' => 'FG-BOM-BULK-UI',
            'name' => 'Produk UI Finished Good',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $finishedGoodCategory->id,
            'active' => true,
        ]);
        ItemBom::create([
            'item_id' => $finishedGood->id,
            'name' => 'BOM UI Finished Good',
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(route('master.item_boms.bulk_update_form'))
            ->assertOk()
            ->assertSee('<select id="bulk-category" class="bom-bulk-category-select" name="item_category_ids[]" multiple', false)
            ->assertSee('value="all"', false)
            ->assertSee($finishedGoodCategory->name)
            ->assertDontSee($materialCategory->name);
    }

    public function test_bulk_update_all_targets_finished_good_boms_only(): void
    {
        $finishedGoodCategory = ItemCategory::create([
            'code' => 'BOM-BULK-ALL-FG',
            'name' => 'Kategori Semua Finished Good',
            'kind' => 'product',
            'active' => true,
        ]);
        $materialCategory = ItemCategory::create([
            'code' => 'BOM-BULK-ALL-MAT',
            'name' => 'Kategori Semua Material',
            'kind' => 'material',
            'active' => true,
        ]);
        $finishedGood = Item::create([
            'code' => 'FG-BOM-BULK-ALL',
            'name' => 'Produk Semua Finished Good',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $finishedGoodCategory->id,
            'active' => true,
        ]);
        $materialParent = Item::create([
            'code' => 'MAT-BOM-BULK-ALL',
            'name' => 'Material Parent Tidak Ditargetkan',
            'unit' => 'kg',
            'type' => 'material',
            'item_category_id' => $materialCategory->id,
            'active' => true,
        ]);
        $component = Item::create([
            'code' => 'OPP-BOM-BULK-ALL',
            'name' => 'Komponen Bulk Semua',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);

        $finishedGoodBom = ItemBom::create([
            'item_id' => $finishedGood->id,
            'name' => 'BOM Finished Good Semua',
            'active' => true,
        ]);
        $materialBom = ItemBom::create([
            'item_id' => $materialParent->id,
            'name' => 'BOM Material Tidak Ditargetkan',
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->post(route('master.item_boms.bulk_update'), [
                'item_category_id' => '',
                'material_item_id' => $component->id,
                'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
                'qty' => '1',
                'scrap_pct' => '0',
                'is_optional' => '0',
            ])
            ->assertRedirect(route('master.item_boms.bulk_update_form'));

        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $finishedGoodBom->id,
            'material_item_id' => $component->id,
            'uom' => 'pcs',
        ]);
        $this->assertDatabaseMissing('item_bom_lines', [
            'item_bom_id' => $materialBom->id,
            'material_item_id' => $component->id,
        ]);
    }

    public function test_bulk_update_skips_inactive_finished_good_boms(): void
    {
        $category = ItemCategory::create([
            'code' => 'BOM-BULK-ACTIVE-ONLY',
            'name' => 'Kategori BOM Aktif Saja',
            'kind' => 'product',
            'active' => true,
        ]);
        $activeFinishedGood = Item::create([
            'code' => 'FG-BOM-ACTIVE-ONLY',
            'name' => 'Produk BOM Aktif',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
            'active' => true,
        ]);
        $inactiveFinishedGood = Item::create([
            'code' => 'FG-BOM-INACTIVE-ONLY',
            'name' => 'Produk BOM Inactive',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
            'active' => true,
        ]);
        $component = Item::create([
            'code' => 'RIB-BOM-ACTIVE-ONLY',
            'name' => 'Komponen BOM Aktif Saja',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);

        $activeBom = ItemBom::create([
            'item_id' => $activeFinishedGood->id,
            'name' => 'BOM Aktif',
            'active' => true,
        ]);
        $inactiveBom = ItemBom::create([
            'item_id' => $inactiveFinishedGood->id,
            'name' => 'BOM Inactive',
            'active' => false,
        ]);

        $this->actingAs($this->owner)
            ->post(route('master.item_boms.bulk_update'), [
                'item_category_id' => $category->id,
                'material_item_id' => $component->id,
                'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
                'qty' => '1',
                'scrap_pct' => '0',
                'is_optional' => '0',
            ])
            ->assertRedirect(route('master.item_boms.bulk_update_form'));

        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $activeBom->id,
            'material_item_id' => $component->id,
        ]);
        $this->assertDatabaseMissing('item_bom_lines', [
            'item_bom_id' => $inactiveBom->id,
            'material_item_id' => $component->id,
        ]);
    }

    public function test_bulk_update_keeps_legacy_duplicate_component_lines_in_sync(): void
    {
        $finishedGood = Item::create([
            'code' => 'FG-BOM-DUPLICATE-LEGACY',
            'name' => 'Produk BOM Duplikat Legacy',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $component = Item::create([
            'code' => 'LABEL-BOM-DUPLICATE-LEGACY',
            'name' => 'Label Duplikat Legacy',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);
        $bom = ItemBom::create([
            'item_id' => $finishedGood->id,
            'name' => 'BOM Duplikat Legacy',
            'active' => true,
        ]);
        foreach ([10, 20] as $sortOrder) {
            ItemBomLine::create([
                'item_bom_id' => $bom->id,
                'material_item_id' => $component->id,
                'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
                'qty' => '1',
                'uom' => 'pcs',
                'scrap_pct' => '0',
                'is_optional' => false,
                'sort_order' => $sortOrder,
            ]);
        }

        $this->actingAs($this->owner)
            ->post(route('master.item_boms.bulk_update'), [
                'item_category_id' => '',
                'material_item_id' => $component->id,
                'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
                'qty' => '2',
                'scrap_pct' => '7.5',
                'is_optional' => '1',
            ])
            ->assertRedirect(route('master.item_boms.bulk_update_form'));

        $this->assertSame(2, ItemBomLine::query()
            ->where('item_bom_id', $bom->id)
            ->where('material_item_id', $component->id)
            ->where('qty', '2.00')
            ->where('scrap_pct', '7.50')
            ->where('is_optional', true)
            ->count());
    }

    public function test_bom_line_unit_always_follows_component_master_stock_unit(): void
    {
        $finishedGood = Item::create([
            'code' => 'FG-BOM-UOM-001',
            'name' => 'Produk UOM BOM Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'can_make' => true,
            'default_supply_source' => 'make',
            'active' => true,
        ]);
        $material = Item::create([
            'code' => 'MAT-BOM-UOM-001',
            'name' => 'Material UOM BOM Test',
            'unit' => 'pcs',
            'stock_unit' => 'pcs',
            'purchase_unit' => 'pack',
            'type' => 'material',
            'active' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('master.item_boms.store'), [
                'item_id' => $finishedGood->id,
                'name' => 'BOM UOM Test',
                'active' => 1,
                'lines' => [[
                    'material_item_id' => $material->id,
                    'usage_stage' => ItemBomLine::STAGE_PACKING_SUPPLY,
                    'qty' => '1',
                    'uom' => 'pack',
                    'scrap_pct' => '0',
                    'is_optional' => 0,
                    'sort_order' => 10,
                ]],
            ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('item_bom_lines', [
            'material_item_id' => $material->id,
            'uom' => 'pcs',
        ]);
    }

    public function test_existing_bom_can_be_edited_with_a_small_quantity(): void
    {
        $finishedGood = Item::create([
            'code' => 'FG-BOM-EDIT-001',
            'name' => 'Produk Edit BOM',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'can_make' => true,
            'default_supply_source' => 'make',
            'active' => true,
        ]);
        $material = Item::create([
            'code' => 'FAB-BOM-EDIT-001',
            'name' => 'Bahan Edit BOM',
            'unit' => 'kg',
            'type' => 'material',
            'item_role' => 'raw_material',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'default_allocation' => 'hpp',
            'active' => true,
        ]);

        $bom = ItemBom::create([
            'item_id' => $finishedGood->id,
            'name' => 'BOM sebelum edit',
            'active' => true,
        ]);
        ItemBomLine::create([
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
            'qty' => '0.0005',
            'uom' => 'kg',
            'scrap_pct' => '0',
            'is_optional' => false,
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($this->owner)
            ->put(route('master.item_boms.update', $bom), [
                'name' => 'BOM setelah edit',
                'active' => 1,
                'lines' => [[
                    'material_item_id' => $material->id,
                    'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
                    'qty' => '0.0005',
                    'uom' => 'kg',
                    'scrap_pct' => '15',
                    'is_optional' => 0,
                    'sort_order' => 10,
                ]],
            ]);

        $response->assertRedirect(route('master.item_boms.edit', $bom));
        $this->assertSame('BOM setelah edit', $bom->fresh()->name);
        $this->assertDatabaseHas('item_bom_lines', [
            'item_bom_id' => $bom->id,
            'material_item_id' => $material->id,
            'qty' => '0.0005',
            'scrap_pct' => '15.000',
        ]);
    }

    public function test_new_bom_line_uses_zero_percent_waste_by_default(): void
    {
        $this->actingAs($this->owner)
            ->get(route('master.item_boms.create'))
            ->assertOk()
            ->assertSee('Waste (%)')
            ->assertSee('Waste default 0%')
            ->assertSee('name="lines[0][scrap_pct]"', false)
            ->assertSee('value="0"', false)
            ->assertSee('data-max-decimals="3"', false)
            ->assertSee('placeholder="0,000"', false)
            ->assertSee('toFixed(3)', false);
    }

    public function test_bom_form_does_not_reorder_rows_when_group_changes(): void
    {
        $this->actingAs($this->owner)
            ->get(route('master.item_boms.create'))
            ->assertOk()
            ->assertDontSee('function reorderLines', false)
            ->assertDontSee('stageRank(', false);
    }
}
