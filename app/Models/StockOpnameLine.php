<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameLine extends Model
{

    protected static array $activeCostCache = [];

    protected $fillable = [
        'stock_opname_id',
        'item_id',
        'system_qty',
        'physical_qty',
        'difference_qty',
        'is_counted',
        'notes',
        'unit_cost', // ⭐ penting untuk mode opening (HPP per item)
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'physical_qty' => 'decimal:3',
        'difference_qty' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'is_counted' => 'boolean',
    ];

    /**
     * Tambahan attribute terhitung:
     * - difference          : selisih qty (fallback ke physical - system)
     * - effective_unit_cost : HPP/unit yang dipakai (line → snapshot → master)
     * - difference_value    : nilai rupiah selisih (difference × effective_unit_cost)
     */
    protected $appends = [
        'difference',
        'effective_unit_cost',
        'difference_value',
    ];

    protected static function booted()
    {
        static::saving(function (StockOpnameLine $line) {
            if (!is_null($line->physical_qty) && !is_null($line->system_qty)) {
                $line->difference_qty = (float) $line->physical_qty - (float) $line->system_qty;
            }
        });
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Selisih qty (qty fisik - qty sistem).
     * Kalau difference_qty sudah ada di DB → pakai itu.
     */
    public function getDifferenceAttribute(): float
    {
        if (!is_null($this->difference_qty)) {
            return (float) $this->difference_qty;
        }

        return (float) ($this->physical_qty ?? 0) - (float) ($this->system_qty ?? 0);
    }

    /**
     * HPP / unit yang dipakai untuk hitung nilai selisih:
     * 1. unit_cost di line (kalau diisi)
     * 2. snapshot aktif per item+gudang
     * 3. base_unit_cost di master item
     */
    public function getEffectiveUnitCostAttribute(): float
    {
        // 1) input manual di line
        if (!is_null($this->unit_cost) && (float) $this->unit_cost > 0) {
            return (float) $this->unit_cost;
        }

        // 2) snapshot aktif per item + gudang (pakai cache)
        $warehouseId = $this->stockOpname?->warehouse_id;
        if ($warehouseId) {
            $key = $warehouseId . ':' . $this->item_id;

            if (!array_key_exists($key, self::$activeCostCache)) {
                $snap = ItemCostSnapshot::getActiveForItem($this->item_id, $warehouseId);
                self::$activeCostCache[$key] = ($snap && $snap->unit_cost > 0) ? (float) $snap->unit_cost : 0.0;
            }

            if (self::$activeCostCache[$key] > 0) {
                return self::$activeCostCache[$key];
            }
        }

        // 3) fallback master item
        if ($this->item && (float) $this->item->base_unit_cost > 0) {
            return (float) $this->item->base_unit_cost;
        }

        return 0.0;
    }

    /**
     * Nilai selisih (Rp):
     * difference_value = difference * effective_unit_cost
     */
    public function getDifferenceValueAttribute(): float
    {
        $diffQty = $this->difference; // pakai accessor di atas
        $unitCost = $this->effective_unit_cost;

        if ($unitCost <= 0 || abs($diffQty) < 0.0000001) {
            return 0.0;
        }

        return $diffQty * $unitCost;
    }
}
