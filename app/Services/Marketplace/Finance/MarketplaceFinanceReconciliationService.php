<?php

namespace App\Services\Marketplace\Finance;

use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\Journal;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinancialTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MarketplaceFinanceReconciliationService
{
    private const TOLERANCE = 0.01;

    private const PAGE_SIZES = [10, 25, 50];

    /**
     * Keep reconciliation bounded while reducing the number of eager-load
     * round trips for overview and summary requests.
     */
    private const RECONCILIATION_CHUNK_SIZE = 250;

    private const ORDER_REASON_KEYS = [
        'order_unmatched',
        'order_cancelled',
        'missing_escrow',
        'missing_income',
        'income_pending',
        'amount_mismatch',
        'fee_mismatch',
        'duplicate_response',
    ];

    /**
     * Read-only reconciliation over the bounded finance submodule.
     *
     * @return array{filters:array,summary:array,transactions:array<int,array>,settlements:array<int,array>}
     */
    public function reconcile(array $filters = []): array
    {
        $filters = $this->filters($filters);
        $items = [];
        $this->eachTransactionResult($filters, function (array $item) use (&$items, $filters): void {
            if ($filters['status'] !== null && $item['status'] !== $filters['status']) {
                return;
            }
            $items[] = $item;
        });

        $orderOnly = $filters['scope'] === 'order';
        $settlements = [];
        if (! $orderOnly) {
            $this->eachSettlementResult($filters, function (array $settlement) use (&$settlements, $filters): void {
                if ($filters['status'] !== null && $settlement['status'] !== $filters['status']) {
                    return;
                }
                $settlements[] = $settlement;
            });
        }

        $counts = array_fill_keys(['matched', 'mismatch', 'unmatched', 'pending', 'not_applicable'], 0);
        $reasonCounts = array_fill_keys($orderOnly
            ? self::ORDER_REASON_KEYS
            : ['missing_settlement', 'bank_unverified', 'journal_missing', 'amount_mismatch', 'fee_mismatch'], 0);
        foreach ($items as $item) {
            $counts[$item['status']]++;
            foreach (array_keys($reasonCounts) as $reason) {
                if (in_array($reason, $item['reasons'], true)) {
                    $reasonCounts[$reason]++;
                }
            }
        }
        foreach ($settlements as $settlement) {
            if ($settlement['status'] !== 'matched') {
                $counts[$settlement['status']]++;
            }
        }

        return [
            'filters' => $filters,
            'summary' => [
                'transaction_count' => count($items),
                'settlement_count' => count($settlements),
                'matched' => $counts['matched'],
                'mismatch' => $counts['mismatch'],
                'unmatched' => $counts['unmatched'],
                'pending' => $counts['pending'],
                'not_applicable' => $counts['not_applicable'],
                'reason_counts' => $reasonCounts,
                'unreconciled_amount' => round(array_sum(array_map(
                    fn (array $item): float => $this->unreconciledAmount($item, $orderOnly),
                    array_filter($items, fn (array $item): bool => $item['status'] !== 'matched'),
                )), 2),
            ],
            'transactions' => $items,
            'settlements' => $settlements,
        ];
    }

    /**
     * Reconcile only the current transaction page. The listing must not load
     * every financial transaction into memory before rendering the table.
     */
    public function paginateTransactions(array $filters = [], int $perPage = 50, string $pageName = 'page'): LengthAwarePaginator
    {
        $filters = $this->filters($filters);
        $perPage = $this->normalisePerPage($perPage);

        return $this->paginateTransactionRows($filters, $perPage, $pageName);
    }

    /**
     * Lightweight overview metrics. Rows are processed in chunks and are not
     * retained, so the dashboard remains safe for large marketplace imports.
     */
    public function overview(array $filters = []): array
    {
        $filters = $this->filters($filters);
        $summary = array_fill_keys(['matched', 'mismatch', 'unmatched', 'pending', 'not_applicable'], 0);
        $metrics = [
            'gross_sales' => 0.0,
            'total_escrow_fee' => 0.0,
            'pending_income' => 0.0,
            'to_release' => 0.0,
            'released' => 0.0,
            'settlement_received' => 0.0,
            'unreconciled_amount' => 0.0,
        ];
        $transactionCount = 0;
        $settlementCount = 0;

        $this->eachTransactionResult($filters, function (array $item) use (&$summary, &$metrics, &$transactionCount, $filters): void {
            if ($filters['status'] !== null && $item['status'] !== $filters['status']) {
                return;
            }

            $transactionCount++;
            $summary[$item['status']]++;
            $metrics['gross_sales'] += (float) ($item['gross_sales_invoice'] ?? $item['escrow_gross'] ?? 0);
            $metrics['total_escrow_fee'] += abs((float) ($item['total_components'] ?? 0));
            $incomeStatus = $item['income_status'] ?? 'unknown';
            $incomeMetric = match ($incomeStatus) {
                'pending' => 'pending_income',
                'to_release', 'released' => $incomeStatus,
                default => null,
            };
            if ($incomeMetric !== null) {
                $metrics[$incomeMetric] += (float) ($item['actual_net_income'] ?? 0);
            }
            if ($item['status'] !== 'matched') {
                $metrics['unreconciled_amount'] += abs((float) ($item['actual_net_income'] ?? 0) - (float) ($item['allocated_amount'] ?? 0));
            }
        });

        $this->eachSettlementResult($filters, function (array $settlement) use (&$summary, &$metrics, &$settlementCount, $filters): void {
            if ($filters['status'] !== null && $settlement['status'] !== $filters['status']) {
                return;
            }

            $settlementCount++;
            $metrics['settlement_received'] += (float) ($settlement['settlement_received_amount'] ?? 0);
            if ($settlement['status'] !== 'matched') {
                $summary[$settlement['status']]++;
            }
        });

        return [
            'metrics' => array_map(fn (float $value): float => round($value, 2), $metrics),
            'summary' => [
                'transaction_count' => $transactionCount,
                'settlement_count' => $settlementCount,
                ...$summary,
                'unreconciled_amount' => round($metrics['unreconciled_amount'], 2),
            ],
        ];
    }

    /**
     * Paginate settlement rows without loading every settlement in memory.
     */
    public function paginateSettlements(array $filters = [], int $perPage = 50, string $pageName = 'page'): LengthAwarePaginator
    {
        $filters = $this->filters($filters);
        $perPage = $this->normalisePerPage($perPage);

        return $this->paginateSettlementRows($filters, $perPage, $pageName);
    }

    /**
     * Reconciliation uses two independent paginators so a large transaction
     * table never forces the settlement table to render all of its rows too.
     */
    public function paginateReconciliation(array $filters = [], int $perPage = 50): array
    {
        $filters = $this->filters($filters);
        $perPage = $this->normalisePerPage($perPage);

        return [
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'transactions' => $this->paginateTransactionRows($filters, $perPage, 'reconciliation_transaction_page'),
            'settlements' => $this->paginateSettlementRows($filters, $perPage, 'reconciliation_settlement_page'),
        ];
    }

    /** Reconcile order, escrow, fee, and income without payout or bank criteria. */
    public function paginateOrderReconciliation(array $filters = [], int $perPage = 50): array
    {
        $filters['scope'] = 'order';
        $filters = $this->filters($filters);
        $perPage = $this->normalisePerPage($perPage);

        return [
            'filters' => $filters,
            'summary' => $this->summary($filters),
            'transactions' => $this->paginateTransactionRows($filters, $perPage, 'reconciliation_transaction_page'),
        ];
    }

    /**
     * Calculate reconciliation counters in bounded chunks. This keeps the
     * summary accurate without retaining all derived rows in the request.
     */
    public function summary(array $filters = []): array
    {
        $filters = $this->filters($filters);
        $counts = array_fill_keys(['matched', 'mismatch', 'unmatched', 'pending', 'not_applicable'], 0);
        $orderOnly = $filters['scope'] === 'order';
        $reasonCounts = array_fill_keys($orderOnly
            ? self::ORDER_REASON_KEYS
            : ['missing_settlement', 'bank_unverified', 'journal_missing', 'amount_mismatch', 'fee_mismatch'], 0);
        $transactionCount = 0;
        $settlementCount = 0;
        $unreconciledAmount = 0.0;

        $this->eachTransactionResult($filters, function (array $item) use (&$counts, &$reasonCounts, &$transactionCount, &$unreconciledAmount, $filters, $orderOnly): void {
            if ($filters['status'] !== null && $item['status'] !== $filters['status']) {
                return;
            }

            $transactionCount++;
            $counts[$item['status']]++;
            foreach (array_keys($reasonCounts) as $reason) {
                if (in_array($reason, $item['reasons'], true)) {
                    $reasonCounts[$reason]++;
                }
            }
            if ($item['status'] !== 'matched') {
                $unreconciledAmount += $this->unreconciledAmount($item, $orderOnly);
            }
        });

        if (! $orderOnly) {
            $this->eachSettlementResult($filters, function (array $settlement) use (&$counts, &$settlementCount, $filters): void {
                if ($filters['status'] !== null && $settlement['status'] !== $filters['status']) {
                    return;
                }

                $settlementCount++;
                if ($settlement['status'] !== 'matched') {
                    $counts[$settlement['status']]++;
                }
            });
        }

        return [
            'transaction_count' => $transactionCount,
            'settlement_count' => $settlementCount,
            'matched' => $counts['matched'],
            'mismatch' => $counts['mismatch'],
            'unmatched' => $counts['unmatched'],
            'pending' => $counts['pending'],
            'not_applicable' => $counts['not_applicable'],
            'reason_counts' => $reasonCounts,
            'unreconciled_amount' => round($unreconciledAmount, 2),
        ];
    }

    private function paginateTransactionRows(array $filters, int $perPage, string $pageName): LengthAwarePaginator
    {
        if ($filters['status'] !== null) {
            return $this->paginateFilteredTransactions($filters, $perPage, $pageName);
        }

        $paginator = $this->transactionQuery($filters)
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
        $orderOnly = $filters['scope'] === 'order';
        $journalCounts = $orderOnly ? [] : $this->activeJournalCounts($paginator->getCollection());

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn (MarketplaceFinancialTransaction $transaction): array => $this->transactionResult($transaction, $journalCounts, $orderOnly))
                ->values(),
        );

        return $paginator;
    }

    private function paginateSettlementRows(array $filters, int $perPage, string $pageName): LengthAwarePaginator
    {
        if ($filters['status'] !== null) {
            return $this->paginateFilteredSettlements($filters, $perPage, $pageName);
        }

        $paginator = $this->settlementQuery($filters)
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn (MarketplaceFinanceSettlement $settlement): array => $this->settlementResult($settlement))
                ->values(),
        );

        return $paginator;
    }

    private function paginateFilteredTransactions(array $filters, int $perPage, string $pageName): LengthAwarePaginator
    {
        $total = 0;
        $this->eachTransactionResult($filters, function (array $item) use (&$total, $filters): void {
            if ($item['status'] === $filters['status']) {
                $total++;
            }
        });

        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $start = max($total - ($page * $perPage), 0);
        $end = max($total - (($page - 1) * $perPage), 0);
        $index = 0;
        $items = [];

        $this->eachTransactionResult($filters, function (array $item) use (&$index, &$items, $filters, $start, $end): void {
            if ($item['status'] !== $filters['status']) {
                return;
            }

            if ($index >= $start && $index < $end) {
                $items[] = $item;
            }
            $index++;
        });

        usort($items, fn (array $left, array $right): int => $right['transaction_id'] <=> $left['transaction_id']);

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        return $paginator->withQueryString();
    }

    private function paginateFilteredSettlements(array $filters, int $perPage, string $pageName): LengthAwarePaginator
    {
        $total = 0;
        $this->eachSettlementResult($filters, function (array $item) use (&$total, $filters): void {
            if ($item['status'] === $filters['status']) {
                $total++;
            }
        });

        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $start = max($total - ($page * $perPage), 0);
        $end = max($total - (($page - 1) * $perPage), 0);
        $index = 0;
        $items = [];

        $this->eachSettlementResult($filters, function (array $item) use (&$index, &$items, $filters, $start, $end): void {
            if ($item['status'] !== $filters['status']) {
                return;
            }

            if ($index >= $start && $index < $end) {
                $items[] = $item;
            }
            $index++;
        });

        usort($items, fn (array $left, array $right): int => $right['settlement_id'] <=> $left['settlement_id']);

        $paginator = new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);

        return $paginator->withQueryString();
    }

    private function eachTransactionResult(array $filters, callable $callback): void
    {
        $orderOnly = $filters['scope'] === 'order';
        $this->transactionQuery($filters)->reorder('id')->chunkById(self::RECONCILIATION_CHUNK_SIZE, function (Collection $transactions) use ($callback, $orderOnly): void {
            $journalCounts = $orderOnly ? [] : $this->activeJournalCounts($transactions);
            foreach ($transactions as $transaction) {
                $callback($this->transactionResult($transaction, $journalCounts, $orderOnly));
            }
        }, 'id');
    }

    private function eachSettlementResult(array $filters, callable $callback): void
    {
        $this->settlementQuery($filters)->reorder('id')->chunkById(self::RECONCILIATION_CHUNK_SIZE, function (Collection $settlements) use ($callback): void {
            foreach ($settlements as $settlement) {
                $callback($this->settlementResult($settlement));
            }
        }, 'id');
    }

    private function transactionQuery(array $filters): Builder
    {
        $relations = [
            'salesInvoice:id,grand_total',
            'components:id,financial_transaction_id,component_code,amount,source_hash',
        ];
        if ($filters['scope'] === 'order') {
            $relations = array_merge($relations, [
                'marketplaceOrder:id,order_status,status,financial_data_status',
                'allocations:id,financial_transaction_id',
            ]);
        }
        if ($filters['scope'] !== 'order') {
            $relations = array_merge($relations, [
                'allocations:id,settlement_id,financial_transaction_id,allocated_amount',
                'allocations.settlement:id,status,amount,journal_id,raw_payload',
                'allocations.settlement.journal:id,voided_at',
                'allocations.settlement.journal.lines:id,journal_id,debit',
                'saleJournal:id,voided_at',
                'saleJournal.lines:id,journal_id,debit',
                'escrowJournal:id,voided_at',
                'escrowJournal.lines:id,journal_id,debit',
            ]);
        }

        $query = MarketplaceFinancialTransaction::query()
            ->select([
                'id',
                'store_id',
                'marketplace_order_id',
                'order_sn',
                'sales_invoice_id',
                'shipment_id',
                'currency',
                'gross_amount',
                'net_amount',
                'escrow_status',
                'income_status',
                'released_at',
                'sale_journal_id',
                'escrow_journal_id',
                'created_at',
            ])
            ->with($relations);

        if ($filters['store_id'] !== null) {
            $query->where('store_id', $filters['store_id']);
        }
        if ($filters['channel'] !== null) {
            $query->where('channel', $filters['channel']);
        }
        if ($filters['order_sn'] !== null) {
            $query->where('order_sn', $filters['order_sn']);
        }
        if ($filters['date_from'] !== null || $filters['date_to'] !== null) {
            if ($filters['date_basis'] === 'ordered_at') {
                $query->whereExists(function ($orders) use ($filters): void {
                    $orders->selectRaw('1')
                        ->from('marketplace_orders as finance_order')
                        ->where(function ($match) {
                            $match->whereColumn('finance_order.id', 'marketplace_financial_transactions.marketplace_order_id')
                                ->orWhere(function ($fallback) {
                                    $fallback->whereColumn('finance_order.store_id', 'marketplace_financial_transactions.store_id')
                                        ->where(function ($orderId) {
                                            $orderId->whereColumn('finance_order.channel_order_id', 'marketplace_financial_transactions.order_sn')
                                                ->orWhereColumn('finance_order.external_order_id', 'marketplace_financial_transactions.order_sn');
                                        });
                                });
                        });

                    if ($filters['date_from'] !== null) {
                        $orders->whereRaw('COALESCE(finance_order.ordered_at, finance_order.order_date) >= ?', [
                            CarbonImmutable::parse($filters['date_from'])->startOfDay(),
                        ]);
                    }
                    if ($filters['date_to'] !== null) {
                        $orders->whereRaw('COALESCE(finance_order.ordered_at, finance_order.order_date) <= ?', [
                            CarbonImmutable::parse($filters['date_to'])->endOfDay(),
                        ]);
                    }
                });
            } else {
                if ($filters['date_from'] !== null) {
                    $query->where($filters['date_basis'], '>=', CarbonImmutable::parse($filters['date_from'])->startOfDay());
                }
                if ($filters['date_to'] !== null) {
                    $query->where($filters['date_basis'], '<=', CarbonImmutable::parse($filters['date_to'])->endOfDay());
                }
            }
        }

        return $query->orderByDesc('id');
    }

    private function settlementQuery(array $filters): Builder
    {
        $query = MarketplaceFinanceSettlement::query()
            ->select([
                'id',
                'store_id',
                'external_settlement_id',
                'settlement_date',
                'amount',
                'status',
                'journal_id',
                'raw_payload',
            ])
            ->with([
                'allocations:id,settlement_id,financial_transaction_id,allocated_amount',
                'journal:id,voided_at',
                'journal.lines:id,journal_id,debit',
            ]);
        if ($filters['store_id'] !== null) {
            $query->where('store_id', $filters['store_id']);
        }
        if ($filters['channel'] !== null) {
            $query->where('channel', $filters['channel']);
        }
        if ($filters['date_from'] !== null) {
            $query->where('settlement_date', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== null) {
            $query->where('settlement_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('id');
    }

    private function normalisePerPage(int $perPage): int
    {
        if (in_array($perPage, self::PAGE_SIZES, true)) {
            return $perPage;
        }

        return $perPage > 50 ? 50 : 25;
    }

    private function transactionResult(MarketplaceFinancialTransaction $transaction, array $journalCounts = [], bool $orderOnly = false): array
    {
        $reasons = [];
        $pending = false;
        $cancelledOrder = $orderOnly && $transaction->marketplaceOrder?->isCancelledOrder();
        $cancelledHasFinancialActivity = $cancelledOrder && (
            abs((float) $transaction->net_amount) > self::TOLERANCE
            || $transaction->income_status === IncomeStatus::RELEASED
            || $transaction->components->isNotEmpty()
            || $transaction->sale_journal_id !== null
            || $transaction->escrow_journal_id !== null
            || $transaction->allocations->isNotEmpty()
        );
        if (! $transaction->marketplace_order_id) {
            $reasons[] = 'order_unmatched';
        }

        $escrowGross = $this->escrowGross($transaction);
        if ($transaction->escrow_status !== EscrowStatus::SYNCED && $transaction->escrow_status !== EscrowStatus::FINALIZED) {
            $reasons[] = 'missing_escrow';
            $pending = true;
        }
        if ($transaction->income_status === IncomeStatus::UNKNOWN) {
            $reasons[] = 'missing_income';
        } elseif (in_array($transaction->income_status, [IncomeStatus::PENDING, IncomeStatus::TO_RELEASE], true)) {
            $pending = true;
            if ($orderOnly) {
                $reasons[] = 'income_pending';
            }
        }

        $totalComponents = round((float) $transaction->components->sum('amount'), 2);
        $duplicateComponentResponses = $transaction->components
            ->filter(fn ($component): bool => (string) $component->source_hash !== '')
            ->groupBy(fn ($component): string => $component->component_code.'|'.$component->source_hash)
            ->contains(fn ($components): bool => $components->count() > 1);
        if ($duplicateComponentResponses) {
            $reasons[] = 'duplicate_response';
        }
        $expectedNet = $escrowGross === null ? null : round($escrowGross - $totalComponents, 2);
        $invoiceGross = $transaction->salesInvoice?->grand_total !== null ? (float) $transaction->salesInvoice->grand_total : null;
        if ($invoiceGross !== null && $this->different($invoiceGross, (float) $transaction->gross_amount)) {
            $reasons[] = 'amount_mismatch';
        }
        if ($escrowGross !== null && $this->different($escrowGross, (float) $transaction->gross_amount)) {
            $reasons[] = 'amount_mismatch';
        }
        if ($expectedNet !== null
            && $transaction->income_status === IncomeStatus::RELEASED
            && $this->different($expectedNet, (float) $transaction->net_amount)) {
            $reasons[] = 'fee_mismatch';
        }

        $allocated = 0.0;
        $receivedAllocated = 0.0;
        $journal = ['sale' => null, 'escrow' => null, 'settlement' => null];
        if (! $orderOnly) {
            $allocated = round((float) $transaction->allocations->sum('allocated_amount'), 2);
            $receivedAllocated = round((float) $transaction->allocations
                ->filter(fn ($allocation): bool => $allocation->settlement?->status === SettlementStatus::RECEIVED && ! $allocation->settlement->requiresBankVerification())
                ->sum('allocated_amount'), 2);
            if ($allocated <= self::TOLERANCE) {
                $reasons[] = 'missing_settlement';
                $pending = true;
            }
            if ($transaction->allocations->contains(fn ($allocation): bool => $allocation->settlement?->requiresBankVerification())) {
                $reasons[] = 'bank_unverified';
                $pending = true;
            }

            $journal = [
                'sale' => $this->journalResult('marketplace_sale', $transaction->id, $transaction->saleJournal, (float) $transaction->gross_amount, $reasons, $transaction->salesInvoice !== null),
                'escrow' => $this->journalResult('marketplace_escrow', $transaction->id, $transaction->escrowJournal, abs($totalComponents), $reasons, $transaction->escrow_status === EscrowStatus::FINALIZED && $transaction->components->isNotEmpty()),
                'settlement' => $this->settlementJournalResult($transaction, $reasons),
            ];
            if (($journalCounts['marketplace_sale'][$transaction->id] ?? 0) > 1
                || ($journalCounts['marketplace_escrow'][$transaction->id] ?? 0) > 1
                || ($journalCounts['marketplace_settlement'][$transaction->id] ?? 0) > 1) {
                $reasons[] = 'journal_duplicate';
            }
        }

        if ($cancelledOrder && ! $cancelledHasFinancialActivity) {
            $reasons = ['order_cancelled'];
            $pending = false;
            $status = 'not_applicable';
        } else {
            $reasons = array_values(array_unique($reasons));
            $status = $this->status($reasons, $pending);
        }

        return [
            'transaction_id' => $transaction->id,
            'order_sn' => $transaction->order_sn,
            'store_id' => $transaction->store_id,
            'status' => $status,
            'reasons' => $reasons,
            'gross_sales_invoice' => $invoiceGross,
            'escrow_gross' => $escrowGross,
            'total_components' => $totalComponents,
            'expected_net_income' => $expectedNet,
            'actual_net_income' => (float) $transaction->net_amount,
            'net_difference' => $expectedNet === null ? null : round((float) $transaction->net_amount - $expectedNet, 2),
            'income_status' => $transaction->income_status?->value,
            'allocated_amount' => $allocated,
            'settlement_received_amount' => $receivedAllocated,
            'journal_amount' => $journal,
        ];
    }

    private function settlementResult(MarketplaceFinanceSettlement $settlement): array
    {
        $allocated = round((float) $settlement->allocations->sum('allocated_amount'), 2);
        $amount = (float) $settlement->amount;
        $unallocated = round(max($amount - $allocated, 0), 2);
        $reasons = [];
        if ($settlement->allocations->isEmpty()) {
            $reasons[] = 'settlement_without_allocation';
        } elseif ($settlement->status === SettlementStatus::RECEIVED && $this->different($amount, $allocated)) {
            $reasons[] = 'amount_mismatch';
        }
        if ($settlement->requiresBankVerification()) {
            $reasons[] = 'bank_unverified';
        } elseif ($settlement->status !== SettlementStatus::RECEIVED) {
            $reasons[] = 'missing_settlement';
        } elseif (! $settlement->journal_id || ! $settlement->journal || $settlement->journal->voided_at !== null) {
            $reasons[] = 'journal_missing';
        }
        $status = $this->status($reasons, $settlement->status !== SettlementStatus::RECEIVED || $settlement->requiresBankVerification());

        return [
            'settlement_id' => $settlement->id,
            'external_settlement_id' => $settlement->external_settlement_id,
            'store_id' => $settlement->store_id,
            'status' => $status,
            'reasons' => $reasons,
            'settlement_amount' => $amount,
            'allocated_amount' => $allocated,
            'unallocated_amount' => $unallocated,
            'settlement_received_amount' => $settlement->status === SettlementStatus::RECEIVED && ! $settlement->requiresBankVerification() ? $amount : 0.0,
            'settlement_status' => $settlement->status?->value,
            'journal_amount' => $this->journalAmountFromModel($settlement->journal, $reasons),
        ];
    }

    private function settlementJournalResult(MarketplaceFinancialTransaction $transaction, array &$reasons): ?float
    {
        $received = $transaction->allocations->filter(fn ($allocation): bool => $allocation->settlement?->status === SettlementStatus::RECEIVED && ! $allocation->settlement->requiresBankVerification());
        if ($received->isEmpty()) {
            return null;
        }
        $journalAmount = 0.0;
        foreach ($received->groupBy('settlement_id') as $allocations) {
            $settlement = $allocations->first()->settlement;
            $settlementJournal = $this->journalAmountFromModel($settlement?->journal, $reasons);
            if ($settlementJournal === null) {
                $reasons[] = 'journal_missing';

                continue;
            }

            // One payout journal covers every order in the payout. Validate it
            // against the payout header, then attribute only this order's share.
            if ($this->different($settlementJournal, (float) $settlement->amount)) {
                $reasons[] = 'amount_mismatch';
            }
            $journalAmount += (float) $allocations->sum('allocated_amount');
        }

        return round($journalAmount, 2);
    }

    private function journalResult(string $sourceType, int $sourceId, ?\App\Models\Journal $journal, float $expected, array &$reasons, bool $required = true): ?float
    {
        if (! $required && ! $journal) {
            return null;
        }
        $amount = $this->journalAmountFromModel($journal, $reasons);
        if ($required && $amount === null) {
            $reasons[] = 'journal_missing';
        } elseif ($amount !== null && $this->different($amount, $expected)) {
            $reasons[] = 'amount_mismatch';
        }

        return $amount;
    }

    private function journalAmountFromModel(?Journal $journal, array &$reasons): ?float
    {
        if (! $journal || $journal->voided_at !== null) {
            return null;
        }

        return round((float) $journal->lines->sum('debit'), 2);
    }

    private function activeJournalCounts(Collection $transactions): array
    {
        $sourceTypes = ['marketplace_sale', 'marketplace_escrow', 'marketplace_settlement'];
        $transactionIds = $transactions->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $counts = array_fill_keys($sourceTypes, []);

        foreach ($sourceTypes as $sourceType) {
            foreach ($transactionIds as $transactionId) {
                $counts[$sourceType][$transactionId] = 0;
            }
        }

        if ($transactionIds->isEmpty()) {
            return $counts;
        }

        Journal::query()
            ->select('source_type', 'source_id')
            ->selectRaw('COUNT(*) as aggregate')
            ->whereNull('voided_at')
            ->whereIn('source_type', $sourceTypes)
            ->whereIn('source_id', $transactionIds)
            ->groupBy('source_type', 'source_id')
            ->get()
            ->each(function (Journal $journal) use (&$counts): void {
                $counts[$journal->source_type][(int) $journal->source_id] = (int) $journal->aggregate;
            });

        return $counts;
    }

    private function escrowGross(MarketplaceFinancialTransaction $transaction): ?float
    {
        if (! in_array($transaction->escrow_status, [EscrowStatus::SYNCED, EscrowStatus::FINALIZED], true)) {
            return null;
        }

        return (float) $transaction->gross_amount;
    }

    private function status(array $reasons, bool $pending): string
    {
        if (array_intersect($reasons, ['amount_mismatch', 'fee_mismatch', 'journal_missing', 'journal_duplicate'])) {
            return 'mismatch';
        }
        if ($reasons !== []) {
            return $pending ? 'pending' : 'unmatched';
        }

        return 'matched';
    }

    private function unreconciledAmount(array $item, bool $orderOnly): float
    {
        if ($orderOnly) {
            return $item['status'] === 'mismatch'
                ? abs((float) ($item['net_difference'] ?? 0))
                : 0.0;
        }

        return abs((float) ($item['actual_net_income'] ?? 0) - (float) ($item['allocated_amount'] ?? 0));
    }

    private function different(float $left, float $right): bool
    {
        return abs(round($left - $right, 2)) > self::TOLERANCE;
    }

    private function filters(array $filters): array
    {
        $requestedBasis = $filters['date_basis'] ?? 'ordered_at';
        $basis = in_array($requestedBasis, ['ordered_at', 'created_at', 'released_at'], true)
            ? $requestedBasis
            : 'ordered_at';

        return [
            'store_id' => isset($filters['store_id']) && $filters['store_id'] !== '' ? (int) $filters['store_id'] : null,
            'channel' => isset($filters['channel']) && trim((string) $filters['channel']) !== '' ? strtolower(trim((string) $filters['channel'])) : null,
            'scope' => ($filters['scope'] ?? null) === 'order' ? 'order' : 'full',
            'date_basis' => $basis,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'status' => isset($filters['status']) && $filters['status'] !== '' ? (string) $filters['status'] : null,
            'order_sn' => isset($filters['order_sn']) && trim((string) $filters['order_sn']) !== '' ? trim((string) $filters['order_sn']) : null,
        ];
    }
}
