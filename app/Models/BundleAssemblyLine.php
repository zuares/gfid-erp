<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleAssemblyLine extends Model
{
    protected $fillable = [
        'bundle_assembly_id',
        'material_item_id',
        'qty_per_unit',
        'scrap_pct',
        'qty_required',
        'qty_consumed',
        'uom',
        'unit_cost',
        'total_cost',
        'sort_order',
    ];

    protected $casts = [
        'qty_per_unit' => 'decimal:8',
        'scrap_pct' => 'decimal:4',
        'qty_required' => 'decimal:8',
        'qty_consumed' => 'decimal:8',
        'unit_cost' => 'decimal:6',
        'total_cost' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(BundleAssembly::class, 'bundle_assembly_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'material_item_id');
    }
}
