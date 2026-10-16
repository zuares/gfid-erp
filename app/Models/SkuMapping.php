<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
            $aliases = static::channelAliases($channelCode);
            $id = static::where('marketplace_sku', $sku)
                ->whereIn(DB::raw('LOWER(channel_code)'), $aliases)
                ->value('item_id');
            if ($id) return $id;
        }

        // 2. Fallback: global (channel_code null)
        return static::where('marketplace_sku', $sku)
            ->whereNull('channel_code')
            ->value('item_id');
    }

    /**
     * Mapping lama menyimpan nama channel (tiktok/shopee), sedangkan Store
     * memakai kode singkat (TTK/SHP). Terima keduanya agar hasil import tidak
     * gagal menemukan HPP hanya karena variasi kode channel.
     *
     * @return array<int, string>
     */
    private static function channelAliases(string $channelCode): array
    {
        $normalized = strtolower(trim($channelCode));

        return match ($normalized) {
            'shopee', 'shp' => ['shopee', 'shp'],
            'tiktok', 'tiktok shop', 'ttk' => ['tiktok', 'tiktok shop', 'ttk'],
            'tokopedia', 'tkp' => ['tokopedia', 'tkp'],
            default => [$normalized],
        };
    }
}
