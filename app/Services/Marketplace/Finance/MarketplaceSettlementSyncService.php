<?php

namespace App\Services\Marketplace\Finance;

use App\Domain\Marketplace\Finance\Enums\SettlementStatus;
use App\Models\MarketplaceFinanceSettlement;
use App\Models\MarketplaceFinanceSettlementAllocation;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceSettlementSyncService
{
    private const SHOPEE_CODES = ['shopee', 'shp'];

    private const WALLET_WITHDRAWAL_TYPES = [
        '201', '202', 'withdrawal_created', 'withdrawal_completed',
    ];

    public function __construct(
        private MarketplaceApiGateway $gateway,
        private MarketplaceSettlementNormalizer $normalizer,
        private MarketplaceFinanceOrderBridgeService $orderBridge,
    ) {}

    /** Sync payout info/detail and allocate detail rows to finance transactions. */
    public function syncPayouts(Store $store, Carbon $from, Carbon $to, int $pageSize = 100): array
    {
        if (! $this->supported($store)) {
            return $this->result('skipped', message: 'Payout settlement hanya tersedia untuk toko Shopee.');
        }

        $pageSize = min(100, max(1, $pageSize));
        $totals = $this->emptyTotals();

        try {
            $cursor = '';
            do {
                $totals['pages']++;
                $response = $this->gateway->getPayoutInfo($store, $from->timestamp, $to->timestamp, $cursor, $pageSize);
                if ($this->failed($response)) {
                    return $this->failure($totals, $response);
                }
                foreach ($this->extractPayoutRows($response) as $row) {
                    $this->persistHeader($store, $row, $totals);
                }
                $nextCursor = (string) (data_get($response, 'response.next_cursor') ?? data_get($response, 'next_cursor') ?? '');
                $more = (bool) (data_get($response, 'response.more') ?? data_get($response, 'more') ?? ($nextCursor !== ''));
                if (! $more || $nextCursor === '' || $nextCursor === $cursor || $totals['pages'] >= 100) {
                    break;
                }
                $cursor = $nextCursor;
            } while (true);

            $pageNo = 1;
            do {
                $totals['detail_pages']++;
                $response = $this->gateway->getPayoutDetail($store, $from->timestamp, $to->timestamp, $pageNo, $pageSize);
                if ($this->failed($response)) {
                    return $this->failure($totals, $response);
                }
                $rows = $this->extractPayoutRows($response);
                foreach ($rows as $row) {
                    $settlement = $this->persistHeader($store, $row, $totals);
                    if ($settlement) {
                        $this->persistAllocations($store, $settlement, $row, $totals);
                    }
                }
                $pageNo++;
                $more = (bool) (data_get($response, 'response.more') ?? data_get($response, 'more') ?? false);
            } while ($more && $pageNo <= 100);
        } catch (\Throwable $e) {
            Log::warning('[marketplace-finance-settlement-sync] Exception', ['store_id' => $store->id, 'error' => $e->getMessage()]);

            return $this->result('partial', $totals['found'], $totals['synced'], $totals['created'], $totals['updated'], $totals['allocated'], $totals['unmatched'], $totals['invalid'], $totals['pages'], $totals['detail_pages'], 1, $totals['results'], 'Settlement payout gagal sementara: '.$e->getMessage());
        }

        return $this->result('synced', $totals['found'], $totals['synced'], $totals['created'], $totals['updated'], $totals['allocated'], $totals['unmatched'], $totals['invalid'], $totals['pages'], $totals['detail_pages'], 0, $totals['results'], 'Payout settlement berhasil disinkronkan.');
    }

    /** Sync local Shopee wallet withdrawals without writing legacy payout rows. */
    public function syncWalletPayouts(Store $store, Carbon $from, Carbon $to, int $pageSize = 100): array
    {
        if (! $this->supported($store)) {
            return $this->result('skipped', message: 'Wallet settlement hanya tersedia untuk toko Shopee.');
        }

        $pageSize = min(100, max(1, $pageSize));
        $totals = $this->emptyTotals();
        $pageNo = 0;

        try {
            do {
                $totals['pages']++;
                $response = $this->gateway->getWalletTransactionList(
                    $store,
                    $pageNo,
                    $pageSize,
                    $from->timestamp,
                    $to->timestamp,
                    'MONEY_OUT',
                    '201',
                    null,
                    'wallet_withdrawals',
                );
                if ($this->failed($response)) {
                    return $this->failure($totals, $response);
                }
                $rows = $this->extractWalletRows($response);
                foreach ($rows as $row) {
                    $normalized = $this->normalizer->wallet($row);
                    $type = strtolower(str_replace('-', '_', trim((string) ($row['transaction_type'] ?? $row['type'] ?? ''))));
                    if ($normalized['external_settlement_id'] === ''
                        || $normalized['amount'] === null
                        || (! in_array($type, self::WALLET_WITHDRAWAL_TYPES, true)
                            && $type !== '')) {
                        $totals['invalid']++;
                        $totals['results'][] = ['status' => 'invalid', 'reason' => $type !== '' && ! in_array($type, self::WALLET_WITHDRAWAL_TYPES, true) ? 'non_withdrawal_type' : 'missing_wallet_identifier_or_amount'];

                        continue;
                    }
                    $totals['found']++;
                    $this->persistNormalized($store, strtolower((string) $store->channel?->code), $normalized, $totals);
                }
                $pageNo += count($rows);
                $more = (bool) (data_get($response, 'response.more') ?? data_get($response, 'more') ?? false);
            } while ($more && $pageNo < 10000 && $totals['pages'] < 100);
        } catch (\Throwable $e) {
            Log::warning('[marketplace-finance-wallet-settlement-sync] Exception', ['store_id' => $store->id, 'error' => $e->getMessage()]);

            return $this->result('partial', $totals['found'], $totals['synced'], $totals['created'], $totals['updated'], $totals['allocated'], $totals['unmatched'], $totals['invalid'], $totals['pages'], 0, 1, $totals['results'], 'Wallet settlement gagal sementara: '.$e->getMessage());
        }

        return $this->result('synced', $totals['found'], $totals['synced'], $totals['created'], $totals['updated'], $totals['allocated'], $totals['unmatched'], $totals['invalid'], $totals['pages'], 0, 0, $totals['results'], 'Wallet settlement berhasil disinkronkan.');
    }

    private function persistHeader(Store $store, array $row, array &$totals): ?MarketplaceFinanceSettlement
    {
        $normalized = $this->normalizer->payout($row);
        if ($normalized['external_settlement_id'] === '') {
            $totals['invalid']++;
            $totals['results'][] = ['status' => 'invalid', 'reason' => 'missing_settlement_identifier'];

            return null;
        }

        $totals['found']++;

        return $this->persistNormalized($store, strtolower((string) $store->channel?->code), $normalized, $totals);
    }

    private function persistNormalized(Store $store, string $channel, array $normalized, array &$totals): MarketplaceFinanceSettlement
    {
        $identity = [
            'store_id' => $store->id,
            'channel' => $channel,
            'external_settlement_id' => $normalized['external_settlement_id'],
        ];

        return DB::transaction(function () use ($identity, $normalized, &$totals): MarketplaceFinanceSettlement {
            $settlement = MarketplaceFinanceSettlement::query()->where($identity)->lockForUpdate()->first();
            $wasCreated = ! $settlement;
            if (! $settlement) {
                $settlement = MarketplaceFinanceSettlement::create($identity);
            }

            $current = $settlement->status instanceof SettlementStatus ? $settlement->status : SettlementStatus::UNKNOWN;
            $status = $this->forwardStatus($current, $normalized['status']);
            $values = [
                'status' => $status,
                'currency' => $normalized['currency'],
                'raw_payload' => $normalized['raw_payload'],
            ];
            if ($normalized['amount'] !== null) {
                $values['amount'] = $normalized['amount'];
            }
            if ($normalized['settlement_date']) {
                $values['settlement_date'] = $normalized['settlement_date']->toDateString();
            }
            if ($status === SettlementStatus::RECEIVED && ! $settlement->received_at) {
                $values['received_at'] = now();
            }
            $settlement->fill($values)->save();

            $totals[$wasCreated ? 'created' : 'updated']++;
            $totals['synced']++;
            $totals['results'][] = [
                'status' => 'synced',
                'external_settlement_id' => $settlement->external_settlement_id,
                'settlement_id' => $settlement->id,
                'settlement_status' => $settlement->status->value,
            ];

            return $settlement->fresh();
        });
    }

    private function persistAllocations(Store $store, MarketplaceFinanceSettlement $settlement, array $row, array &$totals): void
    {
        $seen = [];
        foreach ($this->normalizer->allocations($row) as $allocation) {
            $orderSn = $allocation['order_sn'];
            if (isset($seen[$orderSn])) {
                continue;
            }
            $seen[$orderSn] = true;
            if ($allocation['amount'] === null) {
                $totals['invalid']++;

                continue;
            }

            $channel = strtolower((string) $store->channel?->code);
            $order = MarketplaceOrder::query()
                ->where('store_id', $store->id)
                ->where(fn ($query) => $query->where('channel_order_id', $orderSn)->orWhere('external_order_id', $orderSn))
                ->first();
            if ($order) {
                $transaction = $this->orderBridge->syncFromOrder($order);
            } else {
                $transaction = MarketplaceFinancialTransaction::query()
                    ->where('store_id', $store->id)
                    ->where('channel', $channel)
                    ->where('order_sn', $orderSn)
                    ->first();
            }

            if (! $transaction) {
                $totals['unmatched']++;
                $totals['results'][] = ['status' => 'unmatched', 'order_sn' => $orderSn, 'settlement_id' => $settlement->id];

                continue;
            }

            MarketplaceFinanceSettlementAllocation::updateOrCreate(
                ['settlement_id' => $settlement->id, 'financial_transaction_id' => $transaction->id],
                ['order_sn' => $orderSn, 'allocated_amount' => $allocation['amount']],
            );
            $totals['allocated']++;
        }
    }

    private function forwardStatus(SettlementStatus $current, SettlementStatus $incoming): SettlementStatus
    {
        if ($incoming === SettlementStatus::UNKNOWN) {
            return $current;
        }
        if ($current === SettlementStatus::UNKNOWN) {
            return $incoming;
        }
        if ($current === SettlementStatus::RECEIVED && $incoming !== SettlementStatus::VOID) {
            return $current;
        }

        return $incoming;
    }

    private function supported(Store $store): bool
    {
        $store->loadMissing('channel');

        return in_array(strtolower((string) $store->channel?->code), self::SHOPEE_CODES, true);
    }

    private function failed(array $response): bool
    {
        return ! empty($response['error']) || (($response['code'] ?? null) !== null && (string) $response['code'] !== '0');
    }

    /** @return array<int,array<string,mixed>> */
    private function extractPayoutRows(array $response): array
    {
        $rows = data_get($response, 'response.payout_list') ?? data_get($response, 'payout_list', []);
        if (! is_array($rows)) {
            return [];
        }
        if (! array_is_list($rows)) {
            $rows = [$rows];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /** @return array<int,array<string,mixed>> */
    private function extractWalletRows(array $response): array
    {
        $rows = data_get($response, 'response.transaction_list') ?? data_get($response, 'transaction_list', []);
        if (! is_array($rows)) {
            return [];
        }
        if (! array_is_list($rows) && (array_key_exists('transaction_id', $rows) || array_key_exists('amount', $rows))) {
            $rows = [$rows];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /** @return array<string,int|array> */
    private function emptyTotals(): array
    {
        return ['found' => 0, 'synced' => 0, 'created' => 0, 'updated' => 0, 'allocated' => 0, 'unmatched' => 0, 'invalid' => 0, 'pages' => 0, 'detail_pages' => 0, 'errors' => 0, 'results' => []];
    }

    private function failure(array $totals, array $response): array
    {
        return $this->result('partial', $totals['found'], $totals['synced'], $totals['created'], $totals['updated'], $totals['allocated'], $totals['unmatched'], $totals['invalid'], $totals['pages'], $totals['detail_pages'], 1, $totals['results'], 'Payout settlement gagal: '.(string) ($response['message'] ?? $response['error'] ?? $response['code'] ?? 'unknown'));
    }

    private function result(string $status, int $found = 0, int $synced = 0, int $created = 0, int $updated = 0, int $allocated = 0, int $unmatched = 0, int $invalid = 0, int $pages = 0, int $detailPages = 0, int $errors = 0, array $results = [], string $message = ''): array
    {
        return [
            'status' => $status,
            'found' => $found,
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'allocated' => $allocated,
            'unmatched' => $unmatched,
            'invalid' => $invalid,
            'pages' => $pages,
            'detail_pages' => $detailPages,
            'errors' => $errors,
            'results' => $results,
            'message' => $message,
        ];
    }
}
