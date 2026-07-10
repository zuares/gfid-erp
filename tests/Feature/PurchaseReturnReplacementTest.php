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
use App\Models\Account;
use App\Services\Accounting\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnReplacementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warehouse;
    protected $item;
    protected $grn;
    protected $grnLine;
    protected $stock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['employee_code' => 'DEV', 'role' => 'owner']);
        $this->actingAs($this->user);

        $this->warehouse = Warehouse::create(['name' => 'Test WH', 'code' => 'TWH']);
        $supplier = Supplier::create(['name' => 'Test Supp', 'code' => 'TS']);

        $this->item = Item::create([
            'name' => 'Test Item', 
            'code' => 'ITM1', 
            'role' => 'inventory',
            'type' => 'goods',
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

        // Setup COA
        Account::create(['code' => JournalService::CODE_SUPPLIER_CLAIM, 'name' => 'Supplier Claim', 'type' => 'asset', 'normal_balance' => 'debit']);
        Account::create(['code' => JournalService::CODE_INV_RAW, 'name' => 'Inventory', 'type' => 'asset', 'normal_balance' => 'debit']);
        Account::create(['code' => JournalService::CODE_AP, 'name' => 'AP', 'type' => 'liability', 'normal_balance' => 'credit']);
    }

    public function test_draft_replacement_updates_expected_qty()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        $line = $ret->lines()->first();

        $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'replacement',
            'lines' => [
                [
                    'id' => $line->id,
                    'purchase_receipt_line_id' => $this->grnLine->id,
                    'qty' => '3',
                    'reason_code' => 'defect',
                ]
            ]
        ])->assertSessionHasNoErrors();

        $ret->refresh();
        $line->refresh();

        $this->assertEquals('replacement', $ret->resolution_type);
        $this->assertEquals(3, $line->replacement_qty_expected);
        $this->assertEquals($this->item->id, $line->replacement_item_id);
    }

    public function test_post_replacement_creates_stock_out_and_sets_pending()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        $line = $ret->lines()->first();

        $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'replacement',
            'lines' => [
                [
                    'id' => $line->id,
                    'purchase_receipt_line_id' => $this->grnLine->id,
                    'qty' => '3',
                    'reason_code' => 'defect',
                ]
            ]
        ]);

        $this->post(route('purchasing.purchase_returns.post', $ret->id))->assertSessionHasNoErrors();

        $ret->refresh();
        $this->assertEquals('posted', $ret->status);
        $this->assertEquals('pending', $ret->replacement_status);

        $this->stock->refresh();
        $this->assertEquals(7, $this->stock->qty);
        $this->assertEquals(0, $this->stock->allocated_qty);
    }

    public function test_receive_replacement_stock_in_and_partial_status()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        $line = $ret->lines()->first();

        $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'replacement',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '3', 'reason_code' => 'defect']
            ]
        ]);

        $this->post(route('purchasing.purchase_returns.post', $ret->id));

        $ret->refresh();

        $response = $this->post(route('purchasing.purchase_returns.receive_replacement', $ret->id), [
            'warehouse_id' => $this->warehouse->id,
            'received_at' => now()->toDateString(),
            'lines' => [
                ['id' => $line->id, 'qty' => 1]
            ]
        ]);
        
        $response->assertSessionHasNoErrors();

        $ret->refresh();
        $line->refresh();
        $this->stock->refresh();

        $this->assertEquals('partial', $ret->replacement_status);
        $this->assertEquals('posted', $ret->status);
        $this->assertEquals(1, $line->replacement_qty_received);
        $this->assertEquals(8, $this->stock->qty);
    }

    public function test_receive_replacement_complete_status()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        $line = $ret->lines()->first();

        $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'replacement',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '3', 'reason_code' => 'defect']
            ]
        ]);

        $this->post(route('purchasing.purchase_returns.post', $ret->id));

        $response = $this->post(route('purchasing.purchase_returns.receive_replacement', $ret->id), [
            'warehouse_id' => $this->warehouse->id,
            'received_at' => now()->toDateString(),
            'lines' => [
                ['id' => $line->id, 'qty' => 3]
            ]
        ]);
        
        $response->assertSessionHasNoErrors();

        $ret->refresh();
        $line->refresh();
        $this->stock->refresh();

        $this->assertEquals('received', $ret->replacement_status);
        $this->assertEquals('posted', $ret->status); // Status must remain posted MVP
        $this->assertEquals(3, $line->replacement_qty_received);
        $this->assertEquals(10, $this->stock->qty);
    }

    public function test_replacement_pending_can_be_voided()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        $line = $ret->lines()->first();

        $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'replacement',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '3', 'reason_code' => 'defect']
            ]
        ]);

        $this->post(route('purchasing.purchase_returns.post', $ret->id));
        
        $this->post(route('purchasing.purchase_returns.void', $ret->id))->assertSessionHasNoErrors();
        $ret->refresh();
        $this->assertNotNull($ret->voided_at);
    }

    public function test_replacement_partial_cannot_be_voided()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        $line = $ret->lines()->first();

        $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'replacement',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '3', 'reason_code' => 'defect']
            ]
        ]);

        $this->post(route('purchasing.purchase_returns.post', $ret->id));

        $this->post(route('purchasing.purchase_returns.receive_replacement', $ret->id), [
            'warehouse_id' => $this->warehouse->id,
            'received_at' => now()->toDateString(),
            'lines' => [['id' => $line->id, 'qty' => 1]]
        ]);
        
        $response = $this->post(route('purchasing.purchase_returns.void', $ret->id));
        $response->assertSessionHasErrors(['return' => 'Retur tidak dapat dibatalkan karena barang pengganti sudah pernah diterima. Gunakan proses reversal penerimaan barang pengganti.']);
        $ret->refresh();
        $this->assertNull($ret->voided_at);
    }

    public function test_replacement_received_cannot_be_voided()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        $line = $ret->lines()->first();

        $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'replacement',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '3', 'reason_code' => 'defect']
            ]
        ]);

        $this->post(route('purchasing.purchase_returns.post', $ret->id));

        $this->post(route('purchasing.purchase_returns.receive_replacement', $ret->id), [
            'warehouse_id' => $this->warehouse->id,
            'received_at' => now()->toDateString(),
            'lines' => [['id' => $line->id, 'qty' => 3]]
        ]);
        
        $response = $this->post(route('purchasing.purchase_returns.void', $ret->id));
        $response->assertSessionHasErrors(['return' => 'Retur tidak dapat dibatalkan karena barang pengganti sudah pernah diterima. Gunakan proses reversal penerimaan barang pengganti.']);
        $ret->refresh();
        $this->assertNull($ret->voided_at);
    }

    public function test_replacement_pending_but_has_qty_cannot_be_voided()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        $line = $ret->lines()->first();

        $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'replacement',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '3', 'reason_code' => 'defect']
            ]
        ]);

        $this->post(route('purchasing.purchase_returns.post', $ret->id));
        
        // Simulasikan data drift
        $line->replacement_qty_received = 1;
        $line->save();

        $response = $this->post(route('purchasing.purchase_returns.void', $ret->id));
        $response->assertSessionHasErrors(['return' => 'Retur tidak dapat dibatalkan karena barang pengganti sudah pernah diterima. Gunakan proses reversal penerimaan barang pengganti.']);
        $ret->refresh();
        $this->assertNull($ret->voided_at);
    }

    public function test_refund_posted_can_be_voided()
    {
        $this->post(route('purchasing.grn.returns.create', $this->grn->id));
        $ret = PurchaseReturn::first();
        $line = $ret->lines()->first();

        $this->put(route('purchasing.purchase_returns.update', $ret->id), [
            'date' => now()->toDateString(),
            'resolution_type' => 'refund',
            'lines' => [
                ['id' => $line->id, 'purchase_receipt_line_id' => $this->grnLine->id, 'qty' => '3', 'reason_code' => 'defect']
            ]
        ]);

        $this->post(route('purchasing.purchase_returns.post', $ret->id));
        
        $this->post(route('purchasing.purchase_returns.void', $ret->id))->assertSessionHasNoErrors();
        $ret->refresh();
        $this->assertNotNull($ret->voided_at);
    }
}
