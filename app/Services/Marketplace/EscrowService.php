<?php

namespace App\Services\Marketplace;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use RuntimeException;

/**
 * Live reader untuk data escrow Shopee.
 *
 * Service ini sengaja tidak mempunyai model/repository/cache. Setiap pemanggilan
 * akan diteruskan ke Shopee melalui MarketplaceApiGateway dan response asli tetap
 * dibawa agar field baru dari Shopee tidak hilang di UI.
 */
class EscrowService
{
    private const SHOPEE_CODES = ['shopee', 'shp'];

    public function __construct(
        protected MarketplaceApiGateway $gateway,
    ) {}

    /**
     * @return array{items: array<int,array<string,mixed>>, more: bool, page_no:int, page_size:int, raw_response:array}
     */
    public function fetchList(
        Store $store,
        Carbon $from,
        Carbon $to,
        int $pageNo = 1,
        int $pageSize = 100,
    ): array {
        $this->assertShopee($store);

        $pageNo = max(1, $pageNo);
        $pageSize = min(100, max(1, $pageSize));
        $response = $this->gateway->getEscrowList(
            $store,
            $from->timestamp,
            $to->timestamp,
            $pageNo,
            $pageSize,
        );
        $this->assertSuccessfulResponse($response);

        $rows = data_get($response, 'response.escrow_list');
        if (! is_array($rows)) {
            $rows = data_get($response, 'escrow_list', []);
        }

        $items = collect($rows)
            ->filter(static fn (mixed $row): bool => is_array($row))
            ->map(fn (array $row): array => $this->normalizeListItem($row))
            ->values()
            ->all();

        $more = data_get($response, 'response.more');
        if ($more === null) {
            $more = data_get($response, 'response.has_more');
        }
        if ($more === null) {
            $more = data_get($response, 'more');
        }
        if ($more === null) {
            $more = data_get($response, 'has_more');
        }

        return [
            'items' => $items,
            'more' => $more === null ? count($items) >= $pageSize : (bool) $more,
            'page_no' => $pageNo,
            'page_size' => $pageSize,
            'raw_response' => $this->withoutMeta($response),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function detail(Store $store, string $orderSn): array
    {
        $this->assertShopee($store);

        $response = $this->gateway->getEscrowDetail($store, $orderSn);
        $this->assertSuccessfulResponse($response);

        $root = data_get($response, 'response');
        if (! is_array($root)) {
            $root = $response;
        }

        $income = data_get($root, 'order_income');
        if (! is_array($income)) {
            // Compatibility dengan beberapa response lama yang menaruh field
            // income langsung di response.
            $income = $root;
        }

        return [
            'order_sn' => (string) ($root['order_sn'] ?? $orderSn),
            'buyer_user_name' => $root['buyer_user_name'] ?? null,
            'return_order_sn_list' => is_array($root['return_order_sn_list'] ?? null)
                ? array_values($root['return_order_sn_list'])
                : [],
            'income' => $income,
            'raw_response' => $this->withoutMeta($response),
        ];
    }

    private function assertShopee(Store $store): void
    {
        // Baca langsung dari relasi agar validasi tidak bergantung pada state
        // relasi yang mungkin sudah ter-hydrate oleh implicit route binding.
        $code = strtolower((string) ($store->channel()->value('code') ?? ''));
        if (! in_array($code, self::SHOPEE_CODES, true)) {
            throw new RuntimeException('Modul escrow hanya tersedia untuk toko Shopee.');
        }
    }

    private function assertSuccessfulResponse(array $response): void
    {
        $error = $response['error'] ?? null;
        $code = $response['code'] ?? null;
        if (! empty($error) || ($code !== null && (string) $code !== '0')) {
            $identifier = (string) ($error ?? $code ?? 'unknown_error');
            $message = (string) ($response['message'] ?? $identifier);
            throw new RuntimeException("Shopee {$identifier}: {$message}");
        }
    }

    /**
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    private function normalizeListItem(array $row): array
    {
        $rawReleaseTime = $row['escrow_release_time'] ?? $row['release_time'] ?? null;
        $releaseTime = $this->timestamp($rawReleaseTime);

        return [
            'order_sn' => (string) ($row['order_sn'] ?? $row['ordersn'] ?? ''),
            'payout_amount' => is_numeric($row['payout_amount'] ?? null)
                ? (float) $row['payout_amount']
                : null,
            'escrow_release_time' => is_numeric($rawReleaseTime) ? (int) $rawReleaseTime : null,
            'escrow_release_at' => $releaseTime?->toIso8601String(),
            'raw' => $row,
        ];
    }

    private function timestamp(mixed $value): ?Carbon
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            return null;
        }

        $seconds = (int) $value;
        if ($seconds > 10_000_000_000) {
            $seconds = (int) floor($seconds / 1000);
        }

        try {
            return Carbon::createFromTimestamp($seconds, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Metadata internal dari gateway tidak perlu ikut menjadi response API modul.
     * Field response Shopee lainnya dibiarkan utuh.
     *
     * @param  array<string,mixed>  $response
     * @return array<string,mixed>
     */
    private function withoutMeta(array $response): array
    {
        return Arr::except($response, ['_meta']);
    }
}
