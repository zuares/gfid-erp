<?php

namespace App\Services\Marketplace;

use App\Models\Account;
use App\Models\Journal;
use App\Models\MarketplaceAccountingPosting;
use App\Models\MarketplaceFinancialAuditLog;
use App\Models\MarketplaceFinancialClosing;
use App\Services\Accounting\JournalService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;

class MarketplaceAccountingPostingService
{
    public const SOURCE_TYPE = 'marketplace_financial_statement';

    public function __construct(
        private MarketplaceFinancialStatementService $statementService,
        private JournalService $journalService,
        private DatabaseManager $db,
    ) {
    }

    /**
     * Prepare a settlement journal without mutating the database.
     *
     * HPP is deliberately not included here because it may already be posted
     * by shipment COGS. Actual wallet advertising is included as a separate
     * cash mutation: Dr advertising / Cr marketplace clearing. When wallet
     * transactions are not available for a store, the statement service may
     * use Ads Daily as an explicitly-labelled fallback for the same period.
     */
    public function preview(array $filters): array
    {
        $statement = $this->statementService->statement($filters);
        $this->assertPostable($statement);

        $accounts = Account::query()
            ->whereIn('code', $this->accountCodes())
            ->where('is_active', true)
            ->get()
            ->keyBy('code');

        $missing = collect($this->accountCodes())->diff($accounts->keys())->values()->all();
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'posting' => 'COA wajib belum tersedia/aktif: ' . implode(', ', $missing) . '.',
            ]);
        }

        $summary = $statement['summary'];
        $otherAdjustment = (float) $summary['other_settlement_adjustment'];
        $adCostForGl = round((float) ($summary['ad_cost_for_gl'] ?? $summary['wallet_ad_cost'] ?? 0), 2);
        $lines = [
            $this->line($accounts[$this->accountCode('marketplace_receivable')], (float) $summary['payout'], 0),
            $this->line($accounts[$this->accountCode('sales_return')], (float) $summary['seller_discount'] + (float) $summary['refund'], 0),
            $this->line($accounts[$this->accountCode('other_fee')], (float) $summary['marketplace_fees'] + max(-$otherAdjustment, 0), 0),
            $this->line($accounts[$this->accountCode('other_fee')], 0, max($otherAdjustment, 0)),
            $this->line($accounts[$this->accountCode('sales')], 0, (float) $summary['gross_sales']),
            // Advertising is a period-level deduction from marketplace saldo.
            // A net refund is represented by the inverse entry. The statement
            // tells us whether this came from wallet actuals or Ads Daily fallback.
            $this->line($accounts[$this->accountCode('advertising')], max($adCostForGl, 0), 0),
            $this->line($accounts[$this->accountCode('marketplace_receivable')], 0, max($adCostForGl, 0)),
            $this->line($accounts[$this->accountCode('marketplace_receivable')], max(-$adCostForGl, 0), 0),
            $this->line($accounts[$this->accountCode('advertising')], 0, max(-$adCostForGl, 0)),
        ];

        $lines = array_values(array_filter($lines, fn (array $line) => $line['debit'] > 0 || $line['credit'] > 0));
        $totalDebit = round(array_sum(array_column($lines, 'debit')), 2);
        $totalCredit = round(array_sum(array_column($lines, 'credit')), 2);

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw ValidationException::withMessages([
                'posting' => 'Posting marketplace tidak balance setelah pembulatan.',
            ]);
        }

        return [
            'filters' => $statement['filters'],
            'statement' => $statement,
            'scope_key' => $this->scopeKey($statement['filters']),
            'lines' => $lines,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'accounting_scope' => 'Settlement marketplace: penjualan, retur/diskon, fee, adjustment, dan saldo marketplace / clearing.',
            'excluded_from_gl' => [
                'hpp' => (float) $summary['hpp'],
                'settlement_ad_cost' => (float) ($summary['ad_cost'] ?? 0),
                'reason' => 'HPP dapat sudah diposting dari shipment COGS. Biaya iklan settlement tidak diposting terpisah agar tidak double count; biaya iklan subledger diposting ke akun advertising.',
            ],
            'included_in_gl' => [
                'wallet_ad_cost' => (float) ($summary['wallet_ad_cost'] ?? 0),
                'amount' => $adCostForGl,
                'source' => $summary['ad_cost_for_gl_source'] ?? 'none',
                'ads_daily_fallback_spend' => (float) ($summary['ads_daily_fallback_spend'] ?? 0),
                'account_code' => $this->accountCode('advertising'),
                'account_name' => $accounts[$this->accountCode('advertising')]->name,
            ],
        ];
    }

    public function post(array $filters, ?int $userId = null): MarketplaceAccountingPosting
    {
        $preview = $this->preview($filters);
        $statement = $preview['statement'];
        $normalized = $statement['filters'];

        return $this->db->transaction(function () use ($preview, $statement, $normalized, $userId) {
            $posting = MarketplaceAccountingPosting::query()
                ->where('scope_key', $preview['scope_key'])
                ->lockForUpdate()
                ->first();

            if ($posting?->status === 'posted') {
                return $posting;
            }

            if ($this->periodIsClosed($normalized)) {
                throw ValidationException::withMessages([
                    'posting' => 'Posting diblokir: periode sudah di-close. Reopen periode melalui menu Closing & Audit terlebih dahulu.',
                ]);
            }

            $posting ??= MarketplaceAccountingPosting::create([
                'store_id' => $normalized['store_id'],
                'date_basis' => $normalized['date_basis'],
                'date_from' => $normalized['date_from'],
                'date_to' => $normalized['date_to'],
                'scope_key' => $preview['scope_key'],
                'status' => 'draft',
                'order_count' => $statement['summary']['order_count'],
                'gross_sales' => $statement['summary']['gross_sales'],
                'payout' => $statement['summary']['payout'],
                'posted_amount' => $preview['total_debit'],
                'snapshot' => $preview,
                'created_by' => $userId ?? auth()->id(),
            ]);

            if ($posting->status === 'void') {
                // Re-post setelah reopen membuat journal baru dengan source_id
                // yang sama; journal lama dan reversal tetap menjadi audit trail.
                $posting->forceFill([
                    'status' => 'draft',
                    'order_count' => $statement['summary']['order_count'],
                    'gross_sales' => $statement['summary']['gross_sales'],
                    'payout' => $statement['summary']['payout'],
                    'posted_amount' => $preview['total_debit'],
                    'snapshot' => $preview,
                    'voided_at' => null,
                    'void_reason' => null,
                ])->save();
            }

            $journal = $this->journalService->post(
                $normalized['date_to'],
                self::SOURCE_TYPE,
                $posting->id,
                $this->description($normalized),
                $preview['lines'],
                [
                    'reference_no' => 'MP-' . $posting->id,
                    'notes' => 'Posting settlement marketplace; scope=' . $preview['scope_key'],
                    'created_by' => $userId ?? auth()->id(),
                ],
            );

            $posting->forceFill([
                'status' => 'posted',
                'journal_id' => $journal->id,
                'posted_by' => $userId ?? auth()->id(),
                'posted_at' => now(),
            ])->save();

            MarketplaceFinancialAuditLog::create([
                'action' => 'posted',
                'scope_key' => $preview['scope_key'],
                'store_id' => $normalized['store_id'],
                'date_basis' => $normalized['date_basis'],
                'date_from' => $normalized['date_from'],
                'date_to' => $normalized['date_to'],
                'posting_id' => $posting->id,
                'user_id' => $userId ?? auth()->id(),
                'after_snapshot' => $posting->fresh()->toArray(),
            ]);

            return $posting->fresh();
        });
    }

    public function void(MarketplaceAccountingPosting $posting, ?string $reason = null): MarketplaceAccountingPosting
    {
        return $this->db->transaction(function () use ($posting, $reason) {
            $posting = MarketplaceAccountingPosting::query()
                ->with('journal')
                ->lockForUpdate()
                ->findOrFail($posting->id);

            if ($posting->status === 'void') {
                throw ValidationException::withMessages(['posting' => 'Posting marketplace sudah void.']);
            }
            if ($posting->status !== 'posted' || ! $posting->journal_id) {
                throw ValidationException::withMessages(['posting' => 'Hanya posting yang sudah posted yang dapat di-void.']);
            }
            if ($this->periodIsClosed([
                'store_id' => $posting->store_id,
                'date_basis' => $posting->date_basis,
                'date_from' => $posting->date_from?->toDateString(),
                'date_to' => $posting->date_to?->toDateString(),
            ])) {
                throw ValidationException::withMessages([
                    'posting' => 'Void diblokir: periode sudah di-close. Reopen periode melalui menu Closing & Audit terlebih dahulu.',
                ]);
            }

            $before = $posting->toArray();
            $this->journalService->void($posting->journal, $reason);
            $posting->forceFill([
                'status' => 'void',
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();

            MarketplaceFinancialAuditLog::create([
                'action' => 'voided',
                'scope_key' => $posting->scope_key,
                'store_id' => $posting->store_id,
                'date_basis' => $posting->date_basis,
                'date_from' => $posting->date_from,
                'date_to' => $posting->date_to,
                'posting_id' => $posting->id,
                'user_id' => auth()->id(),
                'before_snapshot' => $before,
                'after_snapshot' => $posting->fresh()->toArray(),
                'reason' => $reason,
            ]);

            return $posting->fresh();
        });
    }

    public function forFilters(array $filters): ?MarketplaceAccountingPosting
    {
        $normalized = $this->statementService->normalizeFilters($filters);

        return MarketplaceAccountingPosting::query()
            ->where('scope_key', $this->scopeKey($normalized))
            ->first();
    }

    public function filtersForPosting(MarketplaceAccountingPosting $posting): array
    {
        return [
            'store_id' => $posting->store_id,
            'date_basis' => $posting->date_basis,
            'date_from' => $posting->date_from?->toDateString(),
            'date_to' => $posting->date_to?->toDateString(),
        ];
    }

    private function assertPostable(array $statement): void
    {
        if (($statement['filters']['report_scope'] ?? 'final') !== 'final') {
            throw ValidationException::withMessages([
                'posting' => 'Posting jurnal hanya tersedia untuk scope Final. Order SHIPPED tetap berupa piutang provisional sampai statusnya COMPLETED.',
            ]);
        }

        $quality = $statement['quality'];
        if (($quality['incomplete'] ?? 0) > 0 || ($quality['unknown'] ?? 0) > 0) {
            throw ValidationException::withMessages([
                'posting' => 'Posting diblokir: masih ada order incomplete/unknown pada filter ini. Selesaikan audit data terlebih dahulu.',
            ]);
        }
        if ((int) ($statement['summary']['order_count'] ?? 0) < 1) {
            throw ValidationException::withMessages([
                'posting' => 'Tidak ada order ready untuk diposting pada periode ini.',
            ]);
        }
    }

    private function line(Account $account, float $debit, float $credit): array
    {
        return [
            'account_id' => $account->id,
            'account_code' => $account->code,
            'account_name' => $account->name,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
        ];
    }

    private function accountCodes(): array
    {
        return [
            $this->accountCode('sales'),
            $this->accountCode('sales_return'),
            $this->accountCode('marketplace_receivable'),
            $this->accountCode('other_fee'),
            $this->accountCode('advertising'),
        ];
    }

    private function accountCode(string $key): string
    {
        return (string) config('marketplace.accounting_accounts.' . $key, '6201');
    }

    private function scopeKey(array $filters): string
    {
        $key = sprintf(
            'store:%s|basis:%s|from:%s|to:%s',
            $filters['store_id'] ?: 'all',
            $filters['date_basis'],
            $filters['date_from'],
            $filters['date_to'],
        );

        return ($filters['report_scope'] ?? 'final') === 'final'
            ? $key
            : $key . '|scope:' . $filters['report_scope'];
    }

    private function description(array $filters): string
    {
        return sprintf(
            'Posting settlement marketplace %s s/d %s (%s)',
            $filters['date_from'],
            $filters['date_to'],
            $filters['store_id'] ? 'store #' . $filters['store_id'] : 'semua toko',
        );
    }

    private function periodIsClosed(array $filters): bool
    {
        $query = MarketplaceFinancialClosing::query()
            ->where('status', 'closed')
            ->where('date_basis', $filters['date_basis'])
            ->whereDate('date_from', '<=', $filters['date_to'])
            ->whereDate('date_to', '>=', $filters['date_from']);

        if ($filters['store_id'] !== null) {
            $query->where(function ($nested) use ($filters) {
                $nested->whereNull('store_id')->orWhere('store_id', $filters['store_id']);
            });
        }

        return $query->exists();
    }
}
