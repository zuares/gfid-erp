<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\ComponentDirection;
use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\Account;
use App\Models\Channel;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinanceSettlementAllocation;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceFinancePostingService;
use App\Services\Marketplace\Finance\MarketplaceFinanceReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceFinanceReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_finance_chain_is_matched_and_reconciliation_is_read_only(): void
    {
        [$store, $transaction, $settlement] = $this->completeChain();
        $journalCount = DB::table('journals')->count();
        $snapshot = $transaction->fresh()->toArray();

        $result = app(MarketplaceFinanceReconciliationService::class)->reconcile(['store_id' => $store->id]);
        $item = $result['transactions'][0];

        $this->assertSame('matched', $item['status']);
        $this->assertSame([], $item['reasons']);
        $this->assertSame(100000.0, $item['gross_sales_invoice']);
        $this->assertSame(100000.0, $item['escrow_gross']);
        $this->assertSame(5000.0, $item['total_components']);
        $this->assertSame(95000.0, $item['expected_net_income']);
        $this->assertSame(95000.0, $item['settlement_received_amount']);
        $this->assertSame(1, $result['summary']['matched']);
        $this->assertSame($journalCount, DB::table('journals')->count());
        $this->assertSame($snapshot, $transaction->fresh()->toArray());
        $this->assertSame(1, MarketplaceFinanceSettlement::count());
        $this->assertSame('RECON-MATCHED-001', $transaction->order_sn);
    }

    public function test_invoice_or_fee_amount_difference_is_reported_as_mismatch(): void
    {
        [$store, $transaction] = $this->transaction('RECON-MISMATCH-001', 100000);
        $warehouseId = $this->warehouse('RECON-MISMATCH-WH');
        $invoice = SalesInvoice::create([
            'code' => 'INV-RECON-MISMATCH-001',
            'date' => '2026-08-28',
            'warehouse_id' => $warehouseId,
            'status' => 'posted',
            'grand_total' => 110000,
        ]);
        $transaction->forceFill([
            'sales_invoice_id' => $invoice->id,
            'escrow_status' => EscrowStatus::FINALIZED,
            'net_amount' => 90000,
            'source_hash' => str_repeat('e', 64),
            'raw_payload' => ['income' => ['buyer_total_amount' => 100000]],
        ])->save();
        MarketplaceFinancialComponent::create([
            'financial_transaction_id' => $transaction->id,
            'component_code' => 'admin_fee',
            'component_name' => 'Admin Fee',
            'amount' => 5000,
            'direction' => ComponentDirection::DEBIT,
            'dedupe_key' => str_repeat('m', 64),
        ]);

        $result = app(MarketplaceFinanceReconciliationService::class)->reconcile(['order_sn' => 'RECON-MISMATCH-001']);
        $item = $result['transactions'][0];

        $this->assertSame('mismatch', $item['status']);
        $this->assertContains('amount_mismatch', $item['reasons']);
        $this->assertContains('fee_mismatch', $item['reasons']);
        $this->assertContains('missing_shipment', $item['reasons']);
        $this->assertSame(0, DB::table('journals')->count());
    }

    public function test_incomplete_transaction_is_pending_and_explains_missing_facts(): void
    {
        [$store, $transaction] = $this->transaction('RECON-PENDING-001', 80000);

        $result = app(MarketplaceFinanceReconciliationService::class)->reconcile([
            'store_id' => $store->id,
            'status' => 'pending',
        ]);
        $item = $result['transactions'][0];

        $this->assertSame($transaction->id, $item['transaction_id']);
        $this->assertSame('pending', $item['status']);
        $this->assertContains('missing_sales_invoice', $item['reasons']);
        $this->assertContains('missing_shipment', $item['reasons']);
        $this->assertContains('missing_escrow', $item['reasons']);
        $this->assertContains('missing_settlement', $item['reasons']);
        $this->assertSame(1, $result['summary']['pending']);
    }

    public function test_settlement_without_allocation_is_reported_without_creating_journal(): void
    {
        $store = $this->store();
        MarketplaceFinanceSettlement::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'external_settlement_id' => 'RECON-SETTLEMENT-EMPTY',
            'settlement_date' => '2026-08-28',
            'amount' => 50000,
            'status' => SettlementStatus::UNKNOWN,
        ]);

        $result = app(MarketplaceFinanceReconciliationService::class)->reconcile(['store_id' => $store->id]);
        $settlement = $result['settlements'][0];

        $this->assertSame('pending', $settlement['status']);
        $this->assertContains('settlement_without_allocation', $settlement['reasons']);
        $this->assertContains('missing_settlement', $settlement['reasons']);
        $this->assertSame(0, DB::table('journals')->count());
    }

    private function completeChain(): array
    {
        [$store, $transaction] = $this->transaction('RECON-MATCHED-001', 100000);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => $transaction->order_sn,
            'channel_order_id' => $transaction->order_sn,
            'order_date' => '2026-08-28',
            'ordered_at' => '2026-08-28 09:00:00',
            'order_status' => 'COMPLETED',
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);
        $warehouseId = $this->warehouse('RECON-MATCHED-WH');
        $invoice = SalesInvoice::create([
            'code' => 'INV-RECON-MATCHED-001',
            'date' => '2026-08-28',
            'warehouse_id' => $warehouseId,
            'status' => 'posted',
            'grand_total' => 100000,
        ]);
        $invoice->forceFill(['store_id' => $store->id, 'channel' => 'shopee', 'channel_order_no' => $transaction->order_sn])->save();
        $shipment = Shipment::create([
            'code' => 'SHP-RECON-MATCHED-001',
            'store_id' => $store->id,
            'sales_invoice_id' => $invoice->id,
            'date' => '2026-08-28',
            'status' => 'posted',
        ]);
        $transaction->forceFill([
            'marketplace_order_id' => $order->id,
            'sales_invoice_id' => $invoice->id,
            'shipment_id' => $shipment->id,
            'gross_amount' => 100000,
            'net_amount' => 95000,
            'escrow_status' => EscrowStatus::FINALIZED,
            'income_status' => IncomeStatus::RELEASED,
            'source_hash' => str_repeat('f', 64),
            'raw_payload' => ['income' => ['buyer_total_amount' => 100000]],
            'released_at' => '2026-08-28 10:00:00',
        ])->save();
        MarketplaceFinancialComponent::create([
            'financial_transaction_id' => $transaction->id,
            'component_code' => 'admin_fee',
            'component_name' => 'Admin Fee',
            'amount' => 5000,
            'direction' => ComponentDirection::DEBIT,
            'dedupe_key' => str_repeat('c', 64),
        ]);
        $bank = $this->account('1101', 'Bank Rekonsiliasi', 'asset', true);
        $settlement = MarketplaceFinanceSettlement::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'external_settlement_id' => 'RECON-SETTLEMENT-001',
            'settlement_date' => '2026-08-28',
            'amount' => 95000,
            'currency' => 'IDR',
            'bank_account_id' => $bank->id,
            'status' => SettlementStatus::RECEIVED,
            'received_at' => '2026-08-28 12:00:00',
        ]);
        MarketplaceFinanceSettlementAllocation::create([
            'settlement_id' => $settlement->id,
            'financial_transaction_id' => $transaction->id,
            'order_sn' => $transaction->order_sn,
            'allocated_amount' => 95000,
        ]);
        $this->accounts();
        $posting = app(MarketplaceFinancePostingService::class);
        $posting->postSale($transaction);
        $posting->postEscrow($transaction->fresh());
        $posting->postSettlement($settlement);

        return [$store, $transaction, $settlement];
    }

    private function transaction(string $orderSn, float $amount): array
    {
        $store = $this->store();

        return [$store, MarketplaceFinancialTransaction::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'order_sn' => $orderSn,
            'currency' => 'IDR',
            'gross_amount' => $amount,
            'net_amount' => $amount,
            'escrow_status' => EscrowStatus::PENDING,
            'income_status' => IncomeStatus::PENDING,
        ])];
    }

    private function warehouse(string $code): int
    {
        return DB::table('warehouses')->insertGetId([
            'code' => $code,
            'name' => $code,
            'type' => 'internal',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function account(string $code, string $name, string $type, bool $cash = false): Account
    {
        return Account::updateOrCreate(['code' => $code], ['name' => $name, 'type' => $type, 'is_cash' => $cash, 'is_active' => true]);
    }

    private function accounts(): void
    {
        foreach ([['1302', 'Saldo Marketplace / Clearing'], ['4101', 'Penjualan'], ['5101', 'HPP'], ['6202', 'Biaya Komisi']] as [$code, $name]) {
            $this->account($code, $name, $code === '4101' ? 'revenue' : 'expense');
        }
    }

    private function store(): Store
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);

        return Store::create(['channel_id' => $channel->id, 'code' => 'RECON-'.fake()->unique()->numerify('#####'), 'name' => 'Recon Store', 'status' => 'active', 'is_active' => true]);
    }
}
