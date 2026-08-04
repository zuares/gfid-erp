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
        $orderSnList = $payload['order_sn_list'] ?? null;
        if (! is_array($orderSnList) || count($orderSnList) <= $sampleSize) {
            return $payload;
        }

        $payload['order_sn_list'] = array_slice(array_values($orderSnList), 0, max(1, $sampleSize));
        $payload['order_sn_list_total'] = count($orderSnList);
        $payload['order_sn_list_truncated'] = true;

        return $payload;
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
