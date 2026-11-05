<?php

namespace Tests\Feature\Purchasing;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderIndexSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_index_can_search_by_item_code(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'PO-SEARCH-ADMIN',
        ]);
        $supplier = Supplier::create([
            'code' => 'PO-SEARCH-SUP',
            'name' => 'Supplier Search PO',
        ]);
        $matchingItem = Item::create([
            'code' => 'TTB-RJK-XXL',
            'name' => 'Reject Defact Tracktop XXL Garis 3',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);
        $otherItem = Item::create([
            'code' => 'OTHER-ITEM-001',
            'name' => 'Barang lain',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);

        $matchingOrder = PurchaseOrder::create([
            'code' => 'PO-SEARCH-MATCH',
            'date' => '2026-11-05',
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'subtotal' => 100000,
            'grand_total' => 100000,
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $matchingOrder->id,
            'item_id' => $matchingItem->id,
            'qty' => 1,
            'unit_price' => 100000,
            'line_total' => 100000,
        ]);

        $otherOrder = PurchaseOrder::create([
            'code' => 'PO-SEARCH-OTHER',
            'date' => '2026-11-05',
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'subtotal' => 50000,
            'grand_total' => 50000,
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $otherOrder->id,
            'item_id' => $otherItem->id,
            'qty' => 1,
            'unit_price' => 50000,
            'line_total' => 50000,
        ]);

        $this->actingAs($admin)
            ->get(route('purchasing.purchase_orders.index', ['q' => 'TTB-RJK-XXL']))
            ->assertOk()
            ->assertSee('PO-SEARCH-MATCH')
            ->assertDontSee('PO-SEARCH-OTHER')
            ->assertSee('Cari PO / barang (spasi atau koma)...', false);
    }

    public function test_purchase_order_index_can_filter_by_multiple_item_codes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'PO-BULK-SEARCH-ADMIN',
        ]);
        $supplier = Supplier::create([
            'code' => 'PO-BULK-SEARCH-SUP',
            'name' => 'Supplier Bulk Search PO',
        ]);
        $firstItem = Item::create([
            'code' => 'BULK-ITEM-001',
            'name' => 'Bulk item pertama',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);
        $secondItem = Item::create([
            'code' => 'BULK-ITEM-002',
            'name' => 'Bulk item kedua',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);
        $otherItem = Item::create([
            'code' => 'BULK-ITEM-OTHER',
            'name' => 'Bulk item lain',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);

        $createOrder = function (string $code, Item $item) use ($supplier): void {
            $order = PurchaseOrder::create([
                'code' => $code,
                'date' => '2026-11-05',
                'supplier_id' => $supplier->id,
                'status' => 'draft',
                'subtotal' => 100000,
                'grand_total' => 100000,
            ]);

            PurchaseOrderLine::create([
                'purchase_order_id' => $order->id,
                'item_id' => $item->id,
                'qty' => 1,
                'unit_price' => 100000,
                'line_total' => 100000,
            ]);
        };

        $createOrder('PO-BULK-FIRST', $firstItem);
        $createOrder('PO-BULK-SECOND', $secondItem);
        $createOrder('PO-BULK-OTHER', $otherItem);

        $this->actingAs($admin)
            ->get(route('purchasing.purchase_orders.index', [
                'codes' => [$firstItem->code, $secondItem->code],
            ]))
            ->assertOk()
            ->assertSee('PO-BULK-FIRST')
            ->assertSee('PO-BULK-SECOND')
            ->assertDontSee('PO-BULK-OTHER');

        $this->actingAs($admin)
            ->get(route('purchasing.purchase_orders.index', [
                'q' => 'bulk-item-001 bulk-item-002',
            ]))
            ->assertOk()
            ->assertSee('PO-BULK-FIRST')
            ->assertSee('PO-BULK-SECOND')
            ->assertDontSee('PO-BULK-OTHER');
    }
}
