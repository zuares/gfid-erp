<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceStore extends Model
{
    protected $fillable = [
        'channel_id',
        'external_store_id',
        'name',
        'short_code',
        'default_warehouse_id',
        'is_active',
    ];

    public function channel()
    {
        return $this->belongsTo(MarketplaceChannel::class, 'channel_id');
    }

    public function defaultWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function orders()
    {
        return $this->hasMany(MarketplaceOrder::class, 'store_id');
    }
}
