<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\Channel;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinanceSettlementAllocation;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceSettlementSyncService;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceSettlementFinanceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_payout_creates_one_header_and_one_allocation_without_journal(): void
    {
        [$store, $order] = $this->createOrder('SETTLE-FIN-001', 200000);
        $from = Carbon::parse('2026-08-01', config('app.timezone'));
        $to = Carbon::parse('2026-08-10', config('app.timezone'));
        $this->mockPayoutApis($store, $from, $to, [
            'payout_info' => [
                'encrypted_payout_id' => 'PAYOUT-001',
                'payout_amount' => 200000,
                'payout_currency' => 'IDR',
                'payout_time' => 1785580800,
            ],
        ], [
            'payout_info' => [
                'encrypted_payout_id' => 'PAYOUT-001',
                'payout_amount' => 200000,
                'payout_currency' => 'IDR',
                'payout_time' => 1785580800,
            ],
            'escrow_list' => [['order_sn' => 'SETTLE-FIN-001', 'escrow_amount' => 200000]],
        ]);

        $result = app(MarketplaceSettlementSyncService::class)->syncPayouts($store, $from, $to);
        $settlement = MarketplaceFinanceSettlement::query()->firstOrFail();
        $allocation = MarketplaceFinanceSettlementAllocation::query()->firstOrFail();

        $this->assertSame('synced', $result['status']);
        $this->assertSame(1, $result['allocated']);
        $this->assertSame('PAYOUT-001', $settlement->external_settlement_id);
        $this->assertSame(SettlementStatus::UNKNOWN, $settlement->status);
        $this->assertNull($settlement->received_at);
        $this->assertSame($order->id, $allocation->financialTransaction->marketplace_order_id);
        $this->assertSame('200000.00', $allocation->allocated_amount);
        $this->assertNull($settlement->journal_id);
    }

    public function test_one_settlement_can_allocate_many_orders_and_report_partial_mapping(): void
    {
        [$store, $first] = $this->createOrder('SETTLE-FIN-002-A', 100000);
        $this->createOrderForStore($store, 'SETTLE-FIN-002-B', 150000);
        $from = Carbon::parse('2026-08-01', config('app.timezone'));
        $to = Carbon::parse('2026-08-10', config('app.timezone'));
        $header = ['payout_info' => ['encrypted_payout_id' => 'PAYOUT-002', 'payout_amount' => 250000, 'payout_currency' => 'IDR']];
        $detail = $header + ['escrow_list' => [
            ['order_sn' => 'SETTLE-FIN-002-A', 'escrow_amount' => 100000],
            ['order_sn' => 'SETTLE-FIN-002-B', 'escrow_amount' => 150000],
            ['order_sn' => 'SETTLE-FIN-002-MISSING', 'escrow_amount' => 5000],
        ]];
        $this->mockPayoutApis($store, $from, $to, $header, $detail);

        $result = app(MarketplaceSettlementSyncService::class)->syncPayouts($store, $from, $to);

        $this->assertSame(2, $result['allocated']);
        $this->assertSame(1, $result['unmatched']);
        $this->assertSame(2, MarketplaceFinanceSettlementAllocation::count());
        $this->assertSame($first->id, MarketplaceFinancialTransaction::where('order_sn', 'SETTLE-FIN-002-A')->value('marketplace_order_id'));
        $this->assertSame(1, MarketplaceFinanceSettlement::count());
    }

    public function test_duplicate_payout_response_is_idempotent(): void
    {
        $store = $this->createStore();
        $from = Carbon::parse('2026-08-01', config('app.timezone'));
        $to = Carbon::parse('2026-08-10', config('app.timezone'));
        $header = ['payout_info' => ['encrypted_payout_id' => 'PAYOUT-003', 'payout_amount' => 90000, 'payout_currency' => 'IDR', 'status' => 'COMPLETED']];
        $detail = $header + ['escrow_list' => [['order_sn' => 'SETTLE-FIN-003-MISSING', 'escrow_amount' => 90000]]];
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store, $from, $to, $header, $detail): void {
            $mock->shouldReceive('getPayoutInfo')->times(2)->with($store, $from->timestamp, $to->timestamp, '', 100)->andReturn(
                ['response' => ['payout_list' => [$header], 'more' => false]],
                ['response' => ['payout_list' => [$header], 'more' => false]],
            );
            $mock->shouldReceive('getPayoutDetail')->times(2)->with($store, $from->timestamp, $to->timestamp, 1, 100)->andReturn(
                ['response' => ['payout_list' => [$detail], 'more' => false]],
                ['response' => ['payout_list' => [$detail], 'more' => false]],
            );
        });

        $service = app(MarketplaceSettlementSyncService::class);
        $service->syncPayouts($store, $from, $to);
        $service->syncPayouts($store, $from, $to);

        $this->assertSame(1, MarketplaceFinanceSettlement::count());
        $this->assertSame(SettlementStatus::RECEIVED, MarketplaceFinanceSettlement::firstOrFail()->status);
        $this->assertSame(0, MarketplaceFinanceSettlementAllocation::count());
    }

    public function test_wallet_payout_requires_withdrawal_row_and_valid_status_and_is_idempotent(): void
    {
        $store = $this->createStore();
        $from = Carbon::parse('2026-08-01', config('app.timezone'));
        $to = Carbon::parse('2026-08-10', config('app.timezone'));
        $valid = [
            'transaction_id' => 'WALLET-001',
            'transaction_type' => 'withdrawal_created',
            'status' => 'COMPLETED',
            'amount' => -125000,
            'create_time' => 1785580800,
        ];
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store, $from, $to, $valid): void {
            $mock->shouldReceive('getWalletTransactionList')->times(2)->with($store, 0, 100, $from->timestamp, $to->timestamp, 'MONEY_OUT', '201', null, 'wallet_withdrawals')->andReturn(
                ['response' => ['transaction_list' => [$valid, ['transaction_id' => 'AD-1', 'transaction_type' => 'paid_ads_charge', 'status' => 'COMPLETED', 'amount' => -5000]], 'more' => false]],
                ['response' => ['transaction_list' => [$valid], 'more' => false]],
            );
        });

        $service = app(MarketplaceSettlementSyncService::class);
        $first = $service->syncWalletPayouts($store, $from, $to);
        $service->syncWalletPayouts($store, $from, $to);
        $settlement = MarketplaceFinanceSettlement::query()->firstOrFail();

        $this->assertSame(1, $first['synced']);
        $this->assertSame(1, $first['invalid']);
        $this->assertSame(SettlementStatus::RECEIVED, $settlement->status);
        $this->assertSame('125000.00', $settlement->amount);
        $this->assertNotNull($settlement->received_at);
        $this->assertSame(1, MarketplaceFinanceSettlement::count());
    }

    private function mockPayoutApis(Store $store, Carbon $from, Carbon $to, array $info, array $detail): void
    {
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store, $from, $to, $info, $detail): void {
            $mock->shouldReceive('getPayoutInfo')->once()->with($store, $from->timestamp, $to->timestamp, '', 100)->andReturn([
                'response' => ['payout_list' => [$info], 'more' => false],
            ]);
            $mock->shouldReceive('getPayoutDetail')->once()->with($store, $from->timestamp, $to->timestamp, 1, 100)->andReturn([
                'response' => ['payout_list' => [$detail], 'more' => false],
            ]);
        });
    }

    private function createOrder(string $orderSn, float $amount): array
    {
        $store = $this->createStore();

        return [$store, $this->createOrderForStore($store, $orderSn, $amount)];
    }

    private function createOrderForStore(Store $store, string $orderSn, float $amount = 100000): MarketplaceOrder
    {
        return MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => $orderSn,
            'channel_order_id' => $orderSn,
            'order_date' => now(),
            'ordered_at' => now(),
            'total_amount' => $amount,
            'currency' => 'IDR',
            'order_status' => 'COMPLETED',
        ]);
    }

    private function createStore(): Store
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'SETTLE-FIN-'.fake()->unique()->numerify('#####'),
            'name' => 'Settlement Finance Store',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'test-token'],
            'token_expires_at' => now()->addDay(),
        ]);
    }
}
