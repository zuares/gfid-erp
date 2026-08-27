<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Account;
use App\Models\MarketplaceAccountingPosting;
use App\Services\Marketplace\MarketplaceAccountingPostingService;
use App\Services\Marketplace\Ads\ShopeeWalletAdCostSyncService;
use App\Services\Marketplace\MarketplaceFinancialStatementService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Throwable;

class MarketplaceFinancialStatementController extends Controller
{
    public function index(
        Request $request,
        MarketplaceFinancialStatementService $statementService,
        MarketplaceAccountingPostingService $postingService,
    )
    {
        $filters = $this->filters($request);
        $statement = $statementService->statement($filters);
        $stores = Store::with('channel')->where('is_active', true)->orderBy('name')->get();
        $posting = $postingService->forFilters($filters);

        return view('marketplace.reports.financial_statement', compact('statement', 'stores', 'posting'));
    }

    public function postingPreview(Request $request, MarketplaceAccountingPostingService $postingService)
    {
        $filters = $this->filters($request);
        $preview = $postingService->preview($filters);
        $stores = Store::with('channel')->where('is_active', true)->orderBy('name')->get();
        $accountCodes = config('marketplace.accounting_accounts', []);
        $accountMappings = Account::query()
            ->whereIn('code', array_values($accountCodes))
            ->get(['code', 'name', 'type', 'is_active'])
            ->keyBy('code');

        return view('marketplace.reports.financial_statement_posting', compact('preview', 'stores', 'accountCodes', 'accountMappings'));
    }

    public function post(Request $request, MarketplaceAccountingPostingService $postingService)
    {
        $filters = $this->filters($request);
        $posting = $postingService->post($filters, $request->user()?->id);

        return redirect()
            ->route('marketplace.reports.financial-statement', $filters)
            ->with('status', $posting->status === 'posted'
                ? 'Posting accounting marketplace berhasil disimpan dan sudah idempotent.'
                : 'Posting accounting marketplace diproses.');
    }

    public function syncAdWallet(Request $request, ShopeeWalletAdCostSyncService $syncService)
    {
        $filters = $this->filters($request);
        $stores = Store::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['shopee', 'shp', 'SHOPEE']))
            ->when($filters['store_id'], fn ($query, $storeId) => $query->whereKey($storeId))
            ->orderBy('name')
            ->get();

        $totals = ['stores' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $from = \Carbon\Carbon::parse($filters['date_from'])->startOfDay();
        $to = \Carbon\Carbon::parse($filters['date_to'])->endOfDay();

        foreach ($stores as $store) {
            $lock = Cache::lock("marketplace:ad_wallet_sync:{$store->id}", 1800);
            if (! $lock->get()) {
                $totals['errors'][] = "{$store->name}: sync biaya iklan sedang berjalan.";
                continue;
            }

            try {
                $result = $syncService->sync($store, $from, $to);
                $totals['stores']++;
                foreach (['created', 'updated', 'skipped'] as $key) {
                    $totals[$key] += (int) ($result[$key] ?? 0);
                }
            } catch (Throwable $e) {
                report($e);
                $totals['errors'][] = "{$store->name}: {$e->getMessage()}";
            } finally {
                $lock->release();
            }
        }

        $message = sprintf(
            'Sync biaya iklan wallet selesai: %d toko, %d baru, %d diperbarui, %d dilewati.',
            $totals['stores'],
            $totals['created'],
            $totals['updated'],
            $totals['skipped'],
        );
        if ($totals['errors'] !== []) {
            $message .= ' Gagal: ' . implode(' | ', $totals['errors']);
        }

        return redirect()
            ->route('marketplace.reports.financial-statement', $filters)
            ->with('status', $message);
    }

    public function void(Request $request, MarketplaceAccountingPosting $posting, MarketplaceAccountingPostingService $postingService)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $postingService->void($posting, $data['reason']);

        return redirect()
            ->route('marketplace.reports.financial-statement', $postingService->filtersForPosting($posting))
            ->with('status', 'Posting accounting marketplace berhasil di-void dan reversal jurnal disimpan.');
    }

    public function export(Request $request, MarketplaceFinancialStatementService $statementService)
    {
        $statement = $statementService->statement($this->filters($request));
        $filename = 'marketplace-financial-statement-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($statement) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Bagian', 'Kode', 'Keterangan', 'Nilai']);

            $lines = [
                ['Laba Rugi', 'REV-GROSS', 'Omzet customer', $statement['summary']['gross_sales']],
                ['Laba Rugi', 'REV-DISC', 'Diskon seller', -$statement['summary']['seller_discount']],
                ['Laba Rugi', 'REV-NET', 'Penjualan bersih sebelum settlement', $statement['summary']['net_sales_before_settlement']],
                ['Rekonsiliasi', 'SET-FEE', 'Fee marketplace', -$statement['summary']['marketplace_fees']],
                ['Rekonsiliasi', 'SET-REFUND', 'Refund/adjustment', -$statement['summary']['refund']],
                ['Rekonsiliasi', 'SET-OTHER', 'Penyesuaian settlement lainnya', $statement['summary']['other_settlement_adjustment']],
                ['Rekonsiliasi', 'SET-PAYOUT', 'Payout aktual', $statement['summary']['payout']],
                ['Laba Rugi', 'COGS', 'HPP', -$statement['summary']['hpp']],
                ['Laba Rugi', 'GP', 'Laba kotor', $statement['summary']['gross_profit']],
                ['Laba Rugi', 'ADS', 'Biaya iklan', -$statement['summary']['ad_cost']],
                ['Rekonsiliasi Iklan', 'ADS-WALLET', 'Biaya iklan aktual wallet Shopee', -$statement['summary']['wallet_ad_cost']],
                ['Rekonsiliasi Iklan', 'ADS-DAILY', 'Ads Daily spend', -$statement['summary']['ads_daily_spend']],
                ['Rekonsiliasi Iklan', 'ADS-VAR', 'Selisih wallet vs Ads Daily', $statement['summary']['ad_cost_variance']],
                ['Laba Rugi', 'OP', 'Laba operasional setelah iklan wallet', $statement['summary']['operating_profit_after_wallet_ads'] ?? $statement['summary']['operating_profit']],
            ];

            foreach ($lines as $line) {
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $defaultFrom = now()->subDays(29)->toDateString();
        $defaultTo = now()->toDateString();

        return $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'report_scope' => ['nullable', 'in:final,include_shipped'],
            'date_basis' => ['nullable', 'in:ordered_at,settlement_time'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]) + [
            'report_scope' => $request->input('report_scope', 'final'),
            'date_basis' => $request->input('date_basis', 'ordered_at'),
            'date_from' => $request->input('date_from', $defaultFrom),
            'date_to' => $request->input('date_to', $defaultTo),
        ];
    }
}
