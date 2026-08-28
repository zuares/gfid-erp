<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceOrder;
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

        return $this->normalizeDetail($root, $orderSn, $response);
    }

    /**
     * Ambil detail escrow maksimal 50 order per request sesuai kontrak Shopee.
     * Hasil dikembalikan sebagai map order_sn agar frontend mudah menggabungkan
     * hasilnya dengan daftar order lokal maupun escrow_list.
     *
     * @return array{details:array<string,array<string,mixed>>, failed:array<string,array<string,mixed>>, raw_response:array}
     */
    public function detailBatch(Store $store, array $orderSns): array
    {
        $this->assertShopee($store);

        $orderSns = collect($orderSns)
            ->map(static fn (mixed $orderSn): string => trim((string) $orderSn))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count($orderSns) < 1 || count($orderSns) > 50) {
            throw new RuntimeException('Batch escrow membutuhkan 1 sampai 50 order SN.');
        }

        $response = $this->gateway->getEscrowDetailBatch($store, $orderSns);
        $this->assertSuccessfulResponse($response);

        // Bentuk response resmi batch adalah response: [{escrow_detail: ...}].
        // Beberapa gateway/versi lama membungkusnya sebagai escrow_detail_list,
        // jadi kedua bentuk harus diterima agar detail tidak diam-diam kosong.
        $rows = data_get($response, 'response');
        if (is_array($rows) && array_key_exists('escrow_detail_list', $rows)) {
            $rows = $rows['escrow_detail_list'];
        }
        if (is_array($rows) && ! array_is_list($rows) && isset($rows['escrow_detail'])) {
            $rows = [$rows];
        }
        if (! is_array($rows)) {
            $rows = data_get($response, 'escrow_detail_list', []);
        }

        $details = [];
        $failed = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $root = $row['escrow_detail'] ?? $row;
            if (! is_array($root)) {
                continue;
            }

            $orderSn = trim((string) ($root['order_sn'] ?? ''));
            if ($orderSn === '') {
                continue;
            }

            $failError = trim((string) ($row['fail_error'] ?? ''));
            $failMessage = trim((string) ($row['fail_message'] ?? ''));
            if ($failError !== '' || $failMessage !== '') {
                $failed[$orderSn] = [
                    'order_sn' => $orderSn,
                    'error' => $failError ?: 'escrow_detail_unavailable',
                    'message' => $failMessage ?: 'Detail escrow belum tersedia dari Shopee.',
                    'raw_response' => $row,
                ];
                continue;
            }

            $details[$orderSn] = $this->normalizeDetail($root, $orderSn, $row);
        }

        return [
            'details' => $details,
            'failed' => $failed,
            'raw_response' => $this->withoutMeta($response),
        ];
    }

    /**
     * Daftar order yang sudah masuk melalui sync/webhook. Ini bukan cache
     * escrow; hanya sumber order_sn agar order baru dapat ditampilkan sebelum
     * Shopee memasukkannya ke escrow_list.
     *
     * @return array{items:array<int,array<string,mixed>>,more:bool,page_no:int,page_size:int}
     */
    public function fetchLocalOrders(
        Store $store,
        Carbon $from,
        Carbon $to,
        int $pageNo = 1,
        int $pageSize = 50,
    ): array {
        $this->assertShopee($store);

        $pageNo = max(1, $pageNo);
        $pageSize = min(100, max(1, $pageSize));
        $query = MarketplaceOrder::query()
            ->where('store_id', $store->id)
            ->whereNotNull('channel_order_id')
            ->where(function ($query) use ($from, $to): void {
                $query->whereBetween('ordered_at', [$from, $to])
                    ->orWhere(function ($query) use ($from, $to): void {
                        $query->whereNull('ordered_at')
                            ->whereBetween('order_date', [$from, $to]);
                    });
            })
            ->orderByDesc('ordered_at')
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        $offset = ($pageNo - 1) * $pageSize;
        $items = $query->clone()->offset($offset)->limit($pageSize)->get()
            ->map(fn (MarketplaceOrder $order): array => [
                'order_sn' => (string) $order->channel_order_id,
                'order_status' => $order->order_status ?: $order->status,
                'buyer_user_name' => $order->buyer_username ?: $order->buyer_name,
                'ordered_at' => optional($order->ordered_at ?: $order->order_date)->toIso8601String(),
                'order_total' => $order->total_amount !== null
                    ? (float) $order->total_amount
                    : null,
                'payout_amount' => null,
                'escrow_release_at' => null,
                'source' => 'webhook_order',
            ])
            ->values()
            ->all();

        return [
            'items' => $items,
            'more' => $query->clone()->offset($offset + $pageSize)->limit(1)->exists(),
            'page_no' => $pageNo,
            'page_size' => $pageSize,
        ];
    }

    /**
     * @param array<string,mixed> $root
     * @param array<string,mixed> $rawResponse
     * @return array<string,mixed>
     */
    private function normalizeDetail(array $root, string $orderSn, array $rawResponse): array
    {
        $income = $root['order_income'] ?? $root;
        if (! is_array($income)) {
            $income = [];
        }

        // Batch response juga membawa buyer_payment_info di luar order_income.
        // Gabungkan field-nya supaya tabel dinamis tetap menampilkan seluruh
        // informasi accounting yang dikembalikan Shopee tanpa menimpa nilai
        // order_income yang lebih spesifik.
        $buyerPaymentInfo = $root['buyer_payment_info'] ?? [];
        if (is_array($buyerPaymentInfo)) {
            $income = array_merge($buyerPaymentInfo, $income);
        }

        return [
            'order_sn' => (string) ($root['order_sn'] ?? $orderSn),
            'buyer_user_name' => $root['buyer_user_name'] ?? null,
            'return_order_sn_list' => is_array($root['return_order_sn_list'] ?? null)
                ? array_values($root['return_order_sn_list'])
                : [],
            'buyer_payment_info' => is_array($buyerPaymentInfo) ? $buyerPaymentInfo : [],
            'income' => $income,
            'raw_response' => $this->withoutMeta($rawResponse),
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
