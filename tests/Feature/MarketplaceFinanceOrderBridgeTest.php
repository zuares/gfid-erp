<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Models\Channel;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceFinanceOrderBridgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceFinanceOrderBridgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_pending_finance_transaction_and_links_invoice_and_shipment(): void
    {
        [$store, $order] = $this->createOrder('BRIDGE-001', 150000);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH-BRIDGE-1',
            'name' => 'Bridge Warehouse',
            'type' => 'internal',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoice = SalesInvoice::create([
            'code' => 'INV-BRIDGE-001',
            'date' => now()->toDateString(),
            'warehouse_id' => $warehouseId,
            'status' => 'posted',
            'grand_total' => 150000,
        ]);
        $invoice->forceFill([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'channel_order_no' => 'BRIDGE-001',
        ])->save();
        $shipment = Shipment::create([
            'code' => 'SHP-BRIDGE-001',
            'store_id' => $store->id,
            'sales_invoice_id' => $invoice->id,
            'date' => now()->toDateString(),
            'status' => 'posted',
        ]);

        $transaction = app(MarketplaceFinanceOrderBridgeService::class)->syncFromOrder($order);

        $this->assertSame($store->id, $transaction->store_id);
        $this->assertSame('shopee', $transaction->channel);
        $this->assertSame('BRIDGE-001', $transaction->order_sn);
        $this->assertSame('150000.00', $transaction->gross_amount);
        $this->assertSame($invoice->id, $transaction->sales_invoice_id);
        $this->assertSame($shipment->id, $transaction->shipment_id);
        $this->assertSame(EscrowStatus::PENDING, $transaction->escrow_status);
        $this->assertSame(IncomeStatus::PENDING, $transaction->income_status);
        $this->assertNull($transaction->sale_journal_id);
        $this->assertNull($transaction->escrow_journal_id);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_it_updates_the_same_scoped_transaction_without_resetting_finance_state(): void
    {
        [$store, $order] = $this->createOrder('BRIDGE-002', 100000);
        $service = app(MarketplaceFinanceOrderBridgeService::class);
        $first = $service->syncFromOrder($order);
        $first->forceFill([
            'net_amount' => 76000,
            'escrow_status' => EscrowStatus::FINALIZED,
            'income_status' => IncomeStatus::RELEASED,
            'source_hash' => str_repeat('b', 64),
        ])->save();
        $order->forceFill(['total_amount' => 110000])->save();

        $second = $service->syncFromOrder($order->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, MarketplaceFinancialTransaction::where([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'order_sn' => 'BRIDGE-002',
        ])->count());
        $this->assertSame('110000.00', $second->gross_amount);
        $this->assertSame('76000.00', $second->net_amount);
        $this->assertSame(EscrowStatus::FINALIZED, $second->escrow_status);
        $this->assertSame(IncomeStatus::RELEASED, $second->income_status);
        $this->assertSame(str_repeat('b', 64), $second->source_hash);
    }

    public function test_it_keeps_valid_pending_state_when_order_has_no_invoice_or_shipment(): void
    {
        [, $order] = $this->createOrder('BRIDGE-003', 0);

        $transaction = app(MarketplaceFinanceOrderBridgeService::class)->syncFromOrder($order);

        $this->assertNull($transaction->sales_invoice_id);
        $this->assertNull($transaction->shipment_id);
        $this->assertSame('0.00', $transaction->gross_amount);
        $this->assertSame(EscrowStatus::PENDING, $transaction->escrow_status);
        $this->assertSame(IncomeStatus::PENDING, $transaction->income_status);
    }

    private function createOrder(string $orderSn, float $amount): array
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'BRIDGE-'.$orderSn,
            'name' => 'Bridge Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => $orderSn,
            'channel_order_id' => $orderSn,
            'order_date' => now(),
            'ordered_at' => now(),
            'currency' => 'idr',
            'total_amount' => $amount,
            'order_status' => 'COMPLETED',
        ]);

        return [$store, $order];
    }
}
