<?php

namespace App\Services\Marketplace\Finance;

use App\Domain\Marketplace\Finance\Enums\EscrowStatus;
use App\Models\MarketplaceFinancialComponent;
use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Marketplace\EscrowService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarketplaceEscrowSyncService
{
    public function __construct(
        private EscrowService $escrow,
        private MarketplaceEscrowNormalizer $normalizer,
        private MarketplaceFinanceOrderBridgeService $orderBridge,
    ) {}

    /**
     * Sync one escrow detail. Errors are returned as a result so a batch can
     * continue with the remaining orders.
     *
     * @return array<string,mixed>
     */
    public function syncOrder(Store $store, string $orderSn): array
    {
        $orderSn = trim($orderSn);
        if ($orderSn === '') {
            return ['status' => 'error', 'reason' => 'empty_order_sn', 'message' => 'Order SN kosong.'];
        }

        $order = $this->findOrder($store, $orderSn);
        if ($order) {
            $this->orderBridge->syncFromOrder($order);
        }

        try {
            $detail = $this->escrow->detail($store, $orderSn);
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'reason' => 'api_error',
                'order_sn' => $orderSn,
                'message' => $e->getMessage(),
            ];
        }

        $income = $detail['income'] ?? [];
        if (! is_array($income) || $income === []) {
            return [
                'status' => 'skipped',
                'reason' => 'empty_income',
                'order_sn' => $orderSn,
                'message' => 'Escrow belum tersedia atau response kosong.',
            ];
        }

        try {
            return $this->persistDetail($store, $order, $orderSn, $detail);
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'reason' => 'normalization_error',
                'order_sn' => $orderSn,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync one batch using the existing get_escrow_detail_batch adapter.
     *
     * @return array<string,mixed>
     */
    public function syncOrders(Store $store, array $orderSns): array
    {
        $orderSns = collect($orderSns)
            ->map(fn (mixed $orderSn) => trim((string) $orderSn))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($orderSns === []) {
            return ['status' => 'skipped', 'found' => 0, 'synced' => 0, 'skipped' => 0, 'errors' => 0, 'results' => []];
        }

        $results = [];
        $rawResponses = [];
        $handledOrderSns = [];
        foreach (array_chunk($orderSns, 50) as $chunk) {
            try {
                $response = $this->escrow->detailBatch($store, $chunk);
                $rawResponses[] = $response['raw_response'];
            } catch (\Throwable $e) {
                foreach ($chunk as $orderSn) {
                    $handledOrderSns[] = (string) $orderSn;
                    $results[] = [
                        'status' => 'error',
                        'reason' => 'api_error',
                        'order_sn' => $orderSn,
                        'message' => $e->getMessage(),
                    ];
                }

                continue;
            }

            foreach ($response['details'] as $orderSn => $detail) {
                $handledOrderSns[] = (string) $orderSn;
                $order = $this->findOrder($store, (string) $orderSn);
                if ($order) {
                    $this->orderBridge->syncFromOrder($order);
                }
                try {
                    $results[] = $this->persistDetail($store, $order, (string) $orderSn, $detail);
                } catch (\Throwable $e) {
                    $results[] = ['status' => 'error', 'reason' => 'normalization_error', 'order_sn' => $orderSn, 'message' => $e->getMessage()];
                }
            }

            foreach ($response['failed'] as $orderSn => $failed) {
                $handledOrderSns[] = (string) $orderSn;
                $results[] = [
                    'status' => 'skipped',
                    'reason' => $failed['error'] ?? 'escrow_unavailable',
                    'order_sn' => $orderSn,
                    'message' => $failed['message'] ?? 'Escrow belum tersedia.',
                ];
            }
        }

        foreach (array_diff($orderSns, $handledOrderSns) as $orderSn) {
            $results[] = [
                'status' => 'skipped',
                'reason' => 'missing_from_response',
                'order_sn' => $orderSn,
                'message' => 'Order tidak dikembalikan oleh response escrow batch.',
            ];
        }

        $synced = count(array_filter($results, fn (array $result) => $result['status'] === 'synced'));
        $skipped = count(array_filter($results, fn (array $result) => $result['status'] === 'skipped'));
        $errors = count(array_filter($results, fn (array $result) => $result['status'] === 'error'));

        return [
            'status' => $errors > 0 ? ($synced > 0 ? 'partial' : 'error') : ($skipped > 0 ? 'partial' : 'synced'),
            'found' => count($orderSns),
            'synced' => $synced,
            'skipped' => $skipped,
            'errors' => $errors,
            'results' => $results,
            'raw_response' => $rawResponses,
        ];
    }

    /**
     * Pull escrow list pages through the existing get_escrow_list adapter.
     *
     * @return array<string,mixed>
     */
    public function syncList(Store $store, Carbon $from, Carbon $to, int $pageSize = 100): array
    {
        $page = 1;
        $allResults = [];
        do {
            $list = $this->escrow->fetchList($store, $from, $to, $page, $pageSize);
            $orderSns = collect($list['items'])->pluck('order_sn')->filter()->all();
            if ($orderSns !== []) {
                $batch = $this->syncOrders($store, $orderSns);
                $allResults = array_merge($allResults, $batch['results']);
            }
            $page++;
        } while ($list['more']);

        return [
            'status' => count(array_filter($allResults, fn (array $result) => $result['status'] === 'error')) > 0 ? 'partial' : 'synced',
            'found' => count($allResults),
            'synced' => count(array_filter($allResults, fn (array $result) => $result['status'] === 'synced')),
            'skipped' => count(array_filter($allResults, fn (array $result) => $result['status'] === 'skipped')),
            'errors' => count(array_filter($allResults, fn (array $result) => $result['status'] === 'error')),
            'results' => $allResults,
        ];
    }

    /** @return array<string,mixed> */
    private function persistDetail(Store $store, ?MarketplaceOrder $order, string $fallbackOrderSn, array $detail): array
    {
        $normalized = $this->normalizer->normalize($detail, $fallbackOrderSn);
        $orderSn = $normalized['order_sn'] ?: $fallbackOrderSn;

        return DB::transaction(function () use ($store, $order, $orderSn, $normalized): array {
            $identity = [
                'store_id' => $store->id,
                'channel' => strtolower((string) ($store->channel?->code ?? '')),
                'order_sn' => $orderSn,
            ];
            $values = [
                'marketplace_order_id' => $order?->id,
                'source_hash' => $normalized['source_hash'],
                'raw_payload' => $normalized['raw_payload'],
                'synced_at' => now(),
                'escrow_status' => $normalized['released_at'] ? EscrowStatus::FINALIZED : EscrowStatus::SYNCED,
            ];
            if ($normalized['gross_amount'] !== null) {
                $values['gross_amount'] = $normalized['gross_amount'];
            }
            if ($normalized['net_amount'] !== null) {
                $values['net_amount'] = $normalized['net_amount'];
            }
            if ($normalized['released_at'] !== null) {
                $values['released_at'] = $normalized['released_at'];
            }

            $transaction = MarketplaceFinancialTransaction::updateOrCreate($identity, $values);
            foreach ($normalized['components'] as $component) {
                $dedupeKey = hash('sha256', implode('|', [
                    $transaction->id,
                    $component['code'],
                    $component['provider_line_id'],
                ]));
                MarketplaceFinancialComponent::updateOrCreate(
                    ['dedupe_key' => $dedupeKey],
                    [
                        'financial_transaction_id' => $transaction->id,
                        'component_code' => $component['code'],
                        'component_name' => $component['name'],
                        'amount' => $component['amount'],
                        'direction' => $component['direction'],
                        'provider_line_id' => $component['provider_line_id'],
                        'source_hash' => $normalized['source_hash'],
                        'raw_payload' => $component['raw_payload'],
                    ],
                );
            }

            return [
                'status' => 'synced',
                'order_sn' => $orderSn,
                'financial_transaction_id' => $transaction->id,
                'component_count' => count($normalized['components']),
                'source_hash' => $normalized['source_hash'],
                'order_found' => $order !== null,
            ];
        });
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
}
