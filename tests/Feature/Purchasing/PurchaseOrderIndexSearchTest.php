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
            ->assertSee('Cari PO / kode barang...', false);
    }
}
