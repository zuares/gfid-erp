<?php

namespace App\Services\Marketplace\Finance;

use App\Domain\Marketplace\Finance\Enums\ComponentDirection;
use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinanceSettlementAllocation;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderIncomeEstimate;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplacePayout;
use App\Models\MpIncome;
use App\Models\MpShipment;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketplaceFinanceBackfillService
{
    private const SOURCES = [
        'order_settlements',
        'income_estimates',
        'mp_incomes',
        'payouts',
    ];

    private bool $dryRun = true;

    private ?int $storeId = null;

    /** @var array<string,bool> */
    private array $plannedTransactions = [];

    /** @var array<string,bool> */
    private array $plannedComponents = [];

    /** @var array<string,bool> */
    private array $plannedSettlements = [];

    /** @var array<string,bool> */
    private array $plannedAllocations = [];

    /** @var array<string,array<string,bool>> */
    private array $seenSourceKeys = [];

    /**
     * @param  array{dry_run?:bool,sources?:array<int,string>,store_id?:int|null}  $options
     * @return array<string,mixed>
     */
    public function run(array $options = []): array
    {
        $this->dryRun = (bool) ($options['dry_run'] ?? true);
        $this->storeId = isset($options['store_id']) && $options['store_id'] !== ''
            ? (int) $options['store_id']
            : null;
        $this->plannedTransactions = [];
        $this->plannedComponents = [];
        $this->plannedSettlements = [];
        $this->plannedAllocations = [];
        $this->seenSourceKeys = [];

        if (! Schema::hasTable('marketplace_financial_transactions')
            || ! Schema::hasTable('marketplace_financial_components')
            || ! Schema::hasTable('marketplace_finance_settlements')
            || ! Schema::hasTable('marketplace_finance_settlement_allocations')) {
            return [
                'mode' => $this->dryRun ? 'dry-run' : 'apply',
                'sources' => [],
                'summary' => array_merge($this->summaryDefaults(), ['errors' => 1]),
                'unmatched_rows' => [],
                'errors_rows' => [[
                    'source' => 'preflight',
                    'source_id' => null,
                    'message' => 'Tabel Marketplace Finance belum tersedia. Jalankan migration terlebih dahulu.',
                ]],
            ];
        }

        $sources = array_values(array_unique($options['sources'] ?? self::SOURCES));
        $result = [
            'mode' => $this->dryRun ? 'dry-run' : 'apply',
            'sources' => [],
            'summary' => $this->summaryDefaults(),
            'unmatched_rows' => [],
            'errors_rows' => [],
        ];

        foreach ($sources as $source) {
            $stats = $this->sourceStats();
            if (! in_array($source, self::SOURCES, true)) {
                $this->error($result, 'options', null, "Sumber {$source} tidak dikenal.");

                continue;
            }

            $callback = match ($source) {
                'order_settlements' => function (MarketplaceOrderSettlement $row) use (&$stats, &$result): void {
                    $this->backfillOrderSettlement($row, $stats, $result);
                },
                'income_estimates' => function (MarketplaceOrderIncomeEstimate $row) use (&$stats, &$result): void {
                    $this->backfillIncomeEstimate($row, $stats, $result);
                },
                'mp_incomes' => function (MpIncome $row) use (&$stats, &$result): void {
                    $this->backfillMpIncome($row, $stats, $result);
                },
                'payouts' => function (MarketplacePayout $row) use (&$stats, &$result): void {
                    $this->backfillPayout($row, $stats, $result);
                },
            };
            $query = match ($source) {
                'order_settlements' => MarketplaceOrderSettlement::query()->with(['store.channel', 'order.store.channel'])->when($this->storeId, fn (Builder $query) => $query->where('store_id', $this->storeId)),
                'income_estimates' => MarketplaceOrderIncomeEstimate::query()->with(['store.channel', 'order'])->when($this->storeId, fn (Builder $query) => $query->where('store_id', $this->storeId)),
                'mp_incomes' => MpIncome::query()->with('store.channel')->when($this->storeId, fn (Builder $query) => $query->where('store_id', $this->storeId)),
                'payouts' => MarketplacePayout::query()->with('store.channel')->when($this->storeId, fn (Builder $query) => $query->where('store_id', $this->storeId)),
            };
            $this->scan($source, $query, $callback, $stats, $result);
            $result['sources'][$source] = $stats;
        }

        foreach ($result['sources'] as $stats) {
            foreach ($result['summary'] as $key => $value) {
                if (array_key_exists($key, $stats)) {
                    $result['summary'][$key] += $stats[$key];
                }
            }
        }

        return $result;
    }

    private function scan(string $source, Builder $query, callable $callback, array &$stats, array &$result): void
    {
        $query->orderBy('id')->chunkById(200, function ($rows) use ($source, $callback, &$stats, &$result): void {
            foreach ($rows as $row) {
                $stats['scanned']++;
                try {
                    $callback($row);
                } catch (\Throwable $exception) {
                    $stats['errors']++;
                    $result['errors_rows'][] = ['source' => $source, 'source_id' => $row->id, 'message' => $exception->getMessage()];
                    $result['summary']['errors']++;
                }
            }
        });
    }

    /** @return array<string,int> */
    private function sourceStats(): array
    {
        return [
            'scanned' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'unmatched' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'transactions_created' => 0,
            'components_created' => 0,
            'settlements_created' => 0,
            'allocations_created' => 0,
        ];
    }

    /** @return array<string,int> */
    private function summaryDefaults(): array
    {
        return [
            'scanned' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'unmatched' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'transactions_created' => 0,
            'components_created' => 0,
            'settlements_created' => 0,
            'allocations_created' => 0,
        ];
    }

    private function backfillOrderSettlement(MarketplaceOrderSettlement $row, array &$stats, array &$result): void
    {
        $orderSn = trim((string) $row->channel_order_id);
        $context = $this->context($row->store ?: $row->order?->store, $orderSn, $row->order);
        if (! $context['valid']) {
            $this->unmatched($stats, $result, 'order_settlements', $row->id, $context['reason']);

            return;
        }
        $this->duplicateSource($stats, 'order_settlements', $context['key']);

        $gross = $this->nullableAmount($row->buyer_payment_amount);
        $net = $this->nullableAmount($row->final_income);
        $transaction = $this->transaction($context, [
            'gross_amount' => $gross,
            'net_amount' => $net,
            'escrow_status' => $row->settlement_time || ($net !== null && $net > 0) ? EscrowStatus::FINALIZED : EscrowStatus::SYNCED,
            'income_status' => $row->settlement_time || ($net !== null && $net > 0) ? IncomeStatus::RELEASED : IncomeStatus::PENDING,
            'released_at' => $row->settlement_time,
            'synced_at' => $row->synced_at,
            'raw_payload' => $this->legacyPayload('marketplace_order_settlements', $row->id, $row->raw_json),
            'source_hash' => $this->sourceHash('marketplace_order_settlements', $row->id, $row->raw_json),
        ], $stats);

        foreach ([
            'commission_fee' => ['admin_fee', 'Admin Fee'],
            'service_fee' => ['service_fee', 'Service Fee'],
            'transaction_fee' => ['transaction_fee', 'Transaction Fee'],
            'affiliate_fee' => ['affiliate_fee', 'Affiliate Fee'],
            'shipping_insurance_fee' => ['shipping_insurance', 'Shipping Insurance'],
            'seller_voucher' => ['voucher', 'Voucher'],
            'seller_coin_cash_back' => ['rebate', 'Rebate'],
            'reverse_shipping_fee' => ['shipping_adjustment', 'Shipping Adjustment'],
            'activity_fee' => ['other_fee', 'Activity Fee'],
            'drc_adjustable_refund' => ['refund', 'Refund'],
            'escrow_tax' => ['other_fee', 'Escrow Tax'],
            'ad_cost' => ['advertising', 'Advertising'],
        ] as $field => [$code, $name]) {
            if ($row->{$field} !== null && abs((float) $row->{$field}) > 0.0) {
                $this->component($transaction, $code, $name, $row->{$field}, 'marketplace_order_settlements', $row->id, $field, $row->raw_json, $stats);
            }
        }
    }

    private function backfillIncomeEstimate(MarketplaceOrderIncomeEstimate $row, array &$stats, array &$result): void
    {
        $orderSn = trim((string) $row->channel_order_id);
        $context = $this->context($row->store, $orderSn, $row->order);
        if (! $context['valid']) {
            $this->unmatched($stats, $result, 'income_estimates', $row->id, $context['reason']);

            return;
        }
        $this->duplicateSource($stats, 'income_estimates', $context['key']);
        $status = $this->incomeStatus($row->income_status);
        $this->transaction($context, [
            'gross_amount' => $this->nullableAmount($row->estimated_escrow_amount),
            'income_status' => $status,
            'released_at' => $status === IncomeStatus::RELEASED ? $row->estimated_payout_at : null,
            'income_source_hash' => $this->sourceHash('marketplace_order_income_estimates', $row->id, $row->raw_json),
            'income_raw_payload' => $this->legacyPayload('marketplace_order_income_estimates', $row->id, $row->raw_json),
            'income_synced_at' => $row->synced_at,
        ], $stats);
    }

    private function backfillMpIncome(MpIncome $row, array &$stats, array &$result): void
    {
        $orderSn = trim((string) $row->platform_order_id);
        $shipment = MpShipment::query()
            ->where('store_id', $row->store_id)
            ->where('channel', $row->channel)
            ->where('platform_order_id', $orderSn)
            ->latest('id')
            ->first();
        $context = $this->context($row->store, $orderSn, null, $shipment?->grand_total);
        if (! $context['valid']) {
            $this->unmatched($stats, $result, 'mp_incomes', $row->id, $context['reason']);

            return;
        }
        $this->duplicateSource($stats, 'mp_incomes', $context['key']);
        $transaction = $this->transaction($context, [
            'gross_amount' => $this->nullableAmount($shipment?->grand_total),
            'net_amount' => $this->nullableAmount($row->net_payout_actual),
            'income_status' => $row->released_at ? IncomeStatus::RELEASED : IncomeStatus::PENDING,
            'released_at' => $row->released_at,
            'income_source_hash' => $this->sourceHash('mp_incomes', $row->id, $row->raw_payload),
            'income_raw_payload' => $this->legacyPayload('mp_incomes', $row->id, $row->raw_payload),
            'income_synced_at' => $row->updated_at,
            'raw_payload' => $this->legacyPayload('mp_incomes', $row->id, $row->raw_payload),
            'source_hash' => $this->sourceHash('mp_incomes', $row->id, $row->raw_payload),
        ], $stats);

        if ($row->platform_fee_total !== null && abs((float) $row->platform_fee_total) > 0.0) {
            $this->component($transaction, 'legacy_platform_fee', 'Legacy Platform Fee', $row->platform_fee_total, 'mp_incomes', $row->id, 'platform_fee_total', $row->raw_payload, $stats);
        }
        if ($row->refund_total !== null && abs((float) $row->refund_total) > 0.0) {
            $this->component($transaction, 'refund', 'Refund', $row->refund_total, 'mp_incomes', $row->id, 'refund_total', $row->raw_payload, $stats);
        }
    }

    private function backfillPayout(MarketplacePayout $row, array &$stats, array &$result): void
    {
        $store = $row->store;
        $channel = $this->channel($store, (string) $row->marketplace_name);
        $externalId = trim((string) ($row->external_transaction_id ?: $row->reference));
        if (! $store || $channel === '' || $externalId === '') {
            $this->unmatched($stats, $result, 'payouts', $row->id, ! $store ? 'store_unmatched' : ($channel === '' ? 'channel_unmatched' : 'missing_settlement_identifier'));

            return;
        }
        $this->duplicateSource($stats, 'payouts', $store->id.'|'.$channel.'|'.$externalId);
        $settlement = $this->settlement($store, $channel, $externalId, [
            'amount' => $this->nullableAmount($row->amount) ?? '0.00',
            'currency' => 'IDR',
            'settlement_date' => $row->transaction_created_at?->toDateString() ?? $row->date?->toDateString(),
            'bank_account_id' => $row->bank_account_id,
            'status' => $this->settlementStatus($row->status),
            'raw_payload' => $this->legacyPayload('marketplace_payouts', $row->id, $row->source_payload),
        ], $stats);

        $items = $this->allocationRows($row->source_payload);
        if ($items === []) {
            $this->unmatched($stats, $result, 'payouts', $row->id, 'settlement_without_order_mapping');

            return;
        }

        foreach ($items as $index => $item) {
            $orderSn = trim((string) ($item['order_sn'] ?? $item['order_id'] ?? $item['ordersn'] ?? ''));
            $amount = $this->nullableAmount($item['amount'] ?? $item['allocated_amount'] ?? $item['payout_amount'] ?? $item['settlement_amount'] ?? null);
            if ($orderSn === '' || $amount === null) {
                $this->unmatched($stats, $result, 'payouts', $row->id, 'invalid_order_mapping_'.$index);

                continue;
            }
            $context = $this->context($store, $orderSn);
            if (! $context['valid']) {
                $this->unmatched($stats, $result, 'payouts', $row->id, 'order_unmatched:'.$orderSn);

                continue;
            }
            if (! $context['order'] && ! MarketplaceFinancialTransaction::query()
                ->where('store_id', $store->id)
                ->where('channel', $channel)
                ->where('order_sn', $orderSn)
                ->exists()) {
                $this->unmatched($stats, $result, 'payouts', $row->id, 'order_unmatched:'.$orderSn);

                continue;
            }
            $transaction = $this->transaction($context, [
                'net_amount' => $amount,
                'income_status' => IncomeStatus::RELEASED,
                'released_at' => $row->transaction_created_at,
            ], $stats);
            $this->allocation($settlement, $transaction, $orderSn, $amount, $stats);
        }
    }

    /** @return array{valid:bool,reason?:string,key?:string,store:?Store,channel?:string,order_sn?:string,order:?MarketplaceOrder} */
    private function context(?Store $store, string $orderSn, ?MarketplaceOrder $knownOrder = null, mixed $grossFallback = null): array
    {
        $orderSn = trim($orderSn);
        $channel = $this->channel($store);
        if (! $store) {
            return ['valid' => false, 'reason' => 'store_unmatched', 'store' => null, 'order' => null];
        }
        if ($channel === '') {
            return ['valid' => false, 'reason' => 'channel_unmatched', 'store' => $store, 'order' => null];
        }
        if ($orderSn === '') {
            return ['valid' => false, 'reason' => 'missing_order_sn', 'store' => $store, 'channel' => $channel, 'order' => null];
        }

        $order = $knownOrder ?: MarketplaceOrder::query()
            ->where('store_id', $store->id)
            ->where(fn ($query) => $query->where('channel_order_id', $orderSn)->orWhere('external_order_id', $orderSn))
            ->first();
        $invoice = SalesInvoice::query()
            ->where('store_id', $store->id)
            ->where('channel', $channel)
            ->where('channel_order_no', $orderSn)
            ->first();
        $shipment = $invoice
            ? Shipment::query()->where('store_id', $store->id)->where('sales_invoice_id', $invoice->id)->latest('id')->first()
            : null;

        return [
            'valid' => true,
            'key' => $store->id.'|'.$channel.'|'.$orderSn,
            'store' => $store,
            'channel' => $channel,
            'order_sn' => $orderSn,
            'order' => $order,
            'invoice' => $invoice,
            'shipment' => $shipment,
            'gross_fallback' => $grossFallback,
        ];
    }

    /** @param array<string,mixed> $values */
    private function transaction(array $context, array $values, array &$stats): array
    {
        $identity = [
            'store_id' => $context['store']->id,
            'channel' => $context['channel'],
            'order_sn' => $context['order_sn'],
        ];
        $key = implode('|', $identity);
        $transaction = MarketplaceFinancialTransaction::query()->where($identity)->first();
        $planned = isset($this->plannedTransactions[$key]);
        $isNew = ! $transaction && ! $planned;
        $changes = [];

        if ($context['order'] && ! $transaction?->marketplace_order_id) {
            $changes['marketplace_order_id'] = $context['order']->id;
        }
        if ($context['invoice'] && ! $transaction?->sales_invoice_id) {
            $changes['sales_invoice_id'] = $context['invoice']->id;
        }
        if ($context['shipment'] && ! $transaction?->shipment_id) {
            $changes['shipment_id'] = $context['shipment']->id;
        }
        if ($transaction && (float) $transaction->gross_amount === 0.0 && ($values['gross_amount'] ?? null) !== null) {
            $changes['gross_amount'] = $values['gross_amount'];
        }
        if ($transaction && (float) $transaction->net_amount === 0.0 && ($values['net_amount'] ?? null) !== null) {
            $changes['net_amount'] = $values['net_amount'];
        }
        if ($transaction) {
            $changes = array_merge($changes, $this->forwardStatuses($transaction, $values));
            foreach (['released_at', 'synced_at', 'income_synced_at', 'income_source_hash', 'income_raw_payload', 'source_hash', 'raw_payload'] as $field) {
                if ($transaction->{$field} === null && array_key_exists($field, $values) && $values[$field] !== null) {
                    $changes[$field] = $values[$field];
                }
            }
        }

        if ($this->dryRun) {
            $this->plannedTransactions[$key] = true;
            $stats[$isNew ? 'created' : ($changes === [] ? 'unchanged' : 'updated')]++;
            if ($isNew) {
                $stats['transactions_created']++;
            }

            return ['id' => $transaction?->id, 'key' => $key];
        }

        $model = DB::transaction(function () use ($identity, $values, $changes, $transaction, $context): MarketplaceFinancialTransaction {
            if (! $transaction) {
                $defaults = [
                    'currency' => 'IDR',
                    'gross_amount' => '0.00',
                    'net_amount' => '0.00',
                    'escrow_status' => EscrowStatus::PENDING,
                    'income_status' => IncomeStatus::PENDING,
                ];
                $createValues = array_merge($identity, $defaults, $values);
                foreach (['gross_amount', 'net_amount'] as $field) {
                    if ($createValues[$field] === null) {
                        unset($createValues[$field]);
                    }
                }
                foreach (['marketplace_order_id', 'sales_invoice_id', 'shipment_id'] as $field) {
                    if (isset($context[$field])) {
                        $createValues[$field] = $context[$field];
                    }
                }

                return MarketplaceFinancialTransaction::create($createValues);
            }
            if ($changes !== []) {
                $transaction->forceFill($changes)->save();
            }

            return $transaction->fresh();
        });
        $stats[$isNew ? 'created' : ($changes === [] ? 'unchanged' : 'updated')]++;
        if ($isNew) {
            $stats['transactions_created']++;
        }

        return ['id' => $model->id, 'key' => $key];
    }

    /** @param array<string,mixed> $values */
    private function forwardStatuses(MarketplaceFinancialTransaction $transaction, array $values): array
    {
        $changes = [];
        if (($values['income_status'] ?? null) instanceof IncomeStatus && $this->incomeRank($values['income_status']) > $this->incomeRank($transaction->income_status)) {
            $changes['income_status'] = $values['income_status'];
        }
        if (($values['escrow_status'] ?? null) instanceof EscrowStatus && $this->escrowRank($values['escrow_status']) > $this->escrowRank($transaction->escrow_status)) {
            $changes['escrow_status'] = $values['escrow_status'];
        }

        return $changes;
    }

    private function component(array $transaction, string $code, string $name, mixed $value, string $source, int $sourceId, string $field, mixed $raw, array &$stats): void
    {
        $dedupeKey = hash('sha256', "{$source}|{$sourceId}|{$transaction['key']}|{$code}|{$field}");
        $existing = MarketplaceFinancialComponent::query()->where('dedupe_key', $dedupeKey)->first();
        $alreadyPlanned = isset($this->plannedComponents[$dedupeKey]);
        if ($existing || $alreadyPlanned) {
            $stats['unchanged']++;

            return;
        }
        $this->plannedComponents[$dedupeKey] = true;
        $amount = $this->decimal($value);
        if (! $this->dryRun && $transaction['id']) {
            MarketplaceFinancialComponent::create([
                'financial_transaction_id' => $transaction['id'],
                'component_code' => $code,
                'component_name' => $name,
                'amount' => $amount,
                'direction' => (float) $amount < 0 ? ComponentDirection::CREDIT : ComponentDirection::DEBIT,
                'source_hash' => hash('sha256', "{$source}|{$sourceId}"),
                'raw_payload' => ['source' => $source, 'source_id' => $sourceId, 'field' => $field, 'value' => $value, 'raw' => $raw],
                'dedupe_key' => $dedupeKey,
            ]);
        }
        $stats['created']++;
        $stats['components_created']++;
    }

    private function settlement(Store $store, string $channel, string $externalId, array $values, array &$stats): array
    {
        $identity = ['store_id' => $store->id, 'channel' => $channel, 'external_settlement_id' => $externalId];
        $key = implode('|', $identity);
        $settlement = MarketplaceFinanceSettlement::query()->where($identity)->first();
        $planned = isset($this->plannedSettlements[$key]);
        $isNew = ! $settlement && ! $planned;
        $changes = [];
        foreach (['amount', 'currency', 'settlement_date', 'bank_account_id', 'status', 'raw_payload'] as $field) {
            if (! $settlement || ($settlement->{$field} === null && ($values[$field] ?? null) !== null)) {
                $changes[$field] = $values[$field] ?? null;
            }
        }
        if ($settlement && $settlement->status !== SettlementStatus::RECEIVED && ($values['status'] ?? null) === SettlementStatus::RECEIVED) {
            $changes['status'] = SettlementStatus::RECEIVED;
            $changes['received_at'] = now();
        }
        if (! $settlement && ($values['status'] ?? null) === SettlementStatus::RECEIVED) {
            $changes['received_at'] = now();
        }
        $this->plannedSettlements[$key] = true;
        if (! $this->dryRun && $settlement) {
            if ($changes !== []) {
                $settlement->forceFill($changes)->save();
            }
            $id = $settlement->id;
        } elseif (! $this->dryRun) {
            $id = MarketplaceFinanceSettlement::create(array_merge($identity, $changes))->id;
        } else {
            $id = $settlement?->id;
        }
        $stats[$isNew ? 'created' : ($changes === [] ? 'unchanged' : 'updated')]++;
        if ($isNew) {
            $stats['settlements_created']++;
        }

        return ['id' => $id, 'key' => $key];
    }

    private function allocation(array $settlement, array $transaction, string $orderSn, float|string $amount, array &$stats): void
    {
        $key = $settlement['key'].'|'.$transaction['key'];
        $existing = $settlement['id'] && $transaction['id']
            ? MarketplaceFinanceSettlementAllocation::query()->where('settlement_id', $settlement['id'])->where('financial_transaction_id', $transaction['id'])->first()
            : null;
        if ($existing || isset($this->plannedAllocations[$key])) {
            $stats['unchanged']++;

            return;
        }
        $this->plannedAllocations[$key] = true;
        if (! $this->dryRun && $settlement['id'] && $transaction['id']) {
            MarketplaceFinanceSettlementAllocation::create([
                'settlement_id' => $settlement['id'],
                'financial_transaction_id' => $transaction['id'],
                'order_sn' => $orderSn,
                'allocated_amount' => $amount,
            ]);
        }
        $stats['created']++;
        $stats['allocations_created']++;
    }

    private function duplicateSource(array &$stats, string $source, string $key): void
    {
        if (isset($this->seenSourceKeys[$source][$key])) {
            $stats['duplicates']++;
        }
        $this->seenSourceKeys[$source][$key] = true;
    }

    private function unmatched(array &$stats, array &$result, string $source, int $id, string $reason): void
    {
        $stats['unmatched']++;
        if (count($result['unmatched_rows']) < 500) {
            $result['unmatched_rows'][] = ['source' => $source, 'source_id' => $id, 'reason' => $reason];
        }
    }

    private function error(array &$result, string $source, ?int $id, string $message): void
    {
        if (isset($result['sources'][$source])) {
            $result['sources'][$source]['errors']++;
        }
        $result['summary']['errors']++;
        $result['errors_rows'][] = ['source' => $source, 'source_id' => $id, 'message' => $message];
    }

    private function channel(?Store $store, string $fallback = ''): string
    {
        return strtolower(trim((string) ($store?->channel?->code ?: $fallback)));
    }

    private function incomeStatus(mixed $value): IncomeStatus
    {
        return match ((int) $value) {
            0 => IncomeStatus::TO_RELEASE,
            1 => IncomeStatus::RELEASED,
            2 => IncomeStatus::PENDING,
            default => IncomeStatus::UNKNOWN,
        };
    }

    private function settlementStatus(?string $value): SettlementStatus
    {
        return match (strtolower(trim((string) $value))) {
            'posted', 'received', 'completed', 'success', 'successful', 'paid' => SettlementStatus::RECEIVED,
            'void', 'cancelled', 'canceled', 'failed' => SettlementStatus::VOID,
            default => SettlementStatus::PENDING,
        };
    }

    private function incomeRank(?IncomeStatus $status): int
    {
        return match ($status) {
            IncomeStatus::PENDING => 1,
            IncomeStatus::TO_RELEASE => 2,
            IncomeStatus::RELEASED => 3,
            default => 0,
        };
    }

    private function escrowRank(?EscrowStatus $status): int
    {
        return match ($status) {
            EscrowStatus::SYNCED => 1,
            EscrowStatus::FINALIZED => 2,
            default => 0,
        };
    }

    private function nullableAmount(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->decimal($value);
    }

    private function decimal(mixed $value): string
    {
        return number_format((float) str_replace(',', '', (string) $value), 2, '.', '');
    }

    private function sourceHash(string $source, int $id, mixed $raw): string
    {
        return hash('sha256', $source.'|'.$id.'|'.json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function legacyPayload(string $source, int $id, mixed $raw): array
    {
        return ['legacy_source' => $source, 'legacy_id' => $id, 'raw' => is_array($raw) ? $raw : []];
    }

    /** @return array<int,array<string,mixed>> */
    private function allocationRows(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }
        foreach (['allocations', 'orders', 'order_list', 'escrow_list', 'details'] as $key) {
            $rows = data_get($payload, $key);
            if (is_array($rows)) {
                return array_is_list($rows) ? $rows : [$rows];
            }
        }

        return array_is_list($payload) ? $payload : [];
    }
}
