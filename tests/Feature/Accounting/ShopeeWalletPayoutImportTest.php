<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Channel;
use App\Models\MarketplacePayout;
use App\Models\Store;
use App\Services\Accounting\ShopeeWalletPayoutImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopeeWalletPayoutImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_menggunakan_fixture_json_hanya_menyimpan_withdrawal_dan_idempotent(): void
    {
        $store = $this->createShopeeStore();
        $bank = Account::create([
            'code'      => '1101',
            'name'      => 'Bank Test',
            'type'      => 'asset',
            'is_cash'   => true,
            'is_active' => true,
        ]);

        $fixturePath = base_path('tests/Fixtures/shopee/wallet_transaction_list.json');
        $fixture = json_decode((string) file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);

        Http::fake([
            '*/api/v2/payment/get_wallet_transaction_list*' => Http::response($fixture, 200),
        ]);

        $importer = app(ShopeeWalletPayoutImportService::class);
        $from = now()->subDays(14)->startOfDay();
        $to = now()->endOfDay();

        $first = $importer->import($store, $from, $to, $bank->id);

        $this->assertSame([
            'created' => 1,
            'skipped' => 1,
            'skippedExisting' => 0,
            'bankConflicts' => 0,
            'skippedInvalid' => 1,
            'skippedInvalidReasons' => ['non_withdrawal_type' => 1],
        ], $first);
        $this->assertDatabaseHas('marketplace_payouts', [
            'store_id'                => $store->id,
            'external_transaction_id' => '987654321',
            'source'                  => 'shopee_wallet',
            'amount'                  => 125000,
            'status'                  => 'draft',
        ]);
        $this->assertDatabaseCount('marketplace_payouts', 1);

        $second = $importer->import($store, $from, $to, $bank->id);

        $this->assertSame([
            'created' => 0,
            'skipped' => 2,
            'skippedExisting' => 1,
            'bankConflicts' => 0,
            'skippedInvalid' => 1,
            'skippedInvalidReasons' => ['non_withdrawal_type' => 1],
        ], $second);
        $this->assertDatabaseCount('marketplace_payouts', 1);

        $otherBank = Account::create([
            'code'      => '1102',
            'name'      => 'Bank Test Lain',
            'type'      => 'asset',
            'is_cash'   => true,
            'is_active' => true,
        ]);

        $third = $importer->import($store, $from, $to, $otherBank->id);

        $this->assertSame([
            'created' => 0,
            'skipped' => 2,
            'skippedExisting' => 1,
            'bankConflicts' => 1,
            'skippedInvalid' => 1,
            'skippedInvalidReasons' => ['non_withdrawal_type' => 1],
        ], $third);
        $this->assertDatabaseHas('marketplace_payouts', [
            'external_transaction_id' => '987654321',
            'bank_account_id' => $bank->id,
        ]);
        $this->assertDatabaseMissing('marketplace_payouts', [
            'external_transaction_id' => '987654321',
            'bank_account_id' => $otherBank->id,
        ]);

        Http::assertSent(function ($request) use ($from, $to) {
            $body = $request->data();

            return $request->method() === 'POST'
                && str_contains($request->url(), '/api/v2/payment/get_wallet_transaction_list')
                && ($body['page_no'] ?? null) === 0
                && ($body['page_size'] ?? null) === 100
                && ($body['create_time_from'] ?? null) === $from->timestamp
                && ($body['create_time_to'] ?? null) === $to->timestamp
                && ($body['money_flow'] ?? null) === 'MONEY_OUT'
                && ($body['transaction_type'] ?? null) === '201'
                && ($body['transaction_tab_type'] ?? null) === 'wallet_withdrawals';
        });
    }

    private function createShopeeStore(): Store
    {
        $channel = Channel::firstOrCreate(['code' => 'shopee'], ['name' => 'Shopee']);

        return Store::create([
            'channel_id'       => $channel->id,
            'code'             => 'SHP-TEST',
            'name'             => 'Toko Shopee Test',
            'status'           => 'active',
            'is_active'        => true,
            'external_shop_id' => '12345',
            'credentials'      => [
                'partner_id'   => '2000000',
                'partner_key'  => 'dummy_key',
                'shop_id'      => '12345',
                'access_token' => 'dummy_access_token',
            ],
            'token_expires_at' => now()->addHours(2),
        ]);
    }
}
