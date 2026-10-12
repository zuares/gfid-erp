<?php

namespace Tests\Feature\Purchasing;

use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_sent_to_supplier_allocation_after_creating_a_purchase_request(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-PR-FLOW',
        ]);
        $item = Item::create([
            'code' => 'PR-FLOW-ITEM',
            'name' => 'PR Flow Item',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('purchasing.purchase_requests.store'), [
            'date' => '2026-10-12',
            'lines' => [
                ['item_id' => $item->id, 'qty' => 2],
            ],
        ]);

        $purchaseRequest = PurchaseRequest::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route(
            'purchasing.purchase_requests.allocate_suppliers',
            $purchaseRequest,
        ));
        $this->assertSame('draft', $purchaseRequest->status);
        $this->assertTrue($purchaseRequest->isConvertible());

        $this->get(route('purchasing.purchase_requests.show', $purchaseRequest))
            ->assertOk()
            ->assertSee('Pilih Supplier')
            ->assertSee('Barang')
            ->assertDontSee('Menunggu persetujuan');
    }

    public function test_purchase_request_add_row_reinitializes_item_autosuggest(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-PR-AUTOSUGGEST',
        ]);

        $this->actingAs($admin)
            ->get(route('purchasing.purchase_requests.create'))
            ->assertOk()
            ->assertSee("window.initItemSuggestInputs(clone)", false)
            ->assertDontSee("window.initItemSuggest(clone)", false);
    }

    public function test_purchase_request_estimated_price_prefers_latest_po_then_latest_posted_grn(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-PR-PRICE',
        ]);
        $poItem = Item::create([
            'code' => 'PR-PRICE-PO',
            'name' => 'PR Price PO Item',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
            'last_purchase_price' => 100,
        ]);
        $grnItem = Item::create([
            'code' => 'PR-PRICE-GRN',
            'name' => 'PR Price GRN Item',
            'unit' => 'pcs',
            'type' => 'material',
            'active' => true,
            'last_purchase_price' => 100,
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PR-PRICE',
            'name' => 'PR Price Supplier',
            'active' => true,
        ]);

        $po = PurchaseOrder::create([
            'code' => 'PO-PR-PRICE',
            'date' => '2026-10-10',
            'supplier_id' => $supplier->id,
            'status' => 'approved',
        ]);
        PurchaseOrderLine::create([
            'purchase_order_id' => $po->id,
            'item_id' => $poItem->id,
            'qty' => 1,
            'unit_price' => 250,
            'line_total' => 250,
        ]);

        $grn = PurchaseReceipt::create([
            'code' => 'GRN-PR-PRICE',
            'date' => '2026-10-11',
            'supplier_id' => $supplier->id,
            'status' => 'posted',
        ]);
        PurchaseReceiptLine::create([
            'purchase_receipt_id' => $grn->id,
            'item_id' => $grnItem->id,
            'qty_received' => 1,
            'unit_price' => 350,
            'line_total' => 350,
        ]);

        $response = $this->actingAs($admin)->post(route('purchasing.purchase_requests.store'), [
            'date' => '2026-10-12',
            'lines' => [
                ['item_id' => $poItem->id, 'qty' => 1],
                ['item_id' => $grnItem->id, 'qty' => 1],
            ],
        ]);

        $purchaseRequest = PurchaseRequest::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route(
            'purchasing.purchase_requests.allocate_suppliers',
            $purchaseRequest,
        ));
        $this->assertSame(250.0, (float) $purchaseRequest->lines()->where('item_id', $poItem->id)->value('unit_price'));
        $this->assertSame(350.0, (float) $purchaseRequest->lines()->where('item_id', $grnItem->id)->value('unit_price'));
    }
}
