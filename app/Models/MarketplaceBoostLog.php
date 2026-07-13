<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceBoostLog extends Model
{
    protected $fillable = [
        'store_id', 'marketplace_product_id', 'item_id', 'source',
        'success', 'message', 'boosted_at', 'expires_at',
    ];

    protected $casts = [
        'success'    => 'boolean',
        'boosted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
