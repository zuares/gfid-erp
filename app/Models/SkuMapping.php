<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuMapping extends Model
{
    protected $fillable = [
        'marketplace_sku',
        'channel_code',
        'item_id',
        'notes',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Cari item_id dari marketplace_sku + channel_code.
     * Fallback: cari tanpa channel (global mapping).
     */
    public static function resolve(string $sku, ?string $channelCode = null): ?int
    {
        // 1. Cari yang spesifik per channel
        if ($channelCode) {
            $id = static::where('marketplace_sku', $sku)
                ->where('channel_code', $channelCode)
                ->value('item_id');
            if ($id) return $id;
        }

        // 2. Fallback: global (channel_code null)
        return static::where('marketplace_sku', $sku)
            ->whereNull('channel_code')
            ->value('item_id');
    }
}
