<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mapping (item_name + variant_name) → marketplace_sku.
 *
 * Dipakai untuk auto-fill SKU kosong: sekali user mengisi SKU manual
 * untuk suatu produk+variant, order berikutnya dengan nama yang sama
 * otomatis terisi SKU yang sama.
 */
class ProductNameSkuMapping extends Model
{
    protected $fillable = [
        'item_name',
        'variant_name',
        'lookup_hash',
        'channel_code',
        'marketplace_sku',
        'notes',
    ];

    public static function makeHash(?string $itemName, ?string $variantName): string
    {
        $name    = mb_strtolower(trim((string) $itemName));
        $variant = mb_strtolower(trim((string) $variantName));

        return sha1($name . '|' . $variant);
    }

    /**
     * Cari SKU dari nama produk + variant.
     * Prioritas: match channel spesifik → match channel manapun (terbaru).
     */
    public static function resolveSku(?string $itemName, ?string $variantName, ?string $channelCode = null): ?string
    {
        if (empty(trim((string) $itemName))) {
            return null;
        }

        $hash = static::makeHash($itemName, $variantName);

        if ($channelCode) {
            $sku = static::where('lookup_hash', $hash)
                ->where('channel_code', $channelCode)
                ->value('marketplace_sku');
            if ($sku) {
                return $sku;
            }
        }

        return static::where('lookup_hash', $hash)
            ->orderByDesc('updated_at')
            ->value('marketplace_sku');
    }

    /**
     * Simpan / update mapping nama → SKU.
     */
    public static function remember(
        ?string $itemName,
        ?string $variantName,
        ?string $channelCode,
        string $sku,
        ?string $notes = null
    ): void {
        $itemName = trim((string) $itemName);
        $sku      = trim($sku);

        if ($itemName === '' || $sku === '') {
            return;
        }

        static::updateOrCreate(
            [
                'lookup_hash'  => static::makeHash($itemName, $variantName),
                'channel_code' => $channelCode,
            ],
            [
                'item_name'       => mb_substr($itemName, 0, 500),
                'variant_name'    => $variantName !== null ? mb_substr(trim($variantName), 0, 500) : null,
                'marketplace_sku' => $sku,
                'notes'           => $notes,
            ]
        );
    }
}
