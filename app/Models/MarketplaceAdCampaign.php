<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAdCampaign extends Model
{
    protected $table = 'marketplace_ad_campaigns';

    protected $fillable = [
        'store_id',
        'channel_campaign_id',
        'campaign_name',
        'campaign_type',
        'status',

        'report_date_from',
        'report_date_to',

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
        'report_date_from'  => 'date:Y-m-d',
        'report_date_to'    => 'date:Y-m-d',
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

    /** ACOS sebagai persen (0–100). */
    public function acosPct(): ?float
    {
        if ((float) $this->gmv <= 0) return null;
        return round((float) $this->spend / (float) $this->gmv * 100, 1);
    }

    /** Break-even ACOS dalam persen. */
    public function breakEvenAcosPct(): ?float
    {
        return $this->break_even_acos !== null
            ? round((float) $this->break_even_acos * 100, 1)
            : null;
    }
}
