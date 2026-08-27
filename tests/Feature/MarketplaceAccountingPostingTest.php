<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Channel;
use App\Models\Journal;
use App\Models\MarketplaceAccountingPosting;
use App\Models\MarketplaceAdWalletTransaction;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAccountingPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_preview_post_idempotently_and_void_marketplace_settlement(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-POSTING-' . uniqid(),
        ]);
        $store = $this->seedFinancialData();

        $filters = [
            'store_id' => $store->id,
            'date_basis' => 'ordered_at',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ];

        $this->actingAs($owner)
            ->get(route('marketplace.reports.financial-statement.posting-preview', $filters))
            ->assertOk()
            ->assertSee('Posting settlement ke jurnal')
            ->assertSee('Dr = Cr Rp 305')
            ->assertSee('Yang perlu dilakukan');

        $this->assertDatabaseCount('journals', 0);

        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-statement.post', $filters))
            ->assertRedirect();

        $posting = MarketplaceAccountingPosting::firstOrFail();
        $journal = Journal::findOrFail($posting->journal_id);

        $this->assertSame('posted', $posting->status);
        $this->assertSame('marketplace_financial_statement', $journal->source_type);
        $this->assertSame(305.0, (float) $journal->lines()->sum('debit'));
        $this->assertSame(305.0, (float) $journal->lines()->sum('credit'));
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => Account::where('code', '1302')->value('id'),
            'debit' => 235,
        ]);
        $this->assertDatabaseMissing('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => Account::where('code', '1303')->value('id'),
        ]);
        $this->assertDatabaseCount('journals', 1);

        // Same scope is a no-op: no second posting or journal is created.
        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-statement.post', $filters))
            ->assertRedirect();
        $this->assertDatabaseCount('marketplace_accounting_postings', 1);
        $this->assertDatabaseCount('journals', 1);

        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-statement.postings.void', $posting), [
                'reason' => 'Koreksi settlement test',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('marketplace_accounting_postings', [
            'id' => $posting->id,
            'status' => 'void',
            'void_reason' => 'Koreksi settlement test',
        ]);
        $this->assertDatabaseHas('journals', [
            'id' => $journal->id,
        ]);
        $this->assertNotNull(Journal::findOrFail($journal->id)->voided_at);
        $this->assertDatabaseCount('journals', 2);
    }

    public function test_preview_is_blocked_when_quality_gate_is_incomplete(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-POSTING-BLOCK-' . uniqid(),
        ]);
        $store = $this->seedAccountsAndStore();
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'POSTING-INCOMPLETE-001',
            'channel_order_id' => 'POSTING-INCOMPLETE-001',
            'order_date' => '2026-08-04 10:00:00',
            'ordered_at' => '2026-08-04 10:00:00',
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'incomplete',
            'financial_issue_reason' => 'settlement_missing',
        ]);

        $response = $this->actingAs($owner)->get(route('marketplace.reports.financial-statement.posting-preview', [
            'store_id' => $store->id,
            'date_basis' => 'ordered_at',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ]));

        $response->assertSessionHasErrors('posting');
        $this->assertDatabaseCount('marketplace_accounting_postings', 0);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_preview_is_blocked_for_provisional_shipped_scope(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-POSTING-SHIPPED-' . uniqid(),
        ]);
        $store = $this->seedFinancialData();

        $response = $this->actingAs($owner)->get(route('marketplace.reports.financial-statement.posting-preview', [
            'store_id' => $store->id,
            'report_scope' => 'include_shipped',
            'date_basis' => 'ordered_at',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ]));

        $response->assertSessionHasErrors('posting');
        $this->assertDatabaseCount('marketplace_accounting_postings', 0);
        $this->assertDatabaseCount('journals', 0);
    }

    public function test_wallet_topup_uses_prepaid_ad_account_without_crediting_clearing_for_usage(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-POSTING-ADS-' . uniqid(),
        ]);
        $store = $this->seedFinancialData();
        $this->createWalletAdTransaction($store, 'SPM-TOPUP-001', 'SPM_DEDUCT', -50, '2026-08-10 10:00:00');
        $this->createWalletAdTransaction($store, 'SPM-CHARGE-001', 'paid_ads_charge', -30, '2026-08-11 10:00:00');

        $filters = [
            'store_id' => $store->id,
            'date_basis' => 'ordered_at',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ];

        $response = $this->actingAs($owner)->get(route('marketplace.reports.financial-statement.posting-preview', $filters));
        $response->assertOk()->assertSee('Top up saldo iklan');

        $preview = app(\App\Services\Marketplace\MarketplaceAccountingPostingService::class)->preview($filters);
        $lines = collect($preview['lines']);
        $accountLine = fn (string $code, string $side) => (float) $lines
            ->where('account_code', $code)
            ->sum($side);

        $this->assertSame(50.0, $accountLine('1304', 'debit'));
        $this->assertSame(80.0, $accountLine('1304', 'credit'));
        $this->assertSame(50.0, $accountLine('1302', 'credit'));
        $this->assertSame(30.0, $accountLine('6206', 'debit'));
        $this->assertSame(50.0, (float) $preview['included_in_gl']['wallet_ad_topup']);
        $this->assertSame(30.0, (float) $preview['included_in_gl']['amount']);
        $this->assertSame($preview['total_debit'], $preview['total_credit']);
    }

    public function test_non_owner_cannot_post_marketplace_accounting(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-POSTING-' . uniqid(),
        ]);

        $this->actingAs($admin)
            ->post(route('marketplace.reports.financial-statement.post'), [
                'date_basis' => 'ordered_at',
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
            ])
            ->assertForbidden();
    }

    private function seedFinancialData(): Store
    {
        $store = $this->seedAccountsAndStore();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'POSTING-001',
            'channel_order_id' => 'POSTING-001',
            'order_date' => '2026-08-04 10:00:00',
            'ordered_at' => '2026-08-04 10:00:00',
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'ready',
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'line_no' => 1,
            'item_name' => 'Posting Product',
            'model_sku' => 'POSTING-SKU',
            'qty' => 2,
            'price' => 150,
            'hpp_snapshot' => 50,
            'data_status' => 'valid',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => 'POSTING-001',
            'buyer_payment_amount' => 300,
            'seller_voucher' => 20,
            'commission_fee' => 30,
            'service_fee' => 10,
            'transaction_fee' => 5,
            'drc_adjustable_refund' => 5,
            'final_income' => 235,
            'data_status' => 'complete',
            'raw_json' => [],
        ]);

        return $store;
    }

    private function seedAccountsAndStore(): Store
    {
        foreach ([
            ['4101', 'Penjualan', 'revenue'],
            ['4201', 'Retur Penjualan', 'revenue'],
            ['1302', 'Saldo Marketplace / Clearing', 'asset'],
            ['1304', 'Saldo Iklan Marketplace / Prepaid', 'asset'],
            ['6201', 'Biaya Marketplace', 'expense'],
            ['6206', 'Biaya Iklan Marketplace', 'expense'],
        ] as [$code, $name, $type]) {
            Account::create(['code' => $code, 'name' => $name, 'type' => $type, 'is_active' => true]);
        }

        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'POSTING-' . uniqid(),
            'name' => 'Posting Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function createWalletAdTransaction(Store $store, string $id, string $type, float $amount, string $createdAt): void
    {
        MarketplaceAdWalletTransaction::create([
            'store_id' => $store->id,
            'external_transaction_id' => $id,
            'transaction_type' => $type,
            'amount' => $amount,
            'money_flow' => $amount < 0 ? 'MONEY_OUT' : 'MONEY_IN',
            'status' => 'COMPLETED',
            'transaction_created_at' => $createdAt,
            'source_payload' => [],
        ]);
    }
}
