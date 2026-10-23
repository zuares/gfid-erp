<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Finance\Enums\ComponentDirection;
use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinanceSyncRun;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use Carbon\CarbonImmutable;
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

    public function test_overview_handles_pending_income_status(): void
    {
        $store = Store::create([
            'code' => 'OVERVIEW-PENDING-STORE',
            'name' => 'Overview Pending Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        MarketplaceFinancialTransaction::create([
            'store_id' => $store->id,
            'channel' => 'tiktok',
            'order_sn' => 'OVERVIEW-PENDING-001',
            'currency' => 'IDR',
            'gross_amount' => 100000,
            'net_amount' => 95000,
            'escrow_status' => EscrowStatus::PENDING,
            'income_status' => IncomeStatus::PENDING,
        ]);

        $this->get('/marketplace/finance')
            ->assertOk()
            ->assertSee('Pending Income');
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
        $this->get('/marketplace/finance/fee-analysis?store_id='.$store->id.'&date_basis=created_at&order_sn=UI-ORDER-001')
            ->assertOk()
            ->assertSee('Admin Fee')
            ->assertSee('UI-ORDER-001');
    }

    public function test_cancelled_order_is_filterable_as_not_applicable_in_order_reconciliation(): void
    {
        $store = Store::create([
            'code' => 'UI-CANCELLED-STORE',
            'name' => 'UI Cancelled Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'UI-CANCELLED-ORDER-001',
            'channel_order_id' => 'UI-CANCELLED-ORDER-001',
            'order_date' => '2026-10-10',
            'ordered_at' => '2026-10-10 09:00:00',
            'order_status' => 'CANCELLED',
            'status' => 'cancelled',
            'financial_data_status' => 'not_applicable',
            'total_amount' => 100000,
            'currency' => 'IDR',
        ]);
        MarketplaceFinancialTransaction::create([
            'store_id' => $store->id,
            'marketplace_order_id' => $order->id,
            'channel' => 'shopee',
            'order_sn' => $order->channel_order_id,
            'currency' => 'IDR',
            'gross_amount' => 100000,
            'net_amount' => 0,
            'escrow_status' => EscrowStatus::SYNCED,
            'income_status' => IncomeStatus::PENDING,
        ]);

        $this->get('/marketplace/finance/reconciliation?store_id='.$store->id.'&status=not_applicable')
            ->assertOk()
            ->assertSee('UI-CANCELLED-ORDER-001')
            ->assertSee('Tidak berlaku')
            ->assertSee('Order dibatalkan; income tidak berlaku');
    }

    public function test_finance_date_filter_defaults_to_marketplace_order_date(): void
    {
        $store = Store::create([
            'code' => 'ORDER-DATE-STORE',
            'name' => 'Order Date Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-DATE-001',
            'channel_order_id' => 'ORDER-DATE-001',
            'order_status' => 'COMPLETED',
            'order_date' => '2026-10-09 12:00:00',
            'ordered_at' => '2026-10-09 12:00:00',
            'total_amount' => 120000,
        ]);
        $transaction = MarketplaceFinancialTransaction::create([
            'store_id' => $store->id,
            'marketplace_order_id' => $order->id,
            'channel' => 'shopee',
            'order_sn' => 'ORDER-DATE-001',
            'currency' => 'IDR',
            'gross_amount' => 120000,
            'net_amount' => 100000,
            'escrow_status' => EscrowStatus::FINALIZED,
            'income_status' => IncomeStatus::RELEASED,
            'released_at' => '2026-09-20 12:00:00',
        ]);
        $transaction->forceFill([
            'created_at' => '2026-09-15 12:00:00',
            'updated_at' => '2026-09-15 12:00:00',
        ])->save();
        MarketplaceFinancialComponent::create([
            'financial_transaction_id' => $transaction->id,
            'component_code' => 'admin_fee',
            'component_name' => 'Admin Fee',
            'amount' => 20000,
            'direction' => ComponentDirection::DEBIT,
            'dedupe_key' => str_repeat('o', 64),
        ]);

        $query = '?store_id='.$store->id.'&date_from=2026-10-01&date_to=2026-10-31';

        $this->get('/marketplace/finance'.$query)
            ->assertOk()
            ->assertSee('Tanggal order')
            ->assertSee('ORDER-DATE-001');
        $this->get('/marketplace/finance/transactions'.$query)
            ->assertOk()
            ->assertSee('ORDER-DATE-001');
        $this->get('/marketplace/finance/fee-analysis'.$query)
            ->assertOk()
            ->assertSee('Admin Fee')
            ->assertSee('ORDER-DATE-001');

        $this->get('/marketplace/finance/transactions'.$query.'&date_basis=created_at')
            ->assertOk()
            ->assertSee('Tidak ada transaksi pada filter ini')
            ->assertSee('Transaksi dicatat: 2026-10-01 – 2026-10-31')
            ->assertDontSee('ORDER-DATE-001');
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

    public function test_transactions_are_paginated_and_render_a_transaction_sidebar(): void
    {
        $store = Store::create([
            'code' => 'PAGINATION-STORE',
            'name' => 'Pagination Store',
            'status' => 'active',
            'is_active' => true,
        ]);

        for ($index = 1; $index <= 51; $index++) {
            MarketplaceFinancialTransaction::create([
                'store_id' => $store->id,
                'channel' => 'shopee',
                'order_sn' => 'PAGINATION-ORDER-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'currency' => 'IDR',
                'gross_amount' => 100000,
                'net_amount' => 95000,
                'escrow_status' => EscrowStatus::PENDING,
                'income_status' => IncomeStatus::PENDING,
            ]);
        }

        $this->get('/marketplace/finance/transactions?per_page=25&date_basis=created_at')
            ->assertOk()
            ->assertSee('Filter transaksi')
            ->assertSee('25 transaksi')
            ->assertSee('Menampilkan 1–25 dari 51 transaksi')
            ->assertDontSee('PAGINATION-ORDER-001');

        $this->get('/marketplace/finance/transactions?per_page=100&date_basis=created_at')
            ->assertOk()
            ->assertSee('Menampilkan 1–50 dari 51 transaksi');
    }

    public function test_settlements_render_a_settlement_sidebar(): void
    {
        $this->get('/marketplace/finance/settlements?date_from=2026-10-01&date_to=2026-10-22')
            ->assertOk()
            ->assertSee('Filter settlement')
            ->assertSee('Periode settlement')
            ->assertSee('Belum ada payout finance yang tersinkron')
            ->assertSee('1 aktif');
    }

    public function test_wallet_withdrawal_is_labeled_as_unverified_bank_cash_on_finance_pages(): void
    {
        $store = Store::create([
            'code' => 'UI-WALLET-STORE',
            'name' => 'UI Wallet Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        MarketplaceFinanceSettlement::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'external_settlement_id' => 'UI-WALLET-WITHDRAWAL',
            'settlement_date' => '2026-10-10',
            'amount' => 2000000,
            'currency' => 'IDR',
            'status' => SettlementStatus::WITHDRAWN,
            'raw_payload' => ['transaction_type' => 'WITHDRAWAL_CREATED'],
        ]);
        MarketplaceFinanceSettlement::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'external_settlement_id' => 'UI-LEGACY-WALLET',
            'settlement_date' => '2026-10-10',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => SettlementStatus::RECEIVED,
            'raw_payload' => ['transaction_type' => 'WITHDRAWAL_CREATED'],
        ]);
        MarketplaceFinanceSettlement::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'external_settlement_id' => 'UI-PAYOUT-COMPLETED',
            'settlement_date' => '2026-10-10',
            'amount' => 95000,
            'currency' => 'IDR',
            'status' => SettlementStatus::PAYOUT_COMPLETED,
            'raw_payload' => ['source_kind' => 'marketplace_payout', 'payout_info' => ['status' => 'COMPLETED']],
        ]);

        $query = '?store_id='.$store->id.'&date_from=2026-10-01&date_to=2026-10-22';
        $settlementsPage = $this->get('/marketplace/finance/settlements'.$query)
            ->assertOk()
            ->assertSee('UI-WALLET-WITHDRAWAL')
            ->assertSee('UI-LEGACY-WALLET')
            ->assertSee('UI-PAYOUT-COMPLETED')
            ->assertSee('Ditarik dari wallet')
            ->assertSee('Payout selesai · bank belum dicek')
            ->assertSee('Bank tujuan');
        $this->assertSame(3, substr_count($settlementsPage->getContent(), 'Ditarik dari wallet'));

        $this->get('/marketplace/finance/reconciliation'.$query)
            ->assertOk()
            ->assertSee('Mode rekonsiliasi per order')
            ->assertDontSee('Dana masuk bank belum terverifikasi')
            ->assertDontSee('UI-WALLET-WITHDRAWAL');
    }

    public function test_reconciliation_is_explicitly_limited_to_order_values(): void
    {
        $this->get('/marketplace/finance/reconciliation')
            ->assertOk()
            ->assertSee('Alasan yang perlu ditindaklanjuti')
            ->assertSee('Gross · Fee · Net')
            ->assertSee('Payout, alokasi settlement, mutasi bank, serta jurnal tidak memengaruhi hasil halaman ini')
            ->assertDontSee('Detail settlement');
    }

    public function test_fee_analysis_explains_unmapped_settlement_adjustment(): void
    {
        $store = Store::create([
            'code' => 'FEE-UNMAPPED-STORE',
            'name' => 'Fee Unmapped Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $transaction = MarketplaceFinancialTransaction::create([
            'store_id' => $store->id,
            'channel' => 'shopee',
            'order_sn' => 'FEE-UNMAPPED-ORDER-001',
            'currency' => 'IDR',
            'gross_amount' => 120000,
            'net_amount' => 100000,
            'escrow_status' => EscrowStatus::FINALIZED,
            'income_status' => IncomeStatus::RELEASED,
        ]);
        MarketplaceFinancialComponent::create([
            'financial_transaction_id' => $transaction->id,
            'component_code' => 'unmapped_adjustment',
            'component_name' => 'Unmapped Settlement Adjustment',
            'amount' => 20000,
            'direction' => ComponentDirection::DEBIT,
            'dedupe_key' => str_repeat('m', 64),
        ]);

        $this->get('/marketplace/finance/fee-analysis?store_id='.$store->id.'&date_basis=created_at')
            ->assertOk()
            ->assertSee('Selisih settlement belum terpetakan')
            ->assertSee('Sisa dari gross − net setelah komponen yang dikenali.')
            ->assertSee('Bukan otomatis biaya marketplace; perlu ditelusuri.');
    }

    public function test_fee_analysis_aggregates_in_sql_and_paginates_orders(): void
    {
        $store = Store::create([
            'code' => 'FEE-PAGINATION-STORE',
            'name' => 'Fee Pagination Store',
            'status' => 'active',
            'is_active' => true,
        ]);

        for ($index = 1; $index <= 51; $index++) {
            $transaction = MarketplaceFinancialTransaction::create([
                'store_id' => $store->id,
                'channel' => 'shopee',
                'order_sn' => 'FEE-PAGINATION-ORDER-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
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
                'dedupe_key' => hash('sha256', 'fee-pagination-'.$index),
            ]);
        }

        $this->get('/marketplace/finance/fee-analysis?date_basis=created_at')
            ->assertOk()
            ->assertSee('Menampilkan 1–50 dari 51 order')
            ->assertSee('Admin Fee');
    }

    public function test_finance_pages_show_order_sync_health_and_audit_detail(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-23 15:25:00'));
        $run = MarketplaceFinanceSyncRun::create([
            'trigger' => 'order_schedule',
            'status' => 'success',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now()->subMinutes(4),
            'output' => json_encode([
                'scope' => 'order_only',
                'store_name' => 'Greatfit.id',
                'sources' => ['order_settlements', 'income_estimates', 'mp_incomes'],
                'mode' => 'apply',
                'preflight' => [
                    'scanned' => 739,
                    'transactions_created' => 2,
                    'updated' => 1,
                    'components_created' => 4,
                    'unmatched' => 0,
                    'duplicates' => 0,
                    'errors' => 0,
                ],
                'apply' => [
                    'scanned' => 739,
                    'transactions_created' => 2,
                    'updated' => 1,
                    'components_created' => 4,
                    'unmatched' => 0,
                    'duplicates' => 0,
                    'errors' => 0,
                ],
                'verification' => [
                    'scanned' => 739,
                    'transactions_created' => 0,
                    'updated' => 0,
                    'components_created' => 0,
                    'unmatched' => 0,
                    'duplicates' => 0,
                    'errors' => 0,
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->get('/marketplace/finance/transactions')
            ->assertOk()
            ->assertSee('Sinkronisasi sehat')
            ->assertSee('Greatfit.id')
            ->assertSeeInOrder(['Sumber dipindai', '739', 'Order baru', '2', 'Order berubah', '1'])
            ->assertSee('Audit run #'.$run->id)
            ->assertSee('tidak memproses payout, penerimaan bank, alokasi, atau jurnal');

        $this->get('/marketplace/finance/sync-runs/'.$run->id)
            ->assertOk()
            ->assertSee('Audit run #'.$run->id)
            ->assertSee('Status sukses bukan bukti payout diterima')
            ->assertSee('Preflight')
            ->assertSee('Verifikasi')
            ->assertSee('order_settlements')
            ->assertSee('mp_incomes');
    }

    public function test_finance_health_warns_when_order_sync_is_older_than_thirty_minutes(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-23 16:00:00'));
        MarketplaceFinanceSyncRun::create([
            'trigger' => 'order_schedule',
            'status' => 'success',
            'started_at' => now()->subMinutes(32),
            'finished_at' => now()->subMinutes(31),
            'output' => json_encode([
                'store_name' => 'Greatfit.id',
                'apply' => ['scanned' => 739, 'transactions_created' => 0, 'updated' => 0],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->get('/marketplace/finance/reconciliation')
            ->assertOk()
            ->assertSee('Sinkronisasi terlambat')
            ->assertSee('Belum ada run order-only yang selesai dalam 30 menit terakhir.');
    }

    public function test_finance_sync_audit_rejects_non_order_runs(): void
    {
        $run = MarketplaceFinanceSyncRun::create([
            'trigger' => 'manual 30 hr · cek dulu',
            'status' => 'success',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $this->get('/marketplace/finance/sync-runs/'.$run->id)->assertNotFound();
    }
}
