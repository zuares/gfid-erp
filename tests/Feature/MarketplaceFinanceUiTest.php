<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\ComponentDirection;
use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplaceFinanceUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_finance_pages_are_available_under_the_expected_routes(): void
    {
        foreach ([
            '/marketplace/finance',
            '/marketplace/finance/transactions',
            '/marketplace/finance/settlements',
            '/marketplace/finance/reconciliation',
            '/marketplace/finance/fee-analysis',
        ] as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('Marketplace Finance')
                ->assertSee('Overview');
        }
    }

    public function test_filters_are_available_for_reconciliation_and_fee_analysis(): void
    {
        $store = Store::create([
            'code' => 'UI-STORE',
            'name' => 'UI Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $transaction = MarketplaceFinancialTransaction::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'order_sn' => 'UI-ORDER-001',
            'currency' => 'IDR',
            'gross_amount' => 100000,
            'net_amount' => 95000,
            'escrow_status' => EscrowStatus::PENDING,
            'income_status' => IncomeStatus::PENDING,
        ]);
        MarketplaceFinancialComponent::create([
            'financial_transaction_id' => $transaction->id,
            'component_code' => 'admin_fee',
            'component_name' => 'Admin Fee',
            'amount' => 5000,
            'direction' => ComponentDirection::DEBIT,
            'dedupe_key' => str_repeat('u', 64),
        ]);

        $this->get('/marketplace/finance/reconciliation?order_sn=UI-ORDER-001&status=pending')
            ->assertOk()
            ->assertSee('UI-ORDER-001')
            ->assertSee('Pending');
        $this->get('/marketplace/finance/fee-analysis?store_id='.$store->id.'&order_sn=UI-ORDER-001')
            ->assertOk()
            ->assertSee('Admin Fee')
            ->assertSee('UI-ORDER-001');
    }

    public function test_get_pages_do_not_create_journals(): void
    {
        $this->get('/marketplace/finance');
        $this->get('/marketplace/finance/transactions');
        $this->get('/marketplace/finance/settlements');
        $this->get('/marketplace/finance/reconciliation');
        $this->get('/marketplace/finance/fee-analysis');

        $this->assertSame(0, DB::table('journals')->count());
    }
}
