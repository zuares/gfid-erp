<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\Warehouse;
use App\Services\Accounting\SalesInvoiceAccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalesInvoicePostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_posting_creates_balanced_idempotent_journal(): void
    {
        $receivable = $this->account('1301', 'Piutang Dagang', 'asset');
        $sales = $this->account('4101', 'Penjualan', 'revenue');
        $this->account('2201', 'PPN Keluaran', 'liability');
        $warehouse = Warehouse::create(['code' => 'TEST-WH', 'name' => 'Test Warehouse']);
        $item = Item::create([
            'code' => 'TEST-SALES-ITEM',
            'name' => 'Test Sales Item',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'hpp' => 0,
            'last_purchase_price' => 0,
            'active' => true,
        ]);

        $invoice = SalesInvoice::create([
            'code' => 'INV-TEST-001',
            'date' => '2026-08-27',
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'subtotal' => 100000,
            'discount_total' => 0,
            'tax_percent' => 11,
            'tax_amount' => 11000,
            'grand_total' => 111000,
            'currency' => 'IDR',
        ]);
        $invoice->lines()->create([
            'item_id' => $item->id,
            'qty' => 1,
            'unit_price' => 100000,
            'line_discount' => 0,
            'line_total' => 100000,
            'hpp_unit_snapshot' => 0,
            'hpp_total_snapshot' => 0,
            'margin_unit' => 100000,
            'margin_total' => 100000,
        ]);

        $service = app(SalesInvoiceAccountingService::class);
        $posted = $service->post($invoice);
        $postedAgain = $service->post($posted);

        $this->assertSame('posted', $posted->status);
        $this->assertNotNull($posted->journal_id);
        $this->assertSame($posted->journal_id, $postedAgain->journal_id);
        $this->assertDatabaseCount('journals', 1);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $posted->journal_id,
            'account_id' => $receivable->id,
            'debit' => 111000,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $posted->journal_id,
            'account_id' => $sales->id,
            'debit' => 0,
            'credit' => 100000,
        ]);
    }

    public function test_marketplace_invoice_must_use_settlement_posting(): void
    {
        $warehouse = Warehouse::create(['code' => 'TEST-MP-WH', 'name' => 'Test Marketplace Warehouse']);
        $invoice = SalesInvoice::create([
            'code' => 'INV-TEST-MP-001',
            'date' => '2026-08-27',
            'warehouse_id' => $warehouse->id,
            'channel' => 'shopee',
            'status' => 'draft',
            'subtotal' => 100000,
            'grand_total' => 100000,
            'currency' => 'IDR',
        ]);

        $this->expectException(ValidationException::class);
        app(SalesInvoiceAccountingService::class)->post($invoice);
    }

    private function account(string $code, string $name, string $type): Account
    {
        return Account::create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'is_cash' => false,
            'is_active' => true,
        ]);
    }
}
