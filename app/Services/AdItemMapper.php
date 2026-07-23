<?php

namespace App\Services;

use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdItemMap;
use Illuminate\Support\Facades\DB;

/**
 * Resolusi produk iklan → item internal.
 *
 * Urutan resolusi (menghasilkan [internal_item_id, mapping_source]):
 *   1. Override manual (marketplace_ad_item_maps) by channel_item_id.
 *   2. Override manual by channel_campaign_id.
 *   3. marketplace_order_items: external_item_id == channel_item_id pada toko
 *      yang sama → internal_item_id yang paling sering dipakai.
 *   4. Gagal → null (mapping_status = 'unmapped').
 *
 * Karena mapping berbasis item internal, dua campaign berjudul produk berbeda
 * tapi internal_item_id sama otomatis tergabung saat analisa per item.
 */
class AdItemMapper
{
    /**
     * @return array{0: ?int, 1: string} [internalItemId, source]
     *   source: 'manual' | 'order_items' | 'none'
     */
    public function resolve(
        int $storeId,
        ?int $channelItemId,
        ?string $channelCampaignId,
        string $channelCode = 'shopee'
    ): array {
        // 1 & 2 — override manual
        $manual = MarketplaceAdItemMap::query()
            ->where('channel_code', $channelCode)
            ->where(function ($q) use ($channelItemId, $channelCampaignId) {
                if ($channelItemId)      $q->orWhere('channel_item_id', $channelItemId);
                if ($channelCampaignId)  $q->orWhere('channel_campaign_id', $channelCampaignId);
            })
            ->orderByRaw('CASE WHEN channel_item_id IS NOT NULL THEN 0 ELSE 1 END')
            ->first();

        if ($manual) {
            return [(int) $manual->internal_item_id, 'manual'];
        }

        // 3 — belajar dari order items yang sudah dipetakan
        if ($channelItemId) {
            $row = DB::table('marketplace_order_items as oi')
                ->join('marketplace_orders as o', 'o.id', '=', 'oi.marketplace_order_id')
                ->where('o.store_id', $storeId)
                ->where('oi.external_item_id', (string) $channelItemId)
                ->whereNotNull('oi.internal_item_id')
                ->select('oi.internal_item_id', DB::raw('COUNT(*) as n'))
                ->groupBy('oi.internal_item_id')
                ->orderByDesc('n')
                ->first();

            if ($row) {
                return [(int) $row->internal_item_id, 'order_items'];
            }
        }

        return [null, 'none'];
    }

    /**
     * Terapkan resolusi ke satu campaign (mutasi kolom, tidak menyimpan).
     */
    public function applyTo(MarketplaceAdCampaign $campaign): MarketplaceAdCampaign
    {
        [$internalItemId, $source] = $this->resolve(
            (int) $campaign->store_id,
            $campaign->channel_item_id ? (int) $campaign->channel_item_id : null,
            $campaign->channel_campaign_id,
        );

        $campaign->internal_item_id = $internalItemId;
        $campaign->mapping_source   = $internalItemId ? $source : null;
        $campaign->mapping_status   = $internalItemId
            ? ($source === 'manual' ? 'manual' : 'auto')
            : 'unmapped';

        return $campaign;
    }

    /**
     * Recompute mapping untuk banyak campaign sekaligus (backfill).
     *
     * @return int jumlah campaign yang berubah mapping-nya
     */
    public function backfill(?int $storeId = null): int
    {
        $changed = 0;

        MarketplaceAdCampaign::query()
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->chunkById(200, function ($campaigns) use (&$changed) {
                foreach ($campaigns as $campaign) {
                    $before = $campaign->internal_item_id;
                    $this->applyTo($campaign);
                    if ($campaign->isDirty(['internal_item_id', 'mapping_status', 'mapping_source'])) {
                        $campaign->save();
                        if ($campaign->internal_item_id !== $before) $changed++;
                    }
                }
            });

        return $changed;
    }
}
