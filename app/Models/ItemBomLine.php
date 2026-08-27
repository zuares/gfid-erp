<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemBomLine extends Model
{
    public const STAGE_MAIN_MATERIAL = 'main_material';
    public const STAGE_SEWING_SUPPLY = 'sewing_supply';
    public const STAGE_PACKING_SUPPLY = 'packing_supply';

    protected $fillable = [
        'item_bom_id',
        'material_item_id',
        'usage_stage',
        'qty',
        'uom',
        'scrap_pct',
        'is_optional',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:8',
        'scrap_pct' => 'decimal:3',
        'is_optional' => 'boolean',
    ];

    public static function usageStageLabels(): array
    {
        return [
            self::STAGE_MAIN_MATERIAL => 'Bahan baku utama',
            self::STAGE_SEWING_SUPPLY => 'Kelengkapan jahit',
            self::STAGE_PACKING_SUPPLY => 'Kelengkapan packing',
        ];
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(ItemBom::class, 'item_bom_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'material_item_id');
    }

    public function qtyWithScrap(): float
    {
        $qty = (float) $this->qty;
        $scrap = (float) ($this->scrap_pct ?? 0);

        return round($qty * (1 + ($scrap / 100)), 8);
    }

    public function componentUnitCost(): float
    {
        return (float) ($this->material?->effective_unit_cost ?? 0);
    }

    public function estimatedCost(): float
    {
        return round($this->qtyWithScrap() * $this->componentUnitCost(), 2);
    }

    // dipakai saat generate kebutuhan material berdasarkan qty produksi
    public function requiredQty(float $orderQty): float
    {
        return round($this->qtyWithScrap() * $orderQty, 8);
    }
}
