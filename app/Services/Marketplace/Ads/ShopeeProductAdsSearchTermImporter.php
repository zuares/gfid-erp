<?php

namespace App\Services\Marketplace\Ads;

/**
 * @deprecated  Tidak digunakan lagi. Data iklan sekarang dari Shopee Ads API
 *              via MarketplaceSyncService::syncAdCampaigns().
 *              File ini bisa dihapus.
 */
class ShopeeProductAdsSearchTermImporter
{
    public function parse(string $absPath): array
    {
        throw new \RuntimeException(
            'ShopeeProductAdsSearchTermImporter sudah deprecated. ' .
            'Gunakan Shopee Ads API sync via /api/marketplace/stores/{store}/sync-ad-campaigns.'
        );
    }
}
