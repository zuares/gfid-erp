<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProductModelDaily extends Model
{
    protected $table = 'marketplace_product_model_daily';

    protected $fillable = [
        'store_id', 'marketplace_product_id', 'model_id', 'model_name',
        'model_sku', 'price', 'stock', 'is_available', 'date',
    ];

    protected $casts = [
        'date' => 'date',
        'is_available' => 'boolean',
        'price' => 'decimal:2',
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
