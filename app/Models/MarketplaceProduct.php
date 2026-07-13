<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceProduct extends Model
{
    protected $fillable = [
        'store_id', 'item_id', 'item_name', 'item_sku', 'item_status',
        'category_id', 'image_url', 'price_min', 'price_max', 'stock_total',
        'has_model', 'sales', 'views', 'rating_star', 'raw_json', 'synced_at',
    ];

    protected $casts = [
        'raw_json'   => 'array',
        'has_model'  => 'boolean',
        'synced_at'  => 'datetime',
        'price_min'  => 'decimal:2',
        'price_max'  => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function models(): HasMany
    {
        return $this->hasMany(MarketplaceProductModel::class);
    }
}
