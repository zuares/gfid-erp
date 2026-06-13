<?php

namespace App\Services\Channels;

use App\Models\Store;
use App\Services\Channels\Contracts\MarketplaceChannel;
use App\Services\Channels\Shopee\ShopeeChannel;
use App\Services\Channels\TikTokShop\TikTokShopChannel;
use RuntimeException;

class ChannelManager
{
    public function driver(Store $store): MarketplaceChannel
    {
        $code = $store->channel?->code;

        return match ($code) {
            'shopee' => app(ShopeeChannel::class),
            'tiktok' => app(TikTokShopChannel::class),
            default  => throw new RuntimeException("Channel {$code} belum punya adapter."),
        };
    }
}
