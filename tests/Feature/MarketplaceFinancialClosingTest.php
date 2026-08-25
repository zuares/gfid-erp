<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Channel;
use App\Models\Journal;
use App\Models\MarketplaceFinancialAuditLog;
use App\Models\MarketplaceFinancialClosing;
use App\Models\MarketplaceAccountingPosting;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFinancialClosingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_close_audited_period_lock_overlap_and_reopen_with_reason(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-CLOSING-' . uniqid(),
        ]);
        $store = $this->seedFinancialData();
        $filters = $this->filters($store, '2026-08-01', '2026-08-31');

        $this->actingAs($owner)
            ->get(route('marketplace.reports.financial-closing', $filters))
            ->assertOk()
            ->assertSee('Tutup periode keuangan', false)
            ->assertSee('Posting accounting')
            ->assertSee('Periode belum siap dikunci')
            ->assertSee('Langkah 3 dari 3');

        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-statement.post', $filters))
            ->assertRedirect();
        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-closing.close', $filters))
            ->assertRedirect();

        $closing = MarketplaceFinancialClosing::firstOrFail();
        $this->assertSame('closed', $closing->status);
        $this->assertDatabaseHas('marketplace_financial_audit_logs', [
            'action' => 'posted',
            'posting_id' => $closing->snapshot['posting_id'],
        ]);
        $this->assertDatabaseHas('marketplace_financial_audit_logs', [
            'action' => 'closed',
            'closing_id' => $closing->id,
        ]);

        $posting = MarketplaceAccountingPosting::findOrFail($closing->snapshot['posting_id']);
        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-statement.postings.void', $posting), [
                'reason' => 'Percobaan void setelah close',
            ])
            ->assertSessionHasErrors('posting');

        // A new, overlapping scope must be rejected while the period is closed.
        $overlap = $this->filters($store, '2026-08-01', '2026-08-30');
        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-statement.post', $overlap))
            ->assertSessionHasErrors('posting');

        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-closing.reopen', $closing), [
                'reason' => 'Koreksi settlement setelah audit owner',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('marketplace_financial_closings', [
            'id' => $closing->id,
            'status' => 'open',
            'reopen_reason' => 'Koreksi settlement setelah audit owner',
        ]);
        $this->assertDatabaseHas('marketplace_financial_audit_logs', [
            'action' => 'reopened',
            'closing_id' => $closing->id,
            'reason' => 'Koreksi settlement setelah audit owner',
        ]);

        // After reopen, void and re-post the same scope as a new journal revision.
        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-statement.postings.void', $posting), [
                'reason' => 'Koreksi settlement setelah reopen',
            ])
            ->assertRedirect();
        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-statement.post', $filters))
            ->assertRedirect();

        $this->assertSame('posted', $posting->fresh()->status);
        $this->assertSame(3, Journal::count(), 'Original, reversal, and revised journal must remain auditable.');
        $this->assertDatabaseHas('marketplace_financial_audit_logs', [
            'action' => 'voided',
            'reason' => 'Koreksi settlement setelah reopen',
        ]);
    }

    public function test_closing_is_blocked_until_quality_and_posting_checks_pass(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-CLOSING-BLOCK-' . uniqid(),
        ]);
        $store = $this->seedAccountsAndStore();
        MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'CLOSING-INCOMPLETE-001',
            'channel_order_id' => 'CLOSING-INCOMPLETE-001',
            'order_date' => '2026-08-04 10:00:00',
            'ordered_at' => '2026-08-04 10:00:00',
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'incomplete',
        ]);

        $filters = $this->filters($store, '2026-08-01', '2026-08-31');
        $this->actingAs($owner)
            ->get(route('marketplace.reports.financial-closing', $filters))
            ->assertOk()
            ->assertSee('financial-quality?store_id=' . $store->id . '&amp;status=incomplete', false);
        $this->actingAs($owner)
            ->post(route('marketplace.reports.financial-closing.close', $filters))
            ->assertSessionHasErrors('closing');

        $this->assertDatabaseCount('marketplace_financial_closings', 0);
    }

    public function test_non_owner_cannot_access_or_close_period(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-CLOSING-' . uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('marketplace.reports.financial-closing'))
            ->assertForbidden();
        $this->actingAs($admin)
            ->post(route('marketplace.reports.financial-closing.close', [
                'date_basis' => 'ordered_at',
                'date_from' => '2026-08-01',
                'date_to' => '2026-08-31',
            ]))
            ->assertForbidden();
    }

    private function filters(Store $store, string $from, string $to): array
    {
        return [
            'store_id' => $store->id,
            'date_basis' => 'ordered_at',
            'date_from' => $from,
            'date_to' => $to,
        ];
    }

    private function seedFinancialData(): Store
    {
        $store = $this->seedAccountsAndStore();
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'CLOSING-001',
            'channel_order_id' => 'CLOSING-001',
            'order_date' => '2026-08-04 10:00:00',
            'ordered_at' => '2026-08-04 10:00:00',
            'order_status' => 'COMPLETED',
            'financial_data_status' => 'ready',
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'line_no' => 1,
            'item_name' => 'Closing Product',
            'model_sku' => 'CLOSING-SKU',
            'qty' => 2,
            'price' => 150,
            'hpp_snapshot' => 50,
            'data_status' => 'valid',
        ]);
        MarketplaceOrderSettlement::create([
            'store_id' => $store->id,
            'order_id' => $order->id,
            'channel_order_id' => 'CLOSING-001',
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
            ['1302', 'Piutang Marketplace', 'asset'],
            ['6201', 'Biaya Marketplace', 'expense'],
        ] as [$code, $name, $type]) {
            Account::create(['code' => $code, 'name' => $name, 'type' => $type, 'is_active' => true]);
        }

        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'CLOSING-' . uniqid(),
            'name' => 'Closing Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}
