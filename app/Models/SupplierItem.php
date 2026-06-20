<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierItem extends Model
{
    protected $fillable = [
        'supplier_id',
        'item_id',
        'is_primary',
        'last_price',
        'minimum_order_qty',
        'lead_time_days',
        'active',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'last_price' => 'float',
        'minimum_order_qty' => 'float',
        'lead_time_days' => 'integer',
        'active' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
