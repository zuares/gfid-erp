<?php

namespace Tests\Feature\Accounting;

use App\Models\Channel;
use App\Models\MarketplaceAdWalletTransaction;
use App\Models\Store;
use App\Services\Marketplace\Ads\ShopeeWalletAdCostSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopeeWalletAdCostSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_menyimpan_charge_dan_refund_dengan_amount_signed_dan_idempotent(): void
    {
        $store = $this->createShopeeStore();
        $fixture = [
            'response' => [
                'transaction_list' => [
                    [
                        'transaction_id' => 1001,
                        'transaction_type' => 'paid_ads_charge',
                        'amount' => -15000,
                        'status' => 'COMPLETED',
                        'create_time' => 1787223600,
                        'wallet_type' => 'seller_balance',
                        'reason' => 'Paid ads charge',
                    ],
                ],
                'more' => false,
            ],
        ];
        $refund = [
            'response' => [
                'transaction_list' => [
                    [
                        'transaction_id' => 1002,
                        'transaction_type' => 'paid_ads_refund',
                        'amount' => 2000,
                        'status' => 'COMPLETED',
                        'create_time' => 1787227200,
                        'wallet_type' => 'seller_balance',
                        'reason' => 'Paid ads refund',
                    ],
                ],
                'more' => false,
            ],
        ];

        Http::fake(function ($request) use ($fixture, $refund) {
            return Http::response(
                ($request->data()['transaction_type'] ?? null) === '451' ? $refund : $fixture,
                200,
            );
        });

        $from = now()->subDays(2)->startOfDay();
        $to = now()->endOfDay();
        $service = app(ShopeeWalletAdCostSyncService::class);

        $first = $service->sync($store, $from, $to);

        $this->assertSame(2, $first['created']);
        $this->assertSame(0, $first['updated']);
        $this->assertDatabaseHas('marketplace_ad_wallet_transactions', [
            'store_id' => $store->id,
            'external_transaction_id' => '1001',
            'transaction_type' => 'paid_ads_charge',
            'amount' => -15000,
            'money_flow' => 'MONEY_OUT',
        ]);
        $this->assertDatabaseHas('marketplace_ad_wallet_transactions', [
            'store_id' => $store->id,
            'external_transaction_id' => '1002',
            'transaction_type' => 'paid_ads_refund',
            'amount' => 2000,
            'money_flow' => 'MONEY_IN',
        ]);
        $this->assertSame(-13000.0, (float) MarketplaceAdWalletTransaction::sum('amount'));

        $second = $service->sync($store, $from, $to);

        $this->assertSame(0, $second['created']);
        $this->assertSame(2, $second['updated']);
        $this->assertDatabaseCount('marketplace_ad_wallet_transactions', 2);
        Http::assertSentCount(4); // 2 transaction types per sync.
    }

    public function test_sync_memecah_periode_lebih_dari_lima_belas_hari(): void
    {
        $store = $this->createShopeeStore();
        Http::fake(['*/api/v2/payment/get_wallet_transaction_list*' => Http::response([
            'response' => ['transaction_list' => [], 'more' => false],
        ], 200)]);

        $from = now()->subDays(31)->startOfDay();
        $to = now()->endOfDay();
        $result = app(ShopeeWalletAdCostSyncService::class)->sync($store, $from, $to);

        $this->assertSame(6, $result['requests']); // 3 windows x 2 types.
        $ranges = collect(Http::recorded())
            ->map(fn (array $record) => [
                $record[0]->data()['create_time_from'],
                $record[0]->data()['create_time_to'],
                $record[0]->data()['transaction_type'],
            ])
            ->all();

        $this->assertSame($from->timestamp, $ranges[0][0]);
        $this->assertSame($from->copy()->addDays(14)->endOfDay()->timestamp, $ranges[0][1]);
        $this->assertSame('450', $ranges[0][2]);
        $this->assertSame('451', $ranges[1][2]);
    }

    public function test_sync_mengabaikan_respons_campuran_agar_charge_tidak_menjadi_refund(): void
    {
        $store = $this->createShopeeStore();
        $mixed = [
            'response' => [
                'transaction_list' => [
                    [
                        'transaction_id' => 2001,
                        'transaction_type' => 'paid_ads_charge',
                        'amount' => -10000,
                        'create_time' => 1787223600,
                    ],
                    [
                        'transaction_id' => 2002,
                        'transaction_type' => 'paid_ads_refund',
                        'amount' => 1500,
                        'create_time' => 1787227200,
                    ],
                ],
                'more' => false,
            ],
        ];

        Http::fake(['*/api/v2/payment/get_wallet_transaction_list*' => Http::response($mixed, 200)]);

        $result = app(ShopeeWalletAdCostSyncService::class)->sync(
            $store,
            now()->subDays(2)->startOfDay(),
            now()->endOfDay(),
        );

        $this->assertSame(2, $result['created']);
        $this->assertSame(2, $result['skipped']);
        $this->assertDatabaseHas('marketplace_ad_wallet_transactions', [
            'external_transaction_id' => '2001',
            'amount' => -10000,
        ]);
        $this->assertDatabaseHas('marketplace_ad_wallet_transactions', [
            'external_transaction_id' => '2002',
            'amount' => 1500,
        ]);
    }

    private function createShopeeStore(): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'SHP-AD-TEST',
            'name' => 'Toko Shopee Ad Test',
            'status' => 'active',
            'is_active' => true,
            'external_shop_id' => '12345',
            'credentials' => [
                'partner_id' => '2000000',
                'partner_key' => 'dummy_key',
                'shop_id' => '12345',
                'access_token' => 'dummy_access_token',
            ],
            'token_expires_at' => now()->addHours(2),
        ]);
    }
}
