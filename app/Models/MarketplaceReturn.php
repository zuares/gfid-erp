<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceReturn extends Model
{
    protected $fillable = [
        'store_id', 'return_sn', 'order_sn', 'status', 'reason', 'reason_text_code',
        'return_solution', 'amount_before_discount', 'needs_logistics', 'tracking_number',
        'create_time', 'update_time'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(MarketplaceReturnItem::class);
    }
}
