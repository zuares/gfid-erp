<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Models\Channel;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceIncomeSyncService;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MarketplaceIncomeFinanceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_income_updates_only_income_metadata_and_creates_no_journal_or_component(): void
    {
        [$store, $order] = $this->createOrder('INCOME-FIN-001', 125000);
        $payload = $this->incomePayload([
            'order_sn' => 'INCOME-FIN-001',
            'status' => 'Pending',
            'estimated_escrow_amount' => 95000,
        ]);
        $this->mockIncome($store, $payload);

        $result = $this->sync($store);
        $transaction = MarketplaceFinancialTransaction::query()->firstOrFail();

        $this->assertSame('synced', $result['status']);
        $this->assertSame(1, $result['found']);
        // The order bridge creates the transaction before income metadata is
        // applied, so this phase reports the row as updated rather than newly
        // created by the income writer itself.
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame($order->id, $transaction->marketplace_order_id);
        $this->assertSame(IncomeStatus::PENDING, $transaction->income_status);
        $this->assertNotNull($transaction->income_synced_at);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $transaction->income_source_hash);
        $this->assertSame($payload['response']['income_detail_list']['list'][0], $transaction->income_raw_payload);
        $this->assertNull($transaction->sale_journal_id);
        $this->assertNull($transaction->escrow_journal_id);
        $this->assertSame(0, MarketplaceFinancialComponent::count());
    }

    public function test_income_status_moves_pending_to_to_release_to_released_without_duplicate_transaction(): void
    {
        [$store, $order] = $this->createOrder('INCOME-FIN-002', 180000);
        $releasedAt = Carbon::parse('2026-08-28 10:30:00', config('app.timezone'));
        $responses = [
            $this->incomePayload(['order_sn' => 'INCOME-FIN-002', 'income_status' => 2]),
            $this->incomePayload(['order_sn' => 'INCOME-FIN-002', 'income_status' => 0]),
            $this->incomePayload([
                'order_sn' => 'INCOME-FIN-002',
                'income_status' => 1,
                'released_at' => $releasedAt->toIso8601String(),
            ]),
        ];
        $from = Carbon::parse('2026-08-27', config('app.timezone'));
        $to = Carbon::parse('2026-08-28', config('app.timezone'));
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store, $from, $to, $responses): void {
            $mock->shouldReceive('getIncomeDetail')
                ->times(3)
                ->with($store, $from->toDateString(), $to->toDateString(), 2, 100, '')
                ->andReturn(...$responses);
        });

        $service = app(MarketplaceIncomeSyncService::class);
        $service->sync($store, $from, $to);
        $this->assertSame(IncomeStatus::PENDING, MarketplaceFinancialTransaction::query()->firstOrFail()->income_status);
        $service->sync($store, $from, $to);
        $this->assertSame(IncomeStatus::TO_RELEASE, MarketplaceFinancialTransaction::query()->firstOrFail()->income_status);
        $service->sync($store, $from, $to);
        $transaction = MarketplaceFinancialTransaction::query()->firstOrFail();

        $this->assertSame($order->id, $transaction->marketplace_order_id);
        $this->assertSame(IncomeStatus::RELEASED, $transaction->income_status);
        $this->assertTrue($transaction->released_at->equalTo($releasedAt));
        $this->assertSame(1, MarketplaceFinancialTransaction::count());
        $this->assertSame(0, MarketplaceFinancialComponent::count());
    }

    public function test_unknown_income_status_is_preserved_as_unknown(): void
    {
        $store = $this->createStore();
        $this->mockIncome($store, $this->incomePayload([
            'order_sn' => 'INCOME-FIN-UNKNOWN',
            'status' => 'Future Marketplace State',
        ]));

        $result = $this->sync($store);
        $transaction = MarketplaceFinancialTransaction::query()->firstOrFail();

        $this->assertSame(1, $result['unknown']);
        $this->assertSame(1, $result['unmatched']);
        $this->assertSame(IncomeStatus::UNKNOWN, $transaction->income_status);
        $this->assertNull($transaction->marketplace_order_id);
    }

    public function test_income_sync_paginates_and_deduplicates_by_store_channel_order(): void
    {
        $store = $this->createStore();
        $from = Carbon::parse('2026-08-01', config('app.timezone'));
        $to = Carbon::parse('2026-08-02', config('app.timezone'));
        $row = ['order_sn' => 'INCOME-FIN-PAGE-1', 'status' => 'To Release'];
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store, $from, $to, $row): void {
            $mock->shouldReceive('getIncomeDetail')
                ->once()
                ->with($store, $from->toDateString(), $to->toDateString(), 2, 100, '')
                ->andReturn([
                    'response' => [
                        'income_detail_list' => ['list' => [$row], 'next_page' => ['cursor' => 'NEXT']],
                        'more' => true,
                    ],
                ]);
            $mock->shouldReceive('getIncomeDetail')
                ->once()
                ->with($store, $from->toDateString(), $to->toDateString(), 2, 100, 'NEXT')
                ->andReturn([
                    'response' => [
                        'income_detail_list' => ['list' => [$row]],
                        'more' => false,
                    ],
                ]);
        });

        $result = app(MarketplaceIncomeSyncService::class)->sync($store, $from, $to);

        $this->assertSame(2, $result['found']);
        $this->assertSame(2, $result['synced']);
        $this->assertSame(2, $result['unmatched']);
        $this->assertSame(2, $result['pages']);
        $this->assertSame(1, MarketplaceFinancialTransaction::count());
    }

    public function test_income_sync_does_not_overwrite_existing_escrow_state_or_raw_payload(): void
    {
        [$store, $order] = $this->createOrder('INCOME-FIN-005', 220000);
        $transaction = MarketplaceFinancialTransaction::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'marketplace_order_id' => $order->id,
            'order_sn' => $order->channel_order_id,
            'currency' => 'IDR',
            'gross_amount' => 220000,
            'net_amount' => 190000,
            'escrow_status' => EscrowStatus::SYNCED,
            'income_status' => IncomeStatus::PENDING,
            'source_hash' => 'escrow-hash',
            'raw_payload' => ['source' => 'escrow'],
            'sale_journal_id' => null,
            'escrow_journal_id' => null,
        ]);
        $this->mockIncome($store, $this->incomePayload([
            'order_sn' => 'INCOME-FIN-005',
            'status' => 'Released',
            'released_at' => 1787913000,
        ]));

        $this->sync($store);
        $transaction->refresh();

        $this->assertSame(EscrowStatus::SYNCED, $transaction->escrow_status);
        $this->assertSame('220000.00', $transaction->gross_amount);
        $this->assertSame('190000.00', $transaction->net_amount);
        $this->assertSame('escrow-hash', $transaction->source_hash);
        $this->assertSame(['source' => 'escrow'], $transaction->raw_payload);
        $this->assertSame(IncomeStatus::RELEASED, $transaction->income_status);
    }

    private function sync(Store $store): array
    {
        return app(MarketplaceIncomeSyncService::class)->sync(
            $store,
            Carbon::parse('2026-08-27', config('app.timezone')),
            Carbon::parse('2026-08-28', config('app.timezone')),
        );
    }

    private function mockIncome(Store $store, array $payload): void
    {
        $from = Carbon::parse('2026-08-27', config('app.timezone'));
        $to = Carbon::parse('2026-08-28', config('app.timezone'));
        $this->mock(MarketplaceApiGateway::class, function (MockInterface $mock) use ($store, $from, $to, $payload): void {
            $mock->shouldReceive('getIncomeDetail')
                ->once()
                ->with($store, $from->toDateString(), $to->toDateString(), 2, 100, '')
                ->andReturn($payload);
        });
    }

    private function incomePayload(array $row): array
    {
        return [
            'response' => [
                'income_detail_list' => [
                    'list' => [$row],
                    'next_page' => ['cursor' => ''],
                ],
                'more' => false,
            ],
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
            'code' => 'INCOME-FIN-'.fake()->unique()->numerify('#####'),
            'name' => 'Income Finance Store',
            'status' => 'active',
            'is_active' => true,
            'credentials' => ['access_token' => 'test-token'],
            'token_expires_at' => now()->addDay(),
        ]);
    }
}
