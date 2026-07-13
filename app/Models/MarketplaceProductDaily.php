<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceProductDaily extends Model
{
    protected $fillable = [
        'store_id', 'marketplace_product_id', 'date', 'item_status',
        'price_min', 'price_max', 'stock_total', 'sales', 'sales_delta',
        'views', 'rating_star',
    ];

    protected $casts = ['date' => 'date'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
