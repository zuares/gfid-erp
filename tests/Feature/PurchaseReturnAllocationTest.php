<?php

namespace Tests\Feature;

use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;
use App\Services\Inventory\InventoryService;

class PurchaseReturnAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warehouse;
    protected $item;
    protected $expenseItem;
    protected $grn;
    protected $grnLine;
    protected $grnLineExpense;
    protected $stock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['employee_code' => 'DEV', 'role' => 'admin']);
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create(['name' => 'Test WH', 'code' => 'TWH']);
        $supplier = Supplier::create(['name' => 'Test Supp', 'code' => 'TS']);

        $this->item = Item::create([
            'name' => 'Test Item', 
            'code' => 'ITM1', 
            'role' => 'inventory',
            'type' => 'goods',
        ]);
        
        $this->expenseItem = Item::create([
            'name' => 'Test Expense', 
            'code' => 'EXP1', 
            'role' => 'expense',
            'type' => 'service',
            'default_allocation' => 'expense',
        ]);

        $this->stock = InventoryStock::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'qty' => 10,
            'allocated_qty' => 0,
        ]);

        $this->grn = PurchaseReceipt::create([
            'warehouse_id' => $this->warehouse->id,
            'supplier_id' => $supplier->id,
            'code' => 'GRN-TEST-1',
            'status' => 'posted',
            'date' => now()->toDateString(),
        ]);

        $this->grnLine = PurchaseReceiptLine::create([
            'purchase_receipt_id' => $this->grn->id,
            'item_id' => $this->item->id,
            'qty_received' => 10,
            'unit_price' => 100,
        ]);

        $this->grnLineExpense = PurchaseReceiptLine::create([
            'purchase_receipt_id' => $this->grn->id,
            'item_id' => $this->expenseItem->id,
            'qty_received' => 5,
            'unit_price' => 50,
        ]);
    }

    public function test_create_from_grn_sets_qty_0_and_does_not_reserve()
    {
        $response = $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $response->assertRedirect();

        $ret = PurchaseReturn::first();
        $this->assertNotNull($ret);
        
        $this->assertEquals(2, $ret->lines()->count());
        $this->assertEquals(0, $ret->lines->first()->qty);
        $this->assertEquals(0, $ret->lines->first()->allocated_qty);

        $this->stock->refresh();
        $this->assertEquals(0, $this->stock->allocated_qty);
    }

    public function test_update_qty_allocates_stock()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        
        $line = $ret->lines()->where('item_id', $this->item->id)->first();
        $response = $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'refund',
            'lines' => [
                [
                    'id' => $line->id,
                    'purchase_receipt_line_id' => $this->grnLine->id,
                    'qty' => '5',
                    'reason_code' => 'defect',
                ]
            ]
        ]);
        $response->assertSessionHasNoErrors();

        $this->stock->refresh();
        $this->assertEquals(5, $this->stock->allocated_qty);
        
        $line = $ret->lines()->where('item_id', $this->item->id)->first();
        $this->assertEquals(5, $line->qty);
        $this->assertEquals(5, $line->allocated_qty);
    }

    public function test_update_qty_down_releases_stock()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        
        $line = $ret->lines()->where('item_id', $this->item->id)->first();
        $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'refund',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '5', 'reason_code' => 'defect']
            ]
        ]);

        $response = $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'refund',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '3', 'reason_code' => 'defect']
            ]
        ]);
        $response->assertSessionHasNoErrors();

        $this->stock->refresh();
        $this->assertEquals(3, $this->stock->allocated_qty);
    }

    public function test_line_qty_0_not_posted()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        
        $line = $ret->lines()->where('item_id', $this->item->id)->first();
        // Update line to 0 qty
        $response = $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'refund',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '0']
            ]
        ]);
        $response->assertSessionHasNoErrors();
        
        $this->assertEquals(1, $ret->lines()->count()); // The other expense line is still there
    }

    public function test_hard_delete_with_allocation_rejected()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        
        $line = $ret->lines()->where('item_id', $this->item->id)->first();
        $response = $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'refund',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '5', 'reason_code' => 'defect']
            ]
        ]);
        $response->assertSessionHasNoErrors();

        $line = $line->fresh();
        
        $this->expectException(ValidationException::class);
        
        $line->delete();
    }

    public function test_cancel_releases_allocation()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        
        $line = $ret->lines()->where('item_id', $this->item->id)->first();
        $response = $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'refund',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '5', 'reason_code' => 'defect']
            ]
        ]);
        $response->assertSessionHasNoErrors();

        $this->post(route('purchasing.purchase_returns.cancel', $ret->id));

        $this->stock->refresh();
        $this->assertEquals(0, $this->stock->allocated_qty);
        
        $ret->refresh();
        $this->assertEquals('cancelled', $ret->status);
    }

    public function test_post_requires_allocated_qty_equals_qty()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        
        $line = $ret->lines()->where('item_id', $this->item->id)->first();
        $response = $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'refund',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '5', 'reason_code' => 'defect']
            ]
        ]);
        $response->assertSessionHasNoErrors();

        // Manipulate DB to simulate drift
        $line = $ret->lines()->first();
        $line->allocated_qty = 2;
        $line->saveQuietly(); // Skip observers

        $response = $this->post(route('purchasing.purchase_returns.post', $ret->id));
        $response->assertSessionHasErrors(['lines']);
    }

    public function test_expense_line_does_not_allocate()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        
        $line = $ret->lines()->where('item_id', $this->expenseItem->id)->first();
        $response = $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'refund',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLineExpense->id, 'qty' => '3', 'reason_code' => 'defect']
            ]
        ]);
        $response->assertSessionHasNoErrors();

        $line = $ret->lines()->where('item_id', $this->expenseItem->id)->first();
        $this->assertEquals(0, $line->allocated_qty);
    }
}
