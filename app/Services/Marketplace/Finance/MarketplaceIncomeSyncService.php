<?php

namespace App\Services\Marketplace\Finance;

use App\Domain\Marketplace\Finance\Enums\IncomeStatus;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceIncomeSyncService
{
    public function __construct(
        private MarketplaceApiGateway $gateway,
        private MarketplaceIncomeNormalizer $normalizer,
        private MarketplaceFinanceOrderBridgeService $orderBridge,
    ) {}

    /**
     * Sync income state/metadata only. Amounts, components, and journal refs
     * are intentionally outside this service's update payload.
     *
     * @return array{status:string,found:int,synced:int,created:int,updated:int,unmatched:int,unknown:int,pages:int,errors:int,results:array<int,array<string,mixed>>,message:string}
     */
    public function sync(
        Store $store,
        Carbon $from,
        Carbon $to,
        int $incomeStatus = 2,
        int $pageSize = 100,
    ): array {
        $store->loadMissing('channel');
        $channel = strtolower(trim((string) ($store->channel?->code ?? '')));
        if (! in_array($channel, ['shopee', 'shp'], true)) {
            return $this->result('skipped', message: "Channel {$channel} belum mendukung income detail.");
        }

        $pageSize = min(100, max(1, $pageSize));
        $cursor = '';
        $pages = 0;
        $found = 0;
        $synced = 0;
        $created = 0;
        $updated = 0;
        $unmatched = 0;
        $unknown = 0;
        $results = [];

        try {
            do {
                $pages++;
                $response = $this->gateway->getIncomeDetail(
                    $store,
                    $from->toDateString(),
                    $to->toDateString(),
                    $incomeStatus,
                    $pageSize,
                    $cursor,
                );

                if ($this->failed($response)) {
                    $message = (string) ($response['message'] ?? $response['error'] ?? $response['code'] ?? 'unknown');

                    return $this->result(
                        'partial', $found, $synced, $created, $updated, $unmatched, $unknown,
                        $pages, 1, $results, "Income detail gagal: {$message}"
                    );
                }

                $rows = $this->extractRows($response);
                $found += count($rows);
                foreach ($rows as $row) {
                    $normalized = $this->normalizer->normalize($row);
                    $status = $normalized['status'];

                    if ($status === IncomeStatus::UNKNOWN) {
                        $unknown++;
                    }
                    if ($normalized['order_sn'] === '') {
                        $unmatched++;
                        $results[] = ['status' => 'unmatched', 'reason' => 'empty_order_sn'];

                        continue;
                    }

                    $order = $this->findOrder($store, $normalized['order_sn']);
                    if ($order) {
                        $this->orderBridge->syncFromOrder($order);
                    } else {
                        $unmatched++;
                    }

                    $transaction = $this->persist(
                        $store,
                        $channel,
                        $order,
                        $normalized,
                        $created,
                        $updated,
                    );
                    $synced++;
                    $results[] = [
                        'status' => 'synced',
                        'order_sn' => $normalized['order_sn'],
                        'transaction_id' => $transaction->id,
                        'income_status' => $transaction->income_status->value,
                    ];
                }

                $nextCursor = (string) (
                    data_get($response, 'response.income_detail_list.next_page.cursor')
                    ?? data_get($response, 'income_detail_list.next_page.cursor')
                    ?? data_get($response, 'response.next_cursor')
                    ?? data_get($response, 'response.cursor')
                    ?? data_get($response, 'next_cursor')
                    ?? ''
                );
                $hasMore = data_get($response, 'response.more')
                    ?? data_get($response, 'response.has_more')
                    ?? data_get($response, 'more')
                    ?? data_get($response, 'has_more')
                    ?? ($nextCursor !== '');

                if (! $hasMore || $nextCursor === '' || $nextCursor === $cursor || $pages >= 100) {
                    break;
                }
                $cursor = $nextCursor;
            } while (true);
        } catch (\Throwable $e) {
            Log::warning('[marketplace-finance-income-sync] Exception', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return $this->result(
                'partial', $found, $synced, $created, $updated, $unmatched, $unknown,
                $pages, 1, $results, 'Income detail gagal sementara: '.$e->getMessage()
            );
        }

        return $this->result(
            'synced', $found, $synced, $created, $updated, $unmatched, $unknown,
            $pages, 0, $results,
            "Income detail: {$synced} disinkronkan, {$unmatched} tidak cocok."
        );
    }

    private function persist(
        Store $store,
        string $channel,
        ?MarketplaceOrder $order,
        array $normalized,
        int &$created,
        int &$updated,
    ): MarketplaceFinancialTransaction {
        $identity = [
            'store_id' => $store->id,
            'channel' => $channel,
            'order_sn' => $normalized['order_sn'],
        ];

        return DB::transaction(function () use ($identity, $order, $normalized, &$created, &$updated): MarketplaceFinancialTransaction {
            $transaction = MarketplaceFinancialTransaction::query()
                ->where($identity)
                ->lockForUpdate()
                ->first();
            $wasCreated = ! $transaction;

            if (! $transaction) {
                $transaction = MarketplaceFinancialTransaction::create($identity + [
                    'marketplace_order_id' => $order?->id,
                    'currency' => strtoupper((string) ($order?->currency ?: 'IDR')),
                ]);
            }

            $current = $transaction->income_status instanceof IncomeStatus
                ? $transaction->income_status
                : IncomeStatus::UNKNOWN;
            $next = $this->forwardStatus($current, $normalized['status']);
            $values = [
                'income_status' => $next,
                'income_source_hash' => $normalized['source_hash'],
                'income_raw_payload' => $normalized['raw_payload'],
                'income_synced_at' => now(),
            ];
            if ($next === IncomeStatus::RELEASED && $normalized['released_at']) {
                $values['released_at'] = $normalized['released_at'];
            }
            $transaction->fill($values)->save();

            if ($wasCreated) {
                $created++;
            } else {
                $updated++;
            }

            return $transaction->fresh();
        });
    }

    private function forwardStatus(IncomeStatus $current, IncomeStatus $incoming): IncomeStatus
    {
        if ($incoming === IncomeStatus::UNKNOWN) {
            return $current;
        }
        if ($current === IncomeStatus::UNKNOWN) {
            return $incoming;
        }

        $rank = [
            IncomeStatus::PENDING->value => 1,
            IncomeStatus::TO_RELEASE->value => 2,
            IncomeStatus::RELEASED->value => 3,
        ];

        return ($rank[$incoming->value] ?? 0) >= ($rank[$current->value] ?? 0) ? $incoming : $current;
    }

    private function findOrder(Store $store, string $orderSn): ?MarketplaceOrder
    {
        return MarketplaceOrder::query()
            ->where('store_id', $store->id)
            ->where(function ($query) use ($orderSn): void {
                $query->where('channel_order_id', $orderSn)
                    ->orWhere('external_order_id', $orderSn);
            })
            ->first();
    }

    private function failed(array $response): bool
    {
        return ! empty($response['error'])
            || (($response['code'] ?? null) !== null && (string) $response['code'] !== '0');
    }

    /** @return array<int,array<string,mixed>> */
    private function extractRows(array $response): array
    {
        $candidates = [
            data_get($response, 'response.income_detail_list.list'),
            data_get($response, 'income_detail_list.list'),
            data_get($response, 'response.income_detail'),
            data_get($response, 'response.income_details'),
            data_get($response, 'response.income_detail_list'),
            data_get($response, 'response.list'),
            data_get($response, 'response.data'),
            data_get($response, 'income_detail'),
            data_get($response, 'income_details'),
            data_get($response, 'income_detail_list'),
            data_get($response, 'list'),
            data_get($response, 'data'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }
            if (array_is_list($candidate)) {
                return array_values(array_filter($candidate, 'is_array'));
            }
            if (isset($candidate['list']) && is_array($candidate['list'])) {
                return array_values(array_filter($candidate['list'], 'is_array'));
            }
        }

        return is_array($response['response'] ?? null) && array_is_list($response['response'])
            ? array_values(array_filter($response['response'], 'is_array'))
            : [];
    }

    /** @return array{status:string,found:int,synced:int,created:int,updated:int,unmatched:int,unknown:int,pages:int,errors:int,results:array<int,array<string,mixed>>,message:string} */
    private function result(
        string $status,
        int $found = 0,
        int $synced = 0,
        int $created = 0,
        int $updated = 0,
        int $unmatched = 0,
        int $unknown = 0,
        int $pages = 0,
        int $errors = 0,
        array $results = [],
        string $message = '',
    ): array {
        return compact('status', 'found', 'synced', 'created', 'updated', 'unmatched', 'unknown', 'pages', 'errors', 'results', 'message');
    }
}
