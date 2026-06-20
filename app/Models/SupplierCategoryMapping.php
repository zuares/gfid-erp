<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierCategoryMapping extends Model
{
    protected $fillable = [
        'item_category_id',
        'supplier_id',
        'is_primary',
        'lead_time_days',
        'active',
    ];

    protected $casts = [
        'lead_time_days' => 'integer',
        'is_primary' => 'boolean',
        'active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
