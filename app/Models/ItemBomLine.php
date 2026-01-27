<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemBomLine extends Model
{
    protected $fillable = [
        'item_bom_id',
        'material_item_id',
        'qty',
        'uom',
        'scrap_pct',
        'is_optional',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'scrap_pct' => 'decimal:2',
        'is_optional' => 'boolean',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(ItemBom::class, 'item_bom_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'material_item_id');
    }

    // dipakai saat generate kebutuhan material berdasarkan qty produksi
    public function requiredQty(float $orderQty): float
    {
        $base = (float) $this->qty * $orderQty;
        return $base + ($base * ((float) $this->scrap_pct / 100));
    }
}
