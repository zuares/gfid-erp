<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceReturnItem extends Model
{
    protected $fillable = [
        'marketplace_return_id', 'item_id', 'item_name', 'variation_name',
        'item_sku', 'variation_sku', 'return_item_quantity', 'images'
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function marketplaceReturn()
    {
        return $this->belongsTo(MarketplaceReturn::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
