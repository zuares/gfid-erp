<?php

namespace App\Services\Marketplace;

use App\Models\Journal;
use App\Models\MarketplaceAccountingPosting;
use App\Models\MarketplaceFinancialAuditLog;
use App\Models\MarketplaceFinancialClosing;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;

class MarketplaceFinancialClosingService
{
    public function __construct(
        private MarketplaceFinancialStatementService $statementService,
        private MarketplaceAccountingPostingService $postingService,
        private DatabaseManager $db,
    ) {
    }

    public function audit(array $filters): array
    {
        $statement = $this->statementService->statement($filters);
        $filters = $statement['filters'];
        $scopeKey = $this->scopeKey($filters);
        $posting = MarketplaceAccountingPosting::query()
            ->with('journal.lines')
            ->where('scope_key', $scopeKey)
            ->first();
        $closing = MarketplaceFinancialClosing::query()
            ->where('scope_key', $scopeKey)
            ->first();

        $qualityPass = ($statement['quality']['incomplete'] ?? 0) === 0
            && ($statement['quality']['unknown'] ?? 0) === 0;
        $difference = abs((float) ($statement['reconciliation']['difference'] ?? 0));
        $reconciliationPass = $difference <= 0.01;
        $postingPass = $posting?->status === 'posted' && $posting->journal_id !== null;
        $journalPass = $postingPass && $this->journalIsBalanced($posting->journal);
        $hasData = (int) ($statement['summary']['order_count'] ?? 0) > 0;

        $checks = [
            [
                'key' => 'quality',
                'label' => 'Quality gate',
                'pass' => $qualityPass,
                'detail' => $qualityPass
                    ? 'Semua order eligible sudah ready; order yang belum COMPLETED tidak dihitung sebagai fakta finansial.'
                    : sprintf('%d incomplete dan %d unknown masih ditemukan.', $statement['quality']['incomplete'] ?? 0, $statement['quality']['unknown'] ?? 0),
            ],
            [
                'key' => 'reconciliation',
                'label' => 'Rekonsiliasi payout',
                'pass' => $reconciliationPass,
                'detail' => 'Selisih: Rp ' . number_format($difference, 2, ',', '.'),
            ],
            [
                'key' => 'posting',
                'label' => 'Posting accounting',
                'pass' => $postingPass,
                'detail' => $postingPass ? 'Batch posted ke jurnal umum #' . $posting->journal_id . '.' : 'Batch settlement belum posted.',
            ],
            [
                'key' => 'journal',
                'label' => 'Balance jurnal',
                'pass' => $journalPass,
                'detail' => $journalPass ? 'Total debit dan credit seimbang.' : 'Jurnal belum tersedia atau tidak balance.',
            ],
            [
                'key' => 'has_data',
                'label' => 'Data periode',
                'pass' => $hasData,
                'detail' => $hasData ? $statement['summary']['order_count'] . ' order ready.' : 'Tidak ada order ready pada periode ini.',
            ],
        ];

        $logs = MarketplaceFinancialAuditLog::query()
            ->where('scope_key', $scopeKey)
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get();

        return [
            'filters' => $filters,
            'scope_key' => $scopeKey,
            'statement' => $statement,
            'posting' => $posting,
            'closing' => $closing,
            'checks' => $checks,
            'can_close' => collect($checks)->every(fn (array $check) => $check['pass'])
                && $closing?->status !== 'closed',
            'logs' => $logs,
        ];
    }

    public function close(array $filters, ?int $userId = null): MarketplaceFinancialClosing
    {
        $audit = $this->audit($filters);
        if (! $audit['can_close']) {
            throw ValidationException::withMessages([
                'closing' => 'Periode belum dapat di-close. Selesaikan seluruh checklist audit terlebih dahulu.',
            ]);
        }

        return $this->db->transaction(function () use ($audit, $userId) {
            $this->assertNoOverlappingClosedPeriod($audit['filters'], $audit['scope_key']);

            $closing = MarketplaceFinancialClosing::query()
                ->where('scope_key', $audit['scope_key'])
                ->lockForUpdate()
                ->first();

            if ($closing?->status === 'closed') {
                return $closing;
            }

            $closing ??= new MarketplaceFinancialClosing([
                'store_id' => $audit['filters']['store_id'],
                'date_basis' => $audit['filters']['date_basis'],
                'date_from' => $audit['filters']['date_from'],
                'date_to' => $audit['filters']['date_to'],
                'scope_key' => $audit['scope_key'],
            ]);
            $closing->forceFill([
                'status' => 'closed',
                'snapshot' => $this->snapshotForAudit($audit),
                'closed_by' => $userId ?? auth()->id(),
                'closed_at' => now(),
                'reopened_by' => null,
                'reopened_at' => null,
                'reopen_reason' => null,
            ])->save();

            $this->log('closed', $audit['filters'], $audit['scope_key'], $audit['posting'], $closing, null, $userId, null, $this->snapshotForAudit($audit));

            return $closing->fresh();
        });
    }

