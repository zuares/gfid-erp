<?php

namespace Tests\Feature\Purchasing;

use App\Models\Item;
use App\Models\Supplier;
use App\Models\SupplierItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierItemMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_bulk_create_supplier_item_mappings(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'BULK-MAPPING-OWNER']);
        $supplier = Supplier::create([
            'code' => 'BULK-SUPPLIER',
            'name' => 'Bulk Supplier',
            'type' => 'supplier',
            'active' => true,
        ]);
        $items = collect(['BULK-ITEM-1', 'BULK-ITEM-2'])->map(fn (string $code) => Item::create([
            'code' => $code,
            'name' => "Item {$code}",
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]));

        $response = $this->actingAs($owner)->post(route('purchasing.supplier_items.bulk_store'), [
            'items' => $items->map(fn (Item $item) => ['item_id' => $item->id])->all(),
            'supplier_id' => $supplier->id,
            'minimum_order_qty' => '10',
            'lead_time_days' => '7',
            'last_price' => '1250',
            'active' => '1',
            'is_primary' => '1',
        ]);

        $response->assertRedirect();
        $items->each(function (Item $item) use ($supplier): void {
            $this->assertDatabaseHas('supplier_items', [
                'item_id' => $item->id,
                'supplier_id' => $supplier->id,
                'is_primary' => true,
                'minimum_order_qty' => 10,
                'lead_time_days' => 7,
                'last_price' => 1250,
                'active' => true,
            ]);
        });
    }

    public function test_owner_can_bulk_update_selected_mappings(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'BULK-UPDATE-OWNER']);
        $supplier = Supplier::create([
            'code' => 'UPDATE-SUPPLIER',
            'name' => 'Update Supplier',
            'type' => 'supplier',
            'active' => true,
        ]);
        $items = collect(['UPDATE-ITEM-1', 'UPDATE-ITEM-2'])->map(fn (string $code) => Item::create([
            'code' => $code,
            'name' => "Item {$code}",
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]));
        $mappings = $items->map(fn (Item $item) => SupplierItem::create([
            'item_id' => $item->id,
            'supplier_id' => $supplier->id,
            'last_price' => 100,
            'minimum_order_qty' => 1,
            'lead_time_days' => 2,
            'active' => true,
        ]));

        $response = $this->actingAs($owner)->patch(route('purchasing.supplier_items.bulk_update'), [
            'mapping_ids' => $mappings->pluck('id')->all(),
            'bulk_minimum_order_qty' => '25',
            'bulk_lead_time_days' => '14',
            'bulk_active' => '0',
            'bulk_last_price' => '900',
        ]);

        $response->assertRedirect();
        $mappings->each(function (SupplierItem $mapping): void {
            $this->assertDatabaseHas('supplier_items', [
                'id' => $mapping->id,
                'minimum_order_qty' => 25,
                'lead_time_days' => 14,
                'active' => false,
                'last_price' => 900,
            ]);
        });
    }
}
