<?php

namespace App\Services\Marketplace;

final class PurchaseChannel
{
    public static function normalize(mixed $value): ?string
    {
        $channel = strtolower(trim((string) ($value ?? '')));

        return match ($channel) {
            'tiktok', 'tiktok shop', 'aplikasi tiktok', 'aplikasi tiktok shop' => 'tiktok',
            'tokopedia', 'tokopedia shop' => 'tokopedia',
            default => null,
        };
    }
}
