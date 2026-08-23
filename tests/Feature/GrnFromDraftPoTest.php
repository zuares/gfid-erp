<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Journal;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Purchasing\GoodsReceiptService;
use App\Services\Purchasing\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * UAT: Flow baru Purchase Order (draft) → GRN → PO Locked.
 *
 * Menguji: penguncian PO, keamanan harga (server-side), validasi relasi
 * header/line PO, over-receipt, dan konsistensi stok/jurnal/AP saat posting.
 */
class GrnFromDraftPoTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $admin;
    protected Warehouse $warehouse;
    protected Supplier $supplier;
    protected Supplier $supplierOther;
    protected Item $item;
    protected PurchaseOrderService $poService;
    protected GoodsReceiptService $grnService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWN']);
        $this->admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM']);

        // firstOrCreate: kode master (warehouse/supplier/COA) mungkin sudah
        // di-seed oleh migration — jangan bikin duplikat (UNIQUE constraint).
        $this->warehouse = Warehouse::firstOrCreate(['code' => 'GRNTWH'], ['name' => 'GRN Test WH']);
        $this->supplier = Supplier::firstOrCreate(['code' => 'GRNSA'], ['name' => 'GRN Supplier A']);
        $this->supplierOther = Supplier::firstOrCreate(['code' => 'GRNSB'], ['name' => 'GRN Supplier B']);

        $this->item = Item::firstOrCreate(
            ['code' => 'GRNKAIN1'],
            [
                'name' => 'Kain Katun', 'type' => 'material', 'item_role' => 'raw_material',
                'default_allocation' => 'hpp', 'active' => 1,
            ]
        );

        // COA minimal untuk posting GRN (reuse bila sudah ada).
        foreach ([
            ['1151', 'Uang Muka Pembelian'], ['1201', 'Persediaan Bahan Baku'],
            ['1202', 'Persediaan WIP'], ['1203', 'Persediaan Barang Jadi'],
            ['1205', 'Persediaan Packaging'], ['1401', 'PPN Masukan'],
            ['2101', 'Hutang Dagang'], ['6102', 'Biaya Transport'],
            ['6110', 'Biaya Pembelian'], ['1305', 'Piutang Supplier'],
        ] as [$code, $name]) {
            Account::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'type' => 'asset', 'is_active' => 1]
            );
        }

        Account::updateOrCreate(
            ['code' => '6104'],
            ['name' => 'Biaya ATK', 'type' => 'expense', 'is_active' => 1]
        );

        $this->poService = app(PurchaseOrderService::class);
        $this->grnService = app(GoodsReceiptService::class);

        $this->actingAs($this->owner);
    }

    /** Buat PO draft dengan satu line. */
    protected function makeDraftPo(float $qty = 10, float $price = 1000, ?int $supplierId = null): PurchaseOrder
    {
        return $this->poService->create([
            'date' => now()->toDateString(),
            'supplier_id' => $supplierId ?? $this->supplier->id,
            'order_type' => 'material',
            'lines' => [
                ['item_id' => $this->item->id, 'qty' => $qty, 'unit_price' => $price],
            ],
        ]);
    }

    protected function makeGrnPayload(PurchaseOrder $po, float $qtyReceived, float $reqPrice = 0, ?int $supplierId = null): array
    {
        $poLine = $po->lines()->first();
        return [
            'date' => now()->toDateString(),
            'supplier_id' => $supplierId ?? $po->supplier_id,
            'warehouse_id' => $this->warehouse->id,
            'purchase_order_id' => $po->id,
            'created_by' => $this->admin->id,
            'lines' => [[
                'purchase_order_line_id' => $poLine->id,
                'item_id' => $this->item->id,
                'qty_received' => $qtyReceived,
                'qty_reject' => 0,
                'unit_price' => $reqPrice, // harga dari "request" — harus diabaikan
            ]],
        ];
    }

    // 1. PO draft tanpa GRN masih bisa diedit + tidak terkunci.
    public function test_draft_po_without_grn_is_not_locked_and_editable(): void
    {
        $po = $this->makeDraftPo();
        $this->assertFalse($po->isLocked());

        $updated = $this->poService->update($po, [
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier->id,
            'order_type' => 'material',
            'lines' => [['item_id' => $this->item->id, 'qty' => 20, 'unit_price' => 1500]],
        ]);

        $this->assertEqualsWithDelta(20, (float) $updated->lines()->first()->qty, 0.001);
    }

    // 2 + 3. GRN dari PO draft → PO terkunci (locked_at, first_grn_id).
    public function test_grn_from_draft_po_locks_po(): void
    {
        $po = $this->makeDraftPo();
        $this->assertSame('draft', $po->status);

        $grn = $this->grnService->create($this->makeGrnPayload($po, 5));

        $po->refresh();
        $this->assertTrue($po->isLocked(), 'PO harus terkunci setelah GRN pertama.');
        $this->assertEquals($grn->id, $po->first_grn_id);
        $this->assertNotNull($po->receiving_started_at);
        // Status PO tidak dipaksa approved.
        $this->assertSame('draft', $po->status);
    }

    // 4. PO terkunci tidak dapat dihapus (HTTP, sebagai owner).
    public function test_locked_po_cannot_be_deleted(): void
    {
        $po = $this->makeDraftPo();
        $this->grnService->create($this->makeGrnPayload($po, 5));

        $res = $this->actingAs($this->owner)
            ->delete(route('purchasing.purchase_orders.destroy', $po->id));
        $res->assertRedirect();

        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id]);
    }

    // 5. Supplier PO terkunci tidak dapat diubah.
    public function test_locked_po_supplier_cannot_change(): void
    {
        $po = $this->makeDraftPo();
        $this->grnService->create($this->makeGrnPayload($po, 5));
        $po->refresh();

        $this->expectException(ValidationException::class);
        $this->poService->update($po, [
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplierOther->id, // ganti supplier
            'order_type' => 'material',
            'lines' => [['item_id' => $this->item->id, 'qty' => 10, 'unit_price' => 1000]],
        ]);
    }

    // 6. Line yang sudah dirujuk GRN tidak dapat dihapus dari PO terkunci.
    public function test_referenced_line_cannot_be_removed(): void
    {
        $po = $this->makeDraftPo();
        $this->grnService->create($this->makeGrnPayload($po, 5));
        $po->refresh();

        $this->expectException(ValidationException::class);
        $this->poService->update($po, [
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier->id,
            'order_type' => 'material',
            'lines' => [], // hapus semua line
        ]);
    }

    // 7. Qty PO tidak dapat diturunkan di bawah qty received.
    public function test_qty_cannot_go_below_received(): void
    {
        $po = $this->makeDraftPo(10, 1000);
        $this->grnService->create($this->makeGrnPayload($po, 5)); // received 5
        $po->refresh();

        $this->expectException(ValidationException::class);
        $this->poService->update($po, [
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier->id,
            'order_type' => 'material',
            'lines' => [['item_id' => $this->item->id, 'qty' => 3, 'unit_price' => 1000]], // < 5
        ]);
    }

    // Owner boleh memperbaiki HARGA pada PO terkunci (qty tetap >= received).
    public function test_owner_can_fix_price_on_locked_po(): void
    {
        $po = $this->makeDraftPo(10, 1000);
        $this->grnService->create($this->makeGrnPayload($po, 5));
        $po->refresh();

        $updated = $this->poService->update($po, [
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier->id,
            'order_type' => 'material',
            'lines' => [['item_id' => $this->item->id, 'qty' => 10, 'unit_price' => 1250]],
        ]);

        $this->assertEqualsWithDelta(1250, (float) $updated->lines()->first()->unit_price, 0.001);
    }

    // 15. GRN dari PO cancelled ditolak.
    public function test_grn_from_cancelled_po_rejected(): void
    {
        $po = $this->makeDraftPo();
        $po->forceFill(['status' => 'cancelled'])->save();

        $this->expectException(ValidationException::class);
        $this->grnService->create($this->makeGrnPayload($po, 5));
    }

    // 16. PO line dari PO lain ditolak.
    public function test_po_line_from_other_po_rejected(): void
    {
        $poA = $this->makeDraftPo();
        $poB = $this->makeDraftPo();
        $poBLine = $poB->lines()->first();

        $payload = $this->makeGrnPayload($poA, 5);
        $payload['lines'][0]['purchase_order_line_id'] = $poBLine->id; // line milik PO B

        $this->expectException(ValidationException::class);
        $this->grnService->create($payload);
    }

    // 17. Supplier mismatch ditolak.
    public function test_supplier_mismatch_rejected(): void
    {
        $po = $this->makeDraftPo();

        $this->expectException(ValidationException::class);
        $this->grnService->create($this->makeGrnPayload($po, 5, 0, $this->supplierOther->id));
    }

    // 18. Over receipt ditolak.
    public function test_over_receipt_rejected(): void
    {
        $po = $this->makeDraftPo(10, 1000);

        $this->expectException(ValidationException::class);
        $this->grnService->create($this->makeGrnPayload($po, 15)); // > 10
    }

    // 10 + 11. Harga dari request diabaikan; GRN memakai harga PO.
    public function test_price_from_request_is_ignored_uses_po_price(): void
    {
        $po = $this->makeDraftPo(10, 1000);
        $grn = $this->grnService->create($this->makeGrnPayload($po, 5, 999999)); // request 999999

        $line = $grn->lines()->first();
        $this->assertEqualsWithDelta(1000, (float) $line->unit_price, 0.001, 'Harga GRN harus dari PO, bukan request.');
    }

    // 12. Posting GRN → stok, mutation, AP, dan jurnal terbentuk benar.
    public function test_posting_creates_stock_mutation_and_journal(): void
    {
        $po = $this->makeDraftPo(10, 1000);
        $grn = $this->grnService->create($this->makeGrnPayload($po, 10));
        $grn = $this->grnService->post($grn);

        $this->assertSame('posted', $grn->fresh()->status);

        $stock = InventoryStock::where('warehouse_id', $this->warehouse->id)
            ->where('item_id', $this->item->id)->first();
        $this->assertNotNull($stock);
        $this->assertEqualsWithDelta(10, (float) $stock->qty, 0.001);

        $this->assertTrue(
            InventoryMutation::where('source_type', 'purchase_receipt')
                ->where('source_id', $grn->id)->exists(),
            'Inventory mutation GRN harus tercatat.'
        );

        $invJournal = Journal::where('source_type', 'grn_inv')
            ->where('source_id', $grn->id)->whereNull('voided_at')->first();
        $this->assertNotNull($invJournal, 'Jurnal inventory GRN harus ada.');

        $apId = (int) Account::where('code', '2101')->value('id');
        $invId = (int) Account::where('code', '1201')->value('id');
        $apCredit = (float) $invJournal->lines()->where('account_id', $apId)->sum('credit');
        $invDebit = (float) $invJournal->lines()->where('account_id', $invId)->sum('debit');
        $this->assertEqualsWithDelta(10000, $apCredit, 0.01);
        $this->assertEqualsWithDelta(10000, $invDebit, 0.01);

        // received_status PO harus fully_received meski PO draft
        $this->assertSame('fully_received', $po->fresh()->received_status);
    }

    public function test_mixed_raw_material_and_atk_po_splits_stock_and_expense(): void
    {
        $expenseAccount = Account::where('code', '6104')->firstOrFail();
        $atk = Item::create([
            'code' => 'GRNATK1',
            'name' => 'Kertas ATK',
            'unit' => 'pack',
            'type' => 'material',
            'item_role' => 'raw_material',
            'default_allocation' => 'expense',
            'default_expense_account_id' => $expenseAccount->id,
            'is_stocked' => false,
            'hpp_behavior' => 'non_hpp',
            'active' => 1,
        ]);
        $packaging = Item::create([
            'code' => 'GRNPACK1',
            'name' => 'Karton Packaging',
            'unit' => 'pcs',
            'type' => 'material',
            'item_role' => 'shipping_supply',
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'active' => 1,
        ]);
        $finishedGood = Item::create([
            'code' => 'GRNFG1',
            'name' => 'Kaos Jadi',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'default_allocation' => 'hpp',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'active' => 1,
        ]);

        $po = $this->poService->create([
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier->id,
            'order_type' => 'material',
            'lines' => [
                ['item_id' => $this->item->id, 'qty' => 10, 'unit_price' => 1000],
                ['item_id' => $atk->id, 'qty' => 2, 'unit_price' => 2000],
                ['item_id' => $packaging->id, 'qty' => 3, 'unit_price' => 1000],
                ['item_id' => $finishedGood->id, 'qty' => 1, 'unit_price' => 5000],
            ],
        ]);

        $poLines = $po->lines()->orderBy('id')->get();
        $this->assertSame('hpp', $poLines[0]->allocation);
        $this->assertSame('expense', $poLines[1]->allocation);
        $this->assertSame($expenseAccount->id, (int) $poLines[1]->expense_account_id);
        $this->assertSame('hpp', $poLines[2]->allocation);
        $this->assertSame('hpp', $poLines[3]->allocation);

        $grn = $this->grnService->create([
            'date' => now()->toDateString(),
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'purchase_order_id' => $po->id,
            'created_by' => $this->admin->id,
            'lines' => $poLines->map(fn ($line) => [
                'purchase_order_line_id' => $line->id,
                'item_id' => $line->item_id,
                'qty_received' => $line->qty,
                'qty_reject' => 0,
                'unit_price' => 999999,
            ])->all(),
        ]);

        $receiptLines = $grn->lines()->orderBy('id')->get();
        $this->assertSame('hpp', $receiptLines[0]->allocation);
        $this->assertSame('expense', $receiptLines[1]->allocation);
        $this->assertSame($expenseAccount->id, (int) $receiptLines[1]->expense_account_id);

        $this->grnService->post($grn);

        $this->assertEqualsWithDelta(
            10,
            (float) InventoryStock::where('warehouse_id', $this->warehouse->id)
                ->where('item_id', $this->item->id)->value('qty'),
            0.001
        );
        $this->assertEqualsWithDelta(
            0,
            (float) InventoryStock::where('warehouse_id', $this->warehouse->id)
                ->where('item_id', $atk->id)->value('qty'),
            0.001
        );
        $this->assertEqualsWithDelta(
            3,
            (float) InventoryStock::where('warehouse_id', $this->warehouse->id)
                ->where('item_id', $packaging->id)->value('qty'),
            0.001
        );
        $this->assertEqualsWithDelta(
            1,
            (float) InventoryStock::where('warehouse_id', $this->warehouse->id)
                ->where('item_id', $finishedGood->id)->value('qty'),
            0.001
        );

        $invJournal = Journal::where('source_type', 'grn_inv')->where('source_id', $grn->id)->whereNull('voided_at')->firstOrFail();
        $expJournal = Journal::where('source_type', 'grn_exp')->where('source_id', $grn->id)->whereNull('voided_at')->firstOrFail();
        $this->assertEqualsWithDelta(10000, (float) $invJournal->lines()->where('account_id', Account::where('code', '1201')->value('id'))->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(3000, (float) $invJournal->lines()->where('account_id', Account::where('code', '1205')->value('id'))->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(5000, (float) $invJournal->lines()->where('account_id', Account::where('code', '1203')->value('id'))->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(4000, (float) $expJournal->lines()->where('account_id', $expenseAccount->id)->sum('debit'), 0.01);
    }

    // 20. Unpost GRN membalik stok dan void jurnal.
    public function test_unpost_reverses_stock_and_voids_journal(): void
    {
        $po = $this->makeDraftPo(10, 1000);
        $grn = $this->grnService->create($this->makeGrnPayload($po, 10));
        $grn = $this->grnService->post($grn);
        $grn = $this->grnService->unpost($grn);

        $this->assertSame('draft', $grn->fresh()->status);

        $stock = InventoryStock::where('warehouse_id', $this->warehouse->id)
            ->where('item_id', $this->item->id)->first();
        $this->assertEqualsWithDelta(0, (float) ($stock->qty ?? 0), 0.001);

        $active = Journal::where('source_type', 'grn_inv')
            ->where('source_id', $grn->id)->whereNull('voided_at')->exists();
        $this->assertFalse($active, 'Jurnal GRN harus ter-void setelah unpost.');
    }

    // 25 + keamanan harga: helper canSeePurchasePrices konsisten.
    // Catatan: enum users.role = [sewing,cutting,operating,admin,owner,other] —
    // tidak memuat 'accounting', jadi branch itu diuji secara in-memory (tanpa insert)
    // agar tidak melanggar CHECK constraint. Secara praktik di sistem ini hanya
    // OWNER (dan developer) yang melihat harga.
    public function test_price_visibility_helper(): void
    {
        $this->assertTrue($this->owner->canSeePurchasePrices());   // owner
        $this->assertTrue($this->admin->canSeePurchasePrices());   // admin purchasing
        $this->assertFalse((new User(['role' => 'operating']))->canSeePurchasePrices());
        // branch accounting (belum jadi role valid di DB) — uji logika saja:
        $this->assertTrue((new User(['role' => 'accounting']))->canSeePurchasePrices());
    }

    // Concurrency-ish (19): dua GRN berturut tidak boleh over-receipt gabungan.
    public function test_sequential_grn_cannot_exceed_outstanding(): void
    {
        $po = $this->makeDraftPo(10, 1000);
        $this->grnService->create($this->makeGrnPayload($po, 7)); // sisa 3

        $this->expectException(ValidationException::class);
        $this->grnService->create($this->makeGrnPayload($po, 5)); // 7 + 5 > 10
    }
}
