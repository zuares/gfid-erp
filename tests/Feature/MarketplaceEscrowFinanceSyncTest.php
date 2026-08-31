<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\ComponentDirection;
use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Models\Channel;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceEscrowSyncService;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceEscrowFinanceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_escrow_sync_creates_transaction_and_components(): void
    {
        [$store, $order] = $this->createOrder('ESCROW-FIN-001', 100000);
        $payload = $this->detailPayload('ESCROW-FIN-001', [
            'buyer_total_amount' => 100000,
            'escrow_amount' => 76000,
            'commission_fee' => 5000,
            'service_fee' => 0,
            'seller_transaction_fee' => 1000,
            'seller_order_processing_fee' => 500,
            'drc_adjustable_refund' => -250,
        ]);
        $this->mockGatewayDetail($store, $payload);

        $result = app(MarketplaceEscrowSyncService::class)->syncOrder($store, $order->channel_order_id);
        $transaction = MarketplaceFinancialTransaction::query()->firstOrFail();

        $this->assertSame('synced', $result['status']);
        $this->assertSame($order->id, $transaction->marketplace_order_id);
        $this->assertSame('100000.00', $transaction->gross_amount);
        $this->assertSame('76000.00', $transaction->net_amount);
        $this->assertSame(EscrowStatus::SYNCED, $transaction->escrow_status);
        $this->assertSame(IncomeStatus::PENDING, $transaction->income_status);
        $this->assertSame(4, MarketplaceFinancialComponent::count());
        $this->assertSame('0.00', MarketplaceFinancialComponent::where('component_code', 'service_fee')->value('amount'));
        $this->assertSame('-250.00', MarketplaceFinancialComponent::where('component_code', 'refund')->value('amount'));
        $this->assertSame(ComponentDirection::CREDIT, MarketplaceFinancialComponent::where('component_code', 'refund')->firstOrFail()->direction);
        $this->assertNull($transaction->sale_journal_id);
        $this->assertNull($transaction->escrow_journal_id);
        $this->assertArrayNotHasKey('_meta', $transaction->raw_payload);
    }

    public function test_repeated_same_escrow_response_is_idempotent_and_changed_amount_updates_component(): void
    {
        [$store, $order] = $this->createOrder('ESCROW-FIN-002', 200000);
        $firstPayload = $this->detailPayload('ESCROW-FIN-002', [
            'buyer_total_amount' => 200000,
            'escrow_amount' => 150000,
            'commission_fee' => 10000,
        ]);
        $secondPayload = $this->detailPayload('ESCROW-FIN-002', [
            'buyer_total_amount' => 200000,
            'escrow_amount' => 145000,
            'commission_fee' => 15000,
        ]);
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store, $firstPayload, $secondPayload): void {
            $mock->shouldReceive('getEscrowDetail')
                ->times(3)
                ->with($store, 'ESCROW-FIN-002')
                ->andReturn($firstPayload, $firstPayload, $secondPayload);
        });
        $service = app(MarketplaceEscrowSyncService::class);

        $service->syncOrder($store, $order->channel_order_id);
        $first = MarketplaceFinancialTransaction::query()->firstOrFail();
        $firstHash = $first->source_hash;
        $service->syncOrder($store, $order->channel_order_id);
        $service->syncOrder($store, $order->channel_order_id);

        $transaction = MarketplaceFinancialTransaction::query()->firstOrFail();
        $this->assertSame($first->id, $transaction->id);
        $this->assertSame(1, MarketplaceFinancialTransaction::count());
        $this->assertSame(1, MarketplaceFinancialComponent::where('component_code', 'admin_fee')->count());
        $this->assertNotSame($firstHash, $transaction->source_hash);
        $this->assertSame('145000.00', $transaction->net_amount);
        $this->assertSame('15000.00', MarketplaceFinancialComponent::where('component_code', 'admin_fee')->value('amount'));
    }

    public function test_empty_escrow_keeps_bridge_pending_without_components(): void
    {
        [$store, $order] = $this->createOrder('ESCROW-FIN-003', 50000);
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store): void {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->with($store, 'ESCROW-FIN-003')
                ->andReturn(['response' => ['order_sn' => 'ESCROW-FIN-003', 'order_income' => []]]);
        });

        $result = app(MarketplaceEscrowSyncService::class)->syncOrder($store, $order->channel_order_id);
        $transaction = MarketplaceFinancialTransaction::query()->firstOrFail();

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('empty_income', $result['reason']);
        $this->assertSame(EscrowStatus::PENDING, $transaction->escrow_status);
        $this->assertSame(0, MarketplaceFinancialComponent::count());
    }

    public function test_batch_sync_keeps_unknown_order_for_reconciliation_and_reports_failed_order(): void
    {
        $store = $this->createStore();
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store): void {
            $mock->shouldReceive('getEscrowDetailBatch')
                ->once()
                ->with($store, ['ESCROW-BATCH-1', 'ESCROW-BATCH-2'])
                ->andReturn([
                    'response' => [
                        ['escrow_detail' => ['order_sn' => 'ESCROW-BATCH-1', 'order_income' => ['escrow_amount' => 80000]]],
                        ['escrow_detail' => ['order_sn' => 'ESCROW-BATCH-2'], 'fail_error' => 'order_not_found', 'fail_message' => 'Not found'],
                    ],
                ]);
        });

        $result = app(MarketplaceEscrowSyncService::class)->syncOrders($store, ['ESCROW-BATCH-1', 'ESCROW-BATCH-2']);
        $transaction = MarketplaceFinancialTransaction::query()->firstOrFail();

        $this->assertSame('partial', $result['status']);
        $this->assertSame(1, $result['synced']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNull($transaction->marketplace_order_id);
        $this->assertSame('ESCROW-BATCH-1', $transaction->order_sn);
    }

    public function test_list_sync_uses_escrow_list_then_batch_detail(): void
    {
        $store = $this->createStore();
        $from = Carbon::parse('2026-08-01 00:00:00', config('app.timezone'));
        $to = Carbon::parse('2026-08-01 23:59:59', config('app.timezone'));
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store, $from, $to): void {
            $mock->shouldReceive('getEscrowList')
                ->once()
                ->with($store, $from->timestamp, $to->timestamp, 1, 100)
                ->andReturn([
                    'response' => [
                        'escrow_list' => [['order_sn' => 'ESCROW-LIST-1', 'payout_amount' => 70000]],
                        'more' => false,
                    ],
                ]);
            $mock->shouldReceive('getEscrowDetailBatch')
                ->once()
                ->with($store, ['ESCROW-LIST-1'])
                ->andReturn([
                    'response' => [
                        ['escrow_detail' => ['order_sn' => 'ESCROW-LIST-1', 'order_income' => ['escrow_amount' => 70000]]],
                    ],
                ]);
        });

        $result = app(MarketplaceEscrowSyncService::class)->syncList($store, $from, $to);

        $this->assertSame('synced', $result['status']);
        $this->assertSame(1, $result['found']);
        $this->assertSame(1, $result['synced']);
        $this->assertDatabaseHas('marketplace_financial_transactions', [
            'store_id' => $store->id,
            'order_sn' => 'ESCROW-LIST-1',
            'net_amount' => '70000.00',
        ]);
    }

    private function mockGatewayDetail(Store $store, array $payload): void
    {
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store, $payload): void {
            $mock->shouldReceive('getEscrowDetail')
                ->once()
                ->with($store, $payload['response']['order_sn'])
                ->andReturn($payload);
        });
    }

    private function detailPayload(string $orderSn, array $income): array
    {
        return [
            'response' => [
                'order_sn' => $orderSn,
                'order_income' => $income,
            ],
            '_meta' => ['http_status' => 200],
        ];
    }

    private function createOrder(string $orderSn, float $amount): array
    {
        $store = $this->createStore();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => $orderSn,
            'channel_order_id' => $orderSn,
            'order_date' => now(),
            'ordered_at' => now(),
            'total_amount' => $amount,
            'currency' => 'IDR',
            'order_status' => 'COMPLETED',
        ]);

        return [$store, $order];
    }

    private function createStore(): Store
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'ESCROW-FIN-'.fake()->unique()->numerify('#####'),
            'name' => 'Escrow Finance Store',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'test-token'],
            'token_expires_at' => now()->addDay(),
        ]);
    }
}
