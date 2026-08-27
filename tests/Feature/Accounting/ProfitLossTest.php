<?php

namespace Tests\Feature\Accounting;

use App\Http\Controllers\Accounting\ProfitLossController;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\MarketplaceAccountingPosting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Tests\TestCase;

class ProfitLossTest extends TestCase
{
    use RefreshDatabase;

    public function test_profit_loss_totals_all_detail_accounts(): void
    {
        $sales = $this->account('4101', 'Penjualan', 'revenue');
        $salesReturn = $this->account('4201', 'Retur Penjualan', 'revenue');
        $otherRevenue = $this->account('4301', 'Pendapatan Lain', 'revenue');
        $hpp = $this->account('5101', 'HPP', 'expense');
        $expense = $this->account('6101', 'Beban Operasional', 'expense');
        $contraExpense = $this->account('6102', 'Koreksi Beban', 'expense');
        $cash = $this->account('1111', 'Kas', 'asset');

        $journal = Journal::create([
            'date' => '2026-07-15',
            'description' => 'Profit loss test',
            'source_type' => 'cash_expense',
            'source_id' => 1,
            'posted_at' => now(),
        ]);
        $this->line($journal, $sales, 0, 1000);
        $this->line($journal, $salesReturn, 100, 0);
        $this->line($journal, $otherRevenue, 0, 50);
        $this->line($journal, $hpp, 400, 0);
        $this->line($journal, $expense, 100, 0);
        $this->line($journal, $contraExpense, 0, 20);
        $this->line($journal, $cash, 0, 1030);

        $view = $this->report([
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ]);
        $data = $view->getData();

        $this->assertSame(950.0, $data['totalRevenue']);
        $this->assertSame(400.0, $data['totalCogs']);
        $this->assertSame(80.0, $data['totalExpenses']);
        $this->assertSame(550.0, $data['grossProfit']);
        $this->assertSame(470.0, $data['netProfit']);
    }

    public function test_profit_loss_does_not_mix_marketplace_date_bases(): void
    {
        $sales = $this->account('4101', 'Penjualan', 'revenue');
        $clearing = $this->account('1302', 'Saldo Marketplace', 'asset');

        $orderedJournal = $this->marketplaceJournal(100, $sales, $clearing);
        $settlementJournal = $this->marketplaceJournal(250, $sales, $clearing);

        $this->posting($orderedJournal, 'ordered_at', 100);
        $this->posting($settlementJournal, 'settlement_time', 250);

        $view = $this->report([
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ]);
        $data = $view->getData();

        $this->assertSame('ordered_at', $data['dateBasis']);
        $this->assertSame(100.0, $data['totalRevenue']);
        $this->assertSame(100.0, $data['netProfit']);

        $view = $this->report([
            'from' => '2026-07-01',
            'to' => '2026-07-31',
            'date_basis' => 'settlement_time',
        ]);
        $data = $view->getData();

        $this->assertSame('settlement_time', $data['dateBasis']);
        $this->assertSame(250.0, $data['totalRevenue']);
        $this->assertSame(250.0, $data['netProfit']);
    }

    public function test_legacy_inventory_adjustment_is_not_reported_as_operating_expense(): void
    {
        $legacyExpense = $this->account('6101', 'Biaya Operasional Umum', 'expense');
        $cash = $this->account('1111', 'Kas', 'asset');

        $journal = Journal::create([
            'date' => '2026-07-20',
            'description' => 'Legacy inventory adjustment test',
            'source_type' => 'inventory_adjustment',
            'source_id' => 1,
            'posted_at' => now(),
        ]);
        $this->line($journal, $legacyExpense, 0, 800);
        $this->line($journal, $cash, 800, 0);

        $operatingJournal = Journal::create([
            'date' => '2026-07-20',
            'description' => 'Operating expense test',
            'source_type' => 'cash_expense',
            'source_id' => 2,
            'posted_at' => now(),
        ]);
        $this->line($operatingJournal, $legacyExpense, 125, 0);
        $this->line($operatingJournal, $cash, 0, 125);

        $data = $this->report([
            'from' => '2026-07-01',
            'to' => '2026-07-31',
        ])->getData();

        $this->assertSame(125.0, $data['totalExpenses']);
        $this->assertSame(-800.0, $data['inventoryVariance']);
        $this->assertSame(675.0, $data['netProfit']);
    }

    private function account(string $code, string $name, string $type): Account
    {
        return Account::create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'is_cash' => false,
            'is_active' => true,
        ]);
    }

    private function line(Journal $journal, Account $account, float $debit, float $credit): void
    {
        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $account->id,
            'debit' => $debit,
            'credit' => $credit,
        ]);
    }

    private function marketplaceJournal(float $amount, Account $sales, Account $clearing): Journal
    {
        $journal = Journal::create([
            'date' => '2026-07-31',
            'description' => 'Marketplace statement test',
            'source_type' => 'marketplace_financial_statement',
            'source_id' => $amount,
            'posted_at' => now(),
        ]);
        $this->line($journal, $clearing, $amount, 0);
        $this->line($journal, $sales, 0, $amount);

        return $journal;
    }

    private function posting(Journal $journal, string $dateBasis, float $amount): void
    {
        MarketplaceAccountingPosting::create([
            'date_basis' => $dateBasis,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
            'scope_key' => "store:all|basis:{$dateBasis}|from:2026-07-01|to:2026-07-31|test:{$amount}",
            'status' => 'posted',
            'journal_id' => $journal->id,
            'order_count' => 1,
            'gross_sales' => $amount,
            'payout' => 0,
            'posted_amount' => 0,
            'snapshot' => [],
        ]);
    }

    private function report(array $query): View
    {
        return app(ProfitLossController::class)->index(
            Request::create('/accounting/profit-loss', 'GET', $query),
        );
    }
}
