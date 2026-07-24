<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceAdCampaign extends Model
{
    protected $table = 'marketplace_ad_campaigns';

    protected $fillable = [
        'store_id',
        'channel_campaign_id',
        'channel_item_id',
        'internal_item_id',
        'ad_group_id',
        'mapping_status',
        'mapping_source',
        'campaign_name',
        'campaign_type',
        'status',

        // ── Setting campaign (Fase 2, dari setting_info) ──
        'ad_type',
        'bidding_method',
        'target_roas',
        'campaign_budget',
        'campaign_status',
        'campaign_placement',
        'started_at',
        'ended_at',
        'raw_setting_payload',
        'setting_synced_at',

        'last_synced_range_from',
        'last_synced_range_to',

        'spend',
        'impressions',
        'clicks',
        'ctr',
        'orders',
        'items_sold',
        'gmv',
        'direct_gmv',
        'roas',
        'direct_roas',
        'cpc',
        'cvr',

        'break_even_acos',
        'raw_json',
        'synced_at',
    ];

    protected $casts = [
        'channel_item_id'   => 'integer',
        'internal_item_id'  => 'integer',
        'ad_group_id'       => 'integer',
        'target_roas'         => 'decimal:4',
        'campaign_budget'     => 'decimal:2',
        'started_at'          => 'datetime',
        'ended_at'            => 'datetime',
        'setting_synced_at'   => 'datetime',
        'raw_setting_payload' => 'array',
        'last_synced_range_from' => 'date:Y-m-d',
        'last_synced_range_to'   => 'date:Y-m-d',
        'spend'             => 'decimal:2',
        'impressions'       => 'integer',
        'clicks'            => 'integer',
        'ctr'               => 'decimal:6',
        'orders'            => 'integer',
        'items_sold'        => 'integer',
        'gmv'               => 'decimal:2',
        'direct_gmv'        => 'decimal:2',
        'roas'              => 'decimal:4',
        'direct_roas'       => 'decimal:4',
        'cpc'               => 'decimal:4',
        'cvr'               => 'decimal:6',
        'break_even_acos'   => 'decimal:4',
        'raw_json'          => 'array',
        'synced_at'         => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function dailies(): HasMany
    {
        return $this->hasMany(MarketplaceAdCampaignDaily::class, 'channel_campaign_id', 'channel_campaign_id');
    }

    public function internalItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'internal_item_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAdGroup::class, 'ad_group_id');
    }

    /**
     * Break-even ACOS efektif (0..1) = margin kotor. Prioritas:
     * 1. Override manual di kolom break_even_acos.
     * 2. Derivasi dari data: harga jual rata2 teramati (gmv / unit terjual)
     *    dibanding HPP item internal → BE ACOS = (harga - hpp) / harga
     *    = 1 - (hpp * unit) / gmv.
     *
     * Catatan: `items` tidak menyimpan harga jual, jadi harga diambil dari
     * GMV iklan yang teramati. Butuh internal_item_id termapping + ada penjualan.
     */
    public function effectiveBreakEvenAcos(): ?float
    {
        if ($this->break_even_acos !== null) {
            return (float) $this->break_even_acos;
        }

        $units = (int) ($this->items_sold ?: $this->orders);
        $gmv   = (float) $this->gmv;
        if ($units <= 0 || $gmv <= 0) return null;

        $item = $this->relationLoaded('internalItem') ? $this->internalItem : $this->internalItem()->first();
        if (! $item) return null;

        $hpp = (float) ($item->hpp ?? $item->base_unit_cost ?? 0);
        if ($hpp <= 0) return null;

        $avgPrice = $gmv / $units;
        if ($hpp >= $avgPrice) return null; // jual rugi/impas → BE tak berarti

        return round(($avgPrice - $hpp) / $avgPrice, 6);
    }

    /** ACOS sebagai persen (0–100). */
    public function acosPct(): ?float
    {
        if ((float) $this->gmv <= 0) return null;
        return round((float) $this->spend / (float) $this->gmv * 100, 1);
    }

    /** Break-even ACOS dalam persen (override manual atau derivasi HPP). */
    public function breakEvenAcosPct(): ?float
    {
        $be = $this->effectiveBreakEvenAcos();
        return $be !== null ? round($be * 100, 1) : null;
    }
}
