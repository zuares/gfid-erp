<?php

namespace App\Services\Marketplace\TikTokShop;

use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use Illuminate\Support\Collection;
use Throwable;

final class TikTokOrderOwnershipAuditService
{
    public function __construct(
        private MarketplaceApiGateway $gateway,
    ) {}

    /**
     * @param Collection<int, Store> $stores
     * @return array<string, mixed>
     */
    public function audit(Collection $stores, ?string $orderId = null, int $limit = 0, int $pauseMs = 0): array
    {
        $stores = $stores->values();
        $storeIds = $stores->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $orderIds = $this->candidateOrderIds($storeIds, $orderId, $limit);
        $findings = [];

        foreach ($orderIds as $candidateOrderId) {
            $localRows = MarketplaceOrder::query()
                ->with('store')
                ->where('channel_order_id', $candidateOrderId)
                ->whereIn('store_id', $storeIds)
                ->orderBy('id')
                ->get();

            $apiChecks = [];
            foreach ($stores as $store) {
                $apiChecks[(string) $store->id] = $this->checkStore($store, $candidateOrderId);

                if ($pauseMs > 0) {
                    usleep($pauseMs * 1000);
                }
            }

            $foundStoreIds = collect($apiChecks)
                ->filter(fn (array $check): bool => $check['status'] === 'found')
                ->keys()
                ->map(fn ($id): int => (int) $id)
                ->values();
            $errorCount = collect($apiChecks)->where('status', 'error')->count();
            $localStoreIds = $localRows->pluck('store_id')->map(fn ($id): int => (int) $id)->unique()->values();

            $classification = match (true) {
                $foundStoreIds->count() === 1 => 'confirmed_single_store',
                $foundStoreIds->count() > 1 => 'ambiguous_multiple_stores',
                $errorCount > 0 => 'api_error',
                default => 'not_found',
            };

            $findings[] = [
                'channel_order_id' => $candidateOrderId,
                'local_row_ids' => $localRows->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                'local_store_ids' => $localStoreIds->all(),
                'local_stores' => $localRows->map(fn (MarketplaceOrder $order): array => [
                    'store_id' => (int) $order->store_id,
                    'store_name' => (string) ($order->store?->name ?? 'Tanpa toko'),
                ])->unique('store_id')->values()->all(),
                'api_checks' => $apiChecks,
                'recommended_store_id' => $foundStoreIds->count() === 1 ? $foundStoreIds->first() : null,
                'classification' => $classification,
                'reason' => $this->reason($classification, $foundStoreIds, $localStoreIds),
            ];
        }

        return [
            'generated_at' => now()->toISOString(),
            'read_only' => true,
            'stores' => $stores->map(fn (Store $store): array => [
                'id' => (int) $store->id,
                'name' => (string) $store->name,
                'channel' => (string) ($store->channel?->code ?? 'tiktok'),
                'external_shop_id' => (string) ($store->external_shop_id ?? ''),
            ])->values()->all(),
            'summary' => [
                'candidate_order_count' => count($findings),
                'confirmed_single_store' => collect($findings)->where('classification', 'confirmed_single_store')->count(),
                'ambiguous_multiple_stores' => collect($findings)->where('classification', 'ambiguous_multiple_stores')->count(),
                'api_error' => collect($findings)->where('classification', 'api_error')->count(),
                'not_found' => collect($findings)->where('classification', 'not_found')->count(),
            ],
            'findings' => $findings,
        ];
    }

    /**
     * @param array<int, int> $storeIds
     * @return array<int, string>
     */
    private function candidateOrderIds(array $storeIds, ?string $orderId, int $limit): array
    {
        if ($orderId !== null && trim($orderId) !== '') {
            return [trim($orderId)];
        }

        $query = MarketplaceOrder::query()
            ->select('channel_order_id')
            ->whereIn('store_id', $storeIds)
            ->whereNotNull('channel_order_id')
            ->where('channel_order_id', '<>', '')
            ->groupBy('channel_order_id')
            ->havingRaw('COUNT(DISTINCT store_id) > 1')
            ->orderBy('channel_order_id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->pluck('channel_order_id')
            ->map(fn ($id): string => trim((string) $id))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function checkStore(Store $store, string $orderId): array
    {
        try {
            $response = $this->gateway->getOrderDetail($store, [$orderId]);
            $orders = $this->responseOrders($response);
            $code = $response['code'] ?? data_get($response, 'data.code');
            $metaStatus = data_get($response, '_meta.http_status');

            if ($code !== null && (int) $code !== 0) {
                return [
                    'store_id' => (int) $store->id,
                    'store_name' => (string) $store->name,
                    'status' => 'error',
                    'api_code' => (int) $code,
                    'http_status' => $metaStatus !== null ? (int) $metaStatus : null,
                    'error_message' => $this->safeErrorMessage((string) ($response['message'] ?? data_get($response, 'data.message', 'TikTok API returned an error.'))),
                ];
            }

            $matched = collect($orders)->first(function (mixed $order) use ($orderId): bool {
                if (! is_array($order)) {
                    return false;
                }

                return (string) ($order['order_sn'] ?? $order['id'] ?? $order['order_id'] ?? '') === $orderId;
            });

            return [
                'store_id' => (int) $store->id,
                'store_name' => (string) $store->name,
                'status' => $matched !== null ? 'found' : 'absent',
                'api_code' => $code !== null ? (int) $code : null,
                'http_status' => $metaStatus !== null ? (int) $metaStatus : null,
                'api_order_id' => $matched !== null
                    ? (string) ($matched['order_sn'] ?? $matched['id'] ?? $matched['order_id'] ?? $orderId)
                    : null,
            ];
        } catch (Throwable $e) {
            return [
                'store_id' => (int) $store->id,
                'store_name' => (string) $store->name,
                'status' => 'error',
                'error_type' => get_class($e),
                'error_message' => $this->safeErrorMessage($e->getMessage()),
            ];
        }
    }

    /**
     * TikTokShopChannel normalizes successful responses into response.order_list,
     * while older/sandbox responses may expose data.orders directly.
     *
     * @return array<int, array<string, mixed>>
     */
    private function responseOrders(array $response): array
    {
        foreach ([
            data_get($response, 'response.order_list'),
            data_get($response, 'data.orders'),
            data_get($response, 'data.order_list'),
        ] as $orders) {
            if (is_array($orders)) {
                return array_values(array_filter($orders, 'is_array'));
            }
        }

        return [];
    }

    private function safeErrorMessage(string $message): string
    {
        $message = preg_replace(
            '/((?:app_key|shop_cipher|sign|access_token|x-tts-access-token)=)[^&\s]+/i',
            '$1***',
            $message,
        ) ?? 'TikTok API request failed.';

        return mb_substr($message, 0, 240);
    }

    /**
     * @param Collection<int, int> $foundStoreIds
     * @param Collection<int, int> $localStoreIds
     */
    private function reason(string $classification, Collection $foundStoreIds, Collection $localStoreIds): string
    {
        return match ($classification) {
            'confirmed_single_store' => $foundStoreIds->first() && $localStoreIds->contains($foundStoreIds->first())
                ? 'API mengonfirmasi satu toko dan cocok dengan data lokal.'
                : 'API mengonfirmasi satu toko yang berbeda dari data lokal.',
            'ambiguous_multiple_stores' => 'API mengembalikan order dari lebih dari satu toko; perlu verifikasi manual.',
            'api_error' => 'Satu atau lebih pengecekan API gagal; jangan pindahkan data otomatis.',
            default => 'Order tidak ditemukan pada toko yang dicek; perlu verifikasi manual.',
        };
    }
}
