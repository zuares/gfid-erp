<?php

namespace App\Services\Marketplace\Finance;

use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\Journal;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinancialTransaction;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceFinanceReconciliationService
{
    private const TOLERANCE = 0.01;

    /**
     * Read-only reconciliation over the bounded finance submodule.
     *
     * @return array{filters:array,summary:array,transactions:array<int,array>,settlements:array<int,array>}
     */
    public function reconcile(array $filters = []): array
    {
        $filters = $this->filters($filters);
        $transactions = $this->transactionQuery($filters)->get();
        $items = [];
        foreach ($transactions as $transaction) {
            $item = $this->transactionResult($transaction);
            if ($filters['status'] !== null && $item['status'] !== $filters['status']) {
                continue;
            }
            $items[] = $item;
        }

        $settlements = $this->settlementQuery($filters)->get()
            ->map(fn (MarketplaceFinanceSettlement $settlement): array => $this->settlementResult($settlement))
            ->filter(fn (array $settlement): bool => $filters['status'] === null || $settlement['status'] === $filters['status'])
            ->values()
            ->all();

        $counts = array_fill_keys(['matched', 'mismatch', 'unmatched', 'pending'], 0);
        foreach ($items as $item) {
            $counts[$item['status']]++;
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
                'unreconciled_amount' => round(array_sum(array_map(
                    fn (array $item): float => abs((float) ($item['actual_net_income'] ?? 0) - (float) ($item['allocated_amount'] ?? 0)),
                    array_filter($items, fn (array $item): bool => $item['status'] !== 'matched'),
                )), 2),
            ],
            'transactions' => $items,
            'settlements' => $settlements,
        ];
    }

    private function transactionQuery(array $filters): Builder
    {
        $query = MarketplaceFinancialTransaction::query()->with([
            'marketplaceOrder',
            'salesInvoice',
            'shipment',
            'components',
            'allocations.settlement',
            'saleJournal.lines',
            'escrowJournal.lines',
        ]);

        if ($filters['store_id'] !== null) {
            $query->where('store_id', $filters['store_id']);
        }
        if ($filters['order_sn'] !== null) {
            $query->where('order_sn', $filters['order_sn']);
        }
        if ($filters['date_from'] !== null) {
            $query->whereDate($filters['date_basis'], '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== null) {
            $query->whereDate($filters['date_basis'], '<=', $filters['date_to']);
        }

        return $query->orderBy('id');
    }

    private function settlementQuery(array $filters): Builder
    {
        $query = MarketplaceFinanceSettlement::query()->with('allocations');
        if ($filters['store_id'] !== null) {
            $query->where('store_id', $filters['store_id']);
        }
        if ($filters['date_from'] !== null) {
            $query->whereDate('settlement_date', '>=', $filters['date_from']);
        }
        if ($filters['date_to'] !== null) {
            $query->whereDate('settlement_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('id');
    }

    private function transactionResult(MarketplaceFinancialTransaction $transaction): array
    {
        $reasons = [];
        $pending = false;
        if (! $transaction->marketplace_order_id) {
            $reasons[] = 'order_unmatched';
        }
        if (! $transaction->sales_invoice_id || ! $transaction->salesInvoice) {
            $reasons[] = 'missing_sales_invoice';
        }
        if (! $transaction->shipment_id || ! $transaction->shipment) {
            $reasons[] = 'missing_shipment';
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
        if ($expectedNet !== null && $this->different($expectedNet, (float) $transaction->net_amount)) {
            $reasons[] = 'fee_mismatch';
        }

        $allocated = round((float) $transaction->allocations->sum('allocated_amount'), 2);
        $receivedAllocated = round((float) $transaction->allocations
            ->filter(fn ($allocation): bool => $allocation->settlement?->status === SettlementStatus::RECEIVED)
            ->sum('allocated_amount'), 2);
        if ($allocated <= self::TOLERANCE) {
            $reasons[] = 'missing_settlement';
            $pending = true;
        }

        $journal = [
            'sale' => $this->journalResult('marketplace_sale', $transaction->id, $transaction->saleJournal, (float) $transaction->gross_amount, $reasons, $transaction->salesInvoice !== null),
            'escrow' => $this->journalResult('marketplace_escrow', $transaction->id, $transaction->escrowJournal, abs($totalComponents), $reasons, $transaction->escrow_status === EscrowStatus::FINALIZED && $transaction->components->isNotEmpty()),
            'settlement' => $this->settlementJournalResult($transaction, $reasons),
        ];
        if ($this->activeJournalCount('marketplace_sale', $transaction->id) > 1
            || $this->activeJournalCount('marketplace_escrow', $transaction->id) > 1
            || $this->activeJournalCount('marketplace_settlement', $transaction->id) > 1) {
            $reasons[] = 'journal_duplicate';
        }

        $reasons = array_values(array_unique($reasons));
        $status = $this->status($reasons, $pending);

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
        if ($settlement->status !== SettlementStatus::RECEIVED) {
            $reasons[] = 'missing_settlement';
        } elseif (! $settlement->journal_id || ! Journal::query()
            ->whereKey($settlement->journal_id)
            ->whereNull('voided_at')
            ->exists()) {
            $reasons[] = 'journal_missing';
        }
        $status = $this->status($reasons, $settlement->status !== SettlementStatus::RECEIVED);

        return [
            'settlement_id' => $settlement->id,
            'external_settlement_id' => $settlement->external_settlement_id,
            'store_id' => $settlement->store_id,
            'status' => $status,
            'reasons' => $reasons,
            'settlement_amount' => $amount,
            'allocated_amount' => $allocated,
            'unallocated_amount' => $unallocated,
            'settlement_received_amount' => $settlement->status === SettlementStatus::RECEIVED ? $amount : 0.0,
            'settlement_status' => $settlement->status?->value,
            'journal_amount' => $this->journalAmount($settlement->journal_id, $reasons),
        ];
    }

    private function settlementJournalResult(MarketplaceFinancialTransaction $transaction, array &$reasons): ?float
    {
        $received = $transaction->allocations->filter(fn ($allocation): bool => $allocation->settlement?->status === SettlementStatus::RECEIVED);
        if ($received->isEmpty()) {
            return null;
        }
        $amount = round((float) $received->sum('allocated_amount'), 2);
        $journalAmount = 0.0;
        $seenSettlements = [];
        foreach ($received as $allocation) {
            $settlementId = $allocation->settlement?->id;
            if (! $settlementId || isset($seenSettlements[$settlementId])) {
                continue;
            }
            $seenSettlements[$settlementId] = true;
            $settlementJournal = $this->journalAmount($allocation->settlement?->journal_id, $reasons);
            if ($settlementJournal === null) {
                $reasons[] = 'journal_missing';

                continue;
            }
            $journalAmount += $settlementJournal;
        }
        if ($journalAmount > 0 && $this->different($journalAmount, $amount)) {
            $reasons[] = 'amount_mismatch';
        }

        return round($journalAmount, 2);
    }

    private function journalResult(string $sourceType, int $sourceId, ?\App\Models\Journal $journal, float $expected, array &$reasons, bool $required = true): ?float
    {
        if (! $required && ! $journal) {
            return null;
        }
        $amount = $this->journalAmount($journal?->id, $reasons);
        if ($required && $amount === null) {
            $reasons[] = 'journal_missing';
        } elseif ($amount !== null && $this->different($amount, $expected)) {
            $reasons[] = 'amount_mismatch';
        }

        return $amount;
    }

    private function journalAmount(?int $journalId, array &$reasons): ?float
    {
        if (! $journalId) {
            return null;
        }
        $journal = Journal::query()->with('lines')->whereKey($journalId)->whereNull('voided_at')->first();
        if (! $journal) {
            $reasons[] = 'journal_missing';

            return null;
        }

        return round((float) $journal->lines->sum('debit'), 2);
    }

    private function activeJournalCount(string $sourceType, int $sourceId): int
    {
        return Journal::query()->where('source_type', $sourceType)->where('source_id', $sourceId)->whereNull('voided_at')->count();
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

    private function different(float $left, float $right): bool
    {
        return abs(round($left - $right, 2)) > self::TOLERANCE;
    }

    private function filters(array $filters): array
    {
        $requestedBasis = $filters['date_basis'] ?? 'created_at';
        $basis = in_array($requestedBasis, ['created_at', 'released_at'], true)
            ? $requestedBasis
            : 'created_at';

        return [
            'store_id' => isset($filters['store_id']) && $filters['store_id'] !== '' ? (int) $filters['store_id'] : null,
            'date_basis' => $basis,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'status' => isset($filters['status']) && $filters['status'] !== '' ? (string) $filters['status'] : null,
            'order_sn' => isset($filters['order_sn']) && trim((string) $filters['order_sn']) !== '' ? trim((string) $filters['order_sn']) : null,
        ];
    }
}