    public function reopen(MarketplaceFinancialClosing $closing, string $reason, ?int $userId = null): MarketplaceFinancialClosing
    {
        return $this->db->transaction(function () use ($closing, $reason, $userId) {
            $closing = MarketplaceFinancialClosing::query()
                ->lockForUpdate()
                ->findOrFail($closing->id);

            if ($closing->status !== 'closed') {
                throw ValidationException::withMessages(['closing' => 'Periode ini belum berstatus closed.']);
            }

            $before = $closing->snapshot;
            $closing->forceFill([
                'status' => 'open',
                'reopened_by' => $userId ?? auth()->id(),
                'reopened_at' => now(),
                'reopen_reason' => $reason,
            ])->save();

            $this->log(
                'reopened',
                $this->filtersForClosing($closing),
                $closing->scope_key,
                null,
                $closing,
                $reason,
                $userId,
                $before,
                $closing->fresh()->toArray(),
            );

            return $closing->fresh();
        });
    }

    public function filtersForClosing(MarketplaceFinancialClosing $closing): array
    {
        return [
            'store_id' => $closing->store_id,
            'date_basis' => $closing->date_basis,
            'date_from' => $closing->date_from?->toDateString(),
            'date_to' => $closing->date_to?->toDateString(),
        ];
    }

    public function isPeriodClosed(array $filters): bool
    {
        $filters = $this->statementService->statement($filters)['filters'];
        return MarketplaceFinancialClosing::query()
            ->where('status', 'closed')
            ->where('date_basis', $filters['date_basis'])
            ->whereDate('date_from', '<=', $filters['date_to'])
            ->whereDate('date_to', '>=', $filters['date_from'])
            ->where(function ($query) use ($filters) {
                if ($filters['store_id'] !== null) {
                    $query->whereNull('store_id')->orWhere('store_id', $filters['store_id']);
                }
            })
            ->exists();
    }

    private function assertNoOverlappingClosedPeriod(array $filters, string $scopeKey): void
    {
        $query = MarketplaceFinancialClosing::query()
            ->where('status', 'closed')
            ->where('date_basis', $filters['date_basis'])
            ->where('scope_key', '!=', $scopeKey)
            ->whereDate('date_from', '<=', $filters['date_to'])
            ->whereDate('date_to', '>=', $filters['date_from']);

        if ($filters['store_id'] !== null) {
            $query->where(function ($nested) use ($filters) {
                $nested->whereNull('store_id')->orWhere('store_id', $filters['store_id']);
            });
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'closing' => 'Periode overlap dengan closing yang sudah closed. Pilih scope yang belum dikunci.',
            ]);
        }
    }

    private function journalIsBalanced(?Journal $journal): bool
    {
        if (! $journal) {
            return false;
        }

        return abs((float) $journal->lines->sum('debit') - (float) $journal->lines->sum('credit')) <= 0.01
            && $journal->lines->count() >= 2
            && $journal->voided_at === null;
    }

    private function log(
        string $action,
        array $filters,
        string $scopeKey,
        ?MarketplaceAccountingPosting $posting,
        MarketplaceFinancialClosing $closing,
        ?string $reason,
        ?int $userId,
        ?array $before,
        ?array $after,
    ): void {
        MarketplaceFinancialAuditLog::create([
            'action' => $action,
            'scope_key' => $scopeKey,
            'store_id' => $filters['store_id'],
            'date_basis' => $filters['date_basis'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'posting_id' => $posting?->id,
            'closing_id' => $closing->id,
            'user_id' => $userId ?? auth()->id(),
            'before_snapshot' => $before,
            'after_snapshot' => $after,
            'reason' => $reason,
        ]);
    }

    private function scopeKey(array $filters): string
    {
        return sprintf(
            'store:%s|basis:%s|from:%s|to:%s',
            $filters['store_id'] ?: 'all',
            $filters['date_basis'],
            $filters['date_from'],
            $filters['date_to'],
        );
    }

    private function snapshotForAudit(array $audit): array
    {
        return [
            'filters' => $audit['filters'],
            'scope_key' => $audit['scope_key'],
            'checks' => $audit['checks'],
            'can_close' => $audit['can_close'],
            'quality' => $audit['statement']['quality'],
            'reconciliation' => $audit['statement']['reconciliation'],
            'summary' => [
                'order_count' => $audit['statement']['summary']['order_count'],
                'gross_sales' => $audit['statement']['summary']['gross_sales'],
                'payout' => $audit['statement']['summary']['payout'],
                'gross_profit' => $audit['statement']['summary']['gross_profit'],
                'operating_profit' => $audit['statement']['summary']['operating_profit'],
            ],
            'posting_id' => $audit['posting']?->id,
            'journal_id' => $audit['posting']?->journal_id,
        ];
    }

}
