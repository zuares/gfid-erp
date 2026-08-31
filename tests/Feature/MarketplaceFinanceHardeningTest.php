<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\ComponentDirection;
use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Models\Account;
use App\Models\Channel;
use App\Models\MarketplaceFinancialClosing;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceEscrowNormalizer;
use App\Services\Marketplace\Finance\MarketplaceFinancePostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarketplaceFinanceHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_period_blocks_new_sale_posting_without_creating_a_journal(): void
    {
        $store = $this->store();
        $this->account('1302', 'Marketplace Clearing', 'asset');
        $this->account('4101', 'Sales', 'revenue');
        $transaction = MarketplaceFinancialTransaction::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'order_sn' => 'HARDEN-CLOSED-001',
            'currency' => 'IDR',
            'gross_amount' => 100000,
            'net_amount' => 100000,
            'escrow_status' => EscrowStatus::PENDING,
            'income_status' => IncomeStatus::PENDING,
            'synced_at' => '2026-08-28 10:00:00',
        ]);
        MarketplaceFinancialClosing::create([
            'store_id' => $store->id,
            'date_basis' => 'ordered_at',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
            'scope_key' => 'store:'.$store->id.'|basis:ordered_at|from:2026-08-01|to:2026-08-31',
            'status' => 'closed',
            'snapshot' => [],
        ]);

        $this->expectException(ValidationException::class);
        app(MarketplaceFinancePostingService::class)->postSale($transaction);

        $this->assertSame(0, DB::table('journals')->count());
    }

    public function test_fee_adjustment_voucher_and_refund_keep_component_direction(): void
    {
        $normalized = app(MarketplaceEscrowNormalizer::class)->normalize([
            'order_sn' => 'HARDEN-COMPONENTS-001',
            'income' => [
                'buyer_total_amount' => 100000,
                'escrow_amount' => 85000,
                'voucher' => -1000,
                'refund' => 2500,
                'adjustment_list' => [
                    ['type' => 'seller_adjustment', 'amount' => -750, 'line_id' => 'ADJ-001'],
                ],
            ],
        ], 'HARDEN-COMPONENTS-001');

        $components = collect($normalized['components'])->keyBy('code');
        $this->assertSame('-1000.00', $components['voucher']['amount']);
        $this->assertSame(ComponentDirection::CREDIT->value, $components['voucher']['direction']);
        $this->assertSame('2500.00', $components['refund']['amount']);
        $this->assertSame(ComponentDirection::DEBIT->value, $components['refund']['direction']);
        $this->assertSame('-750.00', $components['seller_adjustment']['amount']);
        $this->assertSame(ComponentDirection::CREDIT->value, $components['seller_adjustment']['direction']);
    }

    public function test_finance_routes_require_authentication(): void
    {
        foreach ([
            '/marketplace/finance',
            '/marketplace/finance/transactions',
            '/marketplace/finance/settlements',
            '/marketplace/finance/reconciliation',
            '/marketplace/finance/fee-analysis',
        ] as $uri) {
            $this->get($uri)->assertRedirect();
        }
    }

    private function store(): Store
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);

        return Store::create([
            'channel_id' => $channel->id,
            'code' => 'HARDEN-'.fake()->unique()->numerify('#####'),
            'name' => 'Hardening Store',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    private function account(string $code, string $name, string $type): Account
    {
        return Account::create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'is_active' => true,
            'is_cash' => false,
        ]);
    }
}
