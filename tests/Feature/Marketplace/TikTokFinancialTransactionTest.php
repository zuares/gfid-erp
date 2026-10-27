<?php

namespace Tests\Feature\Marketplace;

use App\Models\Channel;
use App\Models\MarketplaceTikTokFinancialTransaction;
use App\Models\Store;
use App\Models\User;
use App\Jobs\SyncTikTokFinancialTransactionsJob;
use App\Services\Marketplace\TikTokShop\TikTokFinancialTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TikTokFinancialTransactionTest extends TestCase
{
    use RefreshDatabase;

    private function createStore(string $channelCode = 'tiktok'): Store
    {
        $channel = Channel::firstOrCreate(['code' => $channelCode], ['name' => 'TikTok Shop']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => strtoupper($channelCode).'-FIN-01',
            'name' => 'Toko TikTok Finance',
            'status' => 'active',
            'is_active' => true,
            'external_shop_id' => 'shop-123',
            'credentials' => [
                'app_key' => 'app-key-test',
                'app_secret' => 'app-secret-test',
                'access_token' => 'access-token-test',
                'shop_cipher' => 'shop-cipher-test',
            ],
            'token_expires_at' => now()->addHours(2),
        ]);
    }

    private function user(): User
    {
        return User::factory()->create([
            'employee_code' => 'TTFIN'.random_int(1000, 9999),
            'role' => 'admin',
        ]);
    }

    private function response(): array
    {
        return [
            'code' => 0,
            'message' => 'Success',
            'data' => [
                'next_page_token' => 'next-cursor',
                'total_count' => 3,
                'withdrawals' => [
                    [
                        'id' => 'TT-WITHDRAW-1',
                        'type' => 'WITHDRAW',
                        'amount' => '100000',
                        'currency' => 'IDR',
                        'status' => 'COMPLETED',
                        'create_time' => 1787220000,
                    ],
                    [
                        'id' => 'TT-SETTLE-1',
                        'type' => 'SETTLE',
                        'amount' => '250000',
                        'currency' => 'IDR',
                        'status' => 'SUCCESS',
                        'create_time' => 1787223600,
                    ],
                    [
                        'id' => 'TT-TRANSFER-1',
                        'type' => 'TRANSFER',
                        'amount' => '5000',
                        'currency' => 'IDR',
                        'status' => 'PROCESSING',
                        'create_time' => 1787227200,
                    ],
                ],
            ],
        ];
    }

    public function test_service_normalizes_persists_and_keeps_tiktok_cursor(): void
    {
        $store = $this->createStore();
        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202309/withdrawals*' => Http::response($this->response(), 200),
        ]);

        $result = app(TikTokFinancialTransactionService::class)->fetchTransactions(
            $store,
            now()->subDays(3)->startOfDay(),
            now()->endOfDay(),
            ['types' => ['WITHDRAW', 'SETTLE', 'TRANSFER']],
        );

        $this->assertCount(3, $result['items']);
        $this->assertSame('MONEY_OUT', $result['items'][0]['money_flow']);
        $this->assertSame('MONEY_IN', $result['items'][1]['money_flow']);
        $this->assertSame('UNKNOWN', $result['items'][2]['money_flow']);
        $this->assertSame('next-cursor', $result['pagination']['next_page_token']);
        $this->assertSame(250000.0, $result['summary']['total_money_in']);
        $this->assertSame(100000.0, $result['summary']['total_money_out']);
        $this->assertSame(1, $result['summary']['unknown_flow_count']);
        $this->assertDatabaseCount('marketplace_tiktok_financial_transactions', 3);
        $this->assertDatabaseHas('marketplace_tiktok_financial_transactions', [
            'store_id' => $store->id,
            'external_transaction_id' => 'TT-WITHDRAW-1',
            'transaction_type' => 'WITHDRAW',
            'money_flow' => 'MONEY_OUT',
        ]);

        Http::assertSent(function ($request): bool {
            $query = $request->data();

            return $request->method() === 'GET'
                && str_contains($request->url(), '/finance/202309/withdrawals')
                && ($query['types'] ?? null) === 'WITHDRAW,SETTLE,TRANSFER'
                && ($query['page_size'] ?? null) === 100
                && ! empty($query['sign'])
                && ! empty($query['app_key']);
        });
    }

    public function test_upsert_is_idempotent_and_non_tiktok_store_is_rejected(): void
    {
        $store = $this->createStore();
        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202309/withdrawals*' => Http::response($this->response(), 200),
        ]);
        $service = app(TikTokFinancialTransactionService::class);
        $from = now()->subDays(3)->startOfDay();
        $to = now()->endOfDay();

        $service->fetchTransactions($store, $from, $to);
        $service->fetchTransactions($store, $from, $to);

        $this->assertSame(3, MarketplaceTikTokFinancialTransaction::query()->count());

        $shopeeStore = $this->createStore('shopee');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('bukan channel TikTok Shop');
        $service->fetchTransactions($shopeeStore, $from, $to);
    }

    public function test_statement_source_fetches_statements_and_persists_final_breakdown(): void
    {
        $store = $this->createStore();
        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202309/statements*' => Http::response([
                'code' => 0,
                'data' => [
                    'statements' => [[
                        'id' => 'STATEMENT-1',
                        'create_time' => 1787220000,
                        'status' => 'SETTLED',
                        'currency' => 'IDR',
                    ]],
                    'next_page_token' => '',
                ],
            ], 200),
            'https://open-api.tiktokglobalshop.com/finance/202501/statements/STATEMENT-1/statement_transactions*' => Http::response([
                'code' => 0,
                'data' => [
                    'id' => 'STATEMENT-1',
                    'next_page_token' => '',
                    'transactions' => [
                        [
                            'id' => 'STMT-TX-1',
                            'type' => 'ORDER',
                            'order_id' => 'ORDER-1',
                            'order_create_time' => 1787223600,
                            'settlement_amount' => '130000',
                            'revenue_amount' => '200000',
                            'shipping_cost_amount' => '-40000',
                            'fee_tax_amount' => '-30000',
                        ],
                        [
                            'id' => 'STMT-TX-2',
                            'type' => 'PLATFORM_PENALTY',
                            'adjustment_id' => 'ADJUSTMENT-1',
                            'adjustment_amount' => '-10000',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = app(TikTokFinancialTransactionService::class)->fetchStatementTransactions(
            $store,
            now()->subDays(3)->startOfDay(),
            now()->endOfDay(),
        );

        $this->assertCount(2, $result['items']);
        $this->assertSame('statement_transactions', $result['items'][0]['source']);
        $this->assertSame('STATEMENT-1', $result['items'][0]['statement_id']);
        $this->assertSame('ORDER-1', $result['items'][0]['order_id']);
        $this->assertSame(130000.0, $result['items'][0]['amount']);
        $this->assertSame('MONEY_IN', $result['items'][0]['money_flow']);
        $this->assertSame('MONEY_OUT', $result['items'][1]['money_flow']);
        $this->assertSame(130000.0, $result['summary']['total_money_in']);
        $this->assertSame(10000.0, $result['summary']['total_money_out']);
        $this->assertDatabaseHas('marketplace_tiktok_financial_transactions', [
            'store_id' => $store->id,
            'source' => 'statement_transactions',
            'external_transaction_id' => 'STMT-TX-1',
            'statement_id' => 'STATEMENT-1',
            'order_id' => 'ORDER-1',
            'settlement_amount' => 130000,
        ]);
        $this->assertSame(1, $result['pagination']['statements_fetched']);
        $this->assertSame(1, $result['pagination']['transaction_pages_fetched']);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/finance/202501/statements/STATEMENT-1/statement_transactions')
                && ($request->data()['sort_field'] ?? null) === 'order_create_time'
                && ! empty($request->data()['sign']);
        });
    }

    public function test_controller_can_select_statement_source(): void
    {
        $store = $this->createStore();
        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202309/statements*' => Http::response([
                'code' => 0,
                'data' => ['statements' => [], 'next_page_token' => ''],
            ], 200),
        ]);

        $response = $this->actingAs($this->user())->getJson(route('marketplace.api.tiktok-financial-transactions', [
            'store' => $store->id,
            'source' => 'statements',
            'date_from' => now()->subDays(3)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data_source', 'statements')
            ->assertJsonPath('data.items', []);
    }

    public function test_controller_fetches_final_statement_transactions_for_one_order(): void
    {
        $store = $this->createStore();
        Http::fake([
            'https://open-api.tiktokglobalshop.com/finance/202501/orders/ORDER-1/statement_transactions*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => [
                    'order_id' => 'ORDER-1',
                    'transactions' => [[
                        'id' => 'ORDER-TX-1',
                        'type' => 'ORDER',
                        'order_id' => 'ORDER-1',
                        'settlement_amount' => '130000',
                    ]],
                ],
            ], 200),
        ]);

        $this->actingAs($this->user())
            ->getJson(route('marketplace.api.tiktok-order-statement', [
                'store' => $store->id,
                'orderId' => 'ORDER-1',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('endpoint', 'get_order_statement_transactions')
            ->assertJsonPath('data.data.order_id', 'ORDER-1')
            ->assertJsonPath('data.data.transactions.0.id', 'ORDER-TX-1');
    }

    public function test_controller_page_and_json_endpoint_are_available(): void
    {
        $store = $this->createStore();
        $user = $this->user();
        MarketplaceTikTokFinancialTransaction::create([
            'store_id' => $store->id,
            'external_transaction_id' => 'TT-WITHDRAW-1',
            'source' => 'withdrawals',
            'transaction_type' => 'WITHDRAW',
            'amount' => 100000,
            'currency' => 'IDR',
            'money_flow' => 'MONEY_OUT',
            'status' => 'COMPLETED',
            'transaction_created_at' => now()->subDay(),
            'source_payload' => ['reason' => 'Payout test'],
            'synced_at' => now()->subHour(),
        ]);
        Http::fake();

        $this->actingAs($user)
            ->get(route('marketplace.tiktok.financial-transactions'))
            ->assertOk()
            ->assertViewIs('marketplace.tiktok.financial-transactions')
            ->assertSee('Transaksi Finansial TikTok');

        $this->actingAs($user)
            ->getJson(route('marketplace.api.tiktok-financial-transactions', [
                'store' => $store->id,
                'date_from' => now()->subDays(3)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'tiktok_financial_transactions')
            ->assertJsonPath('storage', 'local_database')
            ->assertJsonPath('data.items.0.transaction_id', 'TT-WITHDRAW-1');

        Http::assertNothingSent();
    }

    public function test_controller_can_queue_local_financial_transaction_sync(): void
    {
        Queue::fake();
        $store = $this->createStore();

        $response = $this->actingAs($this->user())->postJson(
            route('marketplace.api.tiktok-financial-transactions.sync', ['store' => $store->id]),
            [
                'date_from' => now()->subDays(3)->toDateString(),
                'date_to' => now()->toDateString(),
                'source' => 'all',
            ],
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'queued');

        Queue::assertPushed(SyncTikTokFinancialTransactionsJob::class, function (SyncTikTokFinancialTransactionsJob $job) use ($store): bool {
            return $job->storeId === $store->id
                && $job->source === 'all';
        });
    }

    public function test_controller_rejects_date_window_over_sixty_days(): void
    {
        $store = $this->createStore();
        $response = $this->actingAs($this->user())->getJson(route('marketplace.api.tiktok-financial-transactions', [
            'store' => $store->id,
            'date_from' => now()->subDays(70)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('maksimal 60 hari', $response->json('message'));
    }
}
