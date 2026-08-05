<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSyncLog extends Model
{
    private const DEFAULT_ORDER_SN_SAMPLE_SIZE = 25;

    protected $fillable = [
        'store_id',
        'action',
        'status',
        'message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Sync order dapat membawa ratusan/ribuan order_sn yang hanya dipakai
     * untuk diagnosis. Simpan sampel kecil agar log tidak menjadi arsip
     * duplikat seluruh hasil sync.
     */
    public function setPayloadAttribute($value): void
    {
        $payload = is_string($value) ? json_decode($value, true) : $value;
        $payload = is_array($payload) ? self::compactPayload($payload) : $payload;

        $this->attributes['payload'] = is_array($payload)
            ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : $payload;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public static function compactPayload(array $payload, int $sampleSize = self::DEFAULT_ORDER_SN_SAMPLE_SIZE): array
    {
        // sync_specific_order sebelumnya menyimpan seluruh detail order/API
        // response ke log. Itu hanya dibutuhkan untuk diagnosis dan menduplikasi
        // data yang sudah tersimpan di marketplace_orders/order_items.
        if (self::looksLikeOrderList($payload)) {
            return self::summarizeOrderList($payload, $sampleSize);
        }

        // Pertahankan error/code/_meta di level atas, tetapi ringkas daftar
        // order/booking besar di dalam response API.
        foreach ([
            ['response', 'order_list'],
            ['response', 'booking_list'],
            ['data', 'orders'],
            ['data', 'order_list'],
        ] as [$root, $key]) {
            $list = $payload[$root][$key] ?? null;
            if (! is_array($list) || ! self::looksLikeOrderList($list)) {
                continue;
            }

            $payload[$root][$key] = self::summarizeOrderList($list, $sampleSize);
        }

        $orderSnList = $payload['order_sn_list'] ?? null;
        if (! is_array($orderSnList) || count($orderSnList) <= $sampleSize) {
            return $payload;
        }

        $payload['order_sn_list'] = array_slice(array_values($orderSnList), 0, max(1, $sampleSize));
        $payload['order_sn_list_total'] = count($orderSnList);
        $payload['order_sn_list_truncated'] = true;

        return $payload;
    }

    /** @param array<int,mixed> $value */
    private static function looksLikeOrderList(array $value): bool
    {
        if (! array_is_list($value) || $value === []) {
            return false;
        }

        $sample = array_slice($value, 0, 3);

        return collect($sample)->contains(function ($row): bool {
            return is_array($row)
                && (array_key_exists('order_sn', $row)
                    || array_key_exists('booking_sn', $row)
                    || array_key_exists('order_id', $row));
        });
    }

    /** @param array<int,array<string,mixed>> $rows */
    private static function summarizeOrderList(array $rows, int $sampleSize): array
    {
        $ids = [];
        $statuses = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = $row['order_sn'] ?? $row['booking_sn'] ?? $row['order_id'] ?? null;
            if ($id !== null && $id !== '') {
                $ids[] = (string) $id;
            }

            $status = strtoupper((string) ($row['order_status'] ?? $row['booking_status'] ?? $row['status'] ?? 'UNKNOWN'));
            $statuses[$status] = ($statuses[$status] ?? 0) + 1;
        }

        return [
            '_compacted'          => true,
            'count'               => count($rows),
            'order_sn_list'       => array_slice(array_values(array_unique($ids)), 0, max(1, $sampleSize)),
            'order_sn_list_total' => count(array_unique($ids)),
            'status_counts'       => $statuses,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
