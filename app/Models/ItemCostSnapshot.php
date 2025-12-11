<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemCostSnapshot extends Model
{
    protected $table = 'item_cost_snapshots';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'snapshot_date',
        'reference_type',
        'reference_id',
        'qty_basis',
        'rm_unit_cost',
        'cutting_unit_cost',
        'sewing_unit_cost',
        'finishing_unit_cost',
        'packaging_unit_cost',
        'overhead_unit_cost',
        'unit_cost',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'qty_basis' => 'decimal:4',
        'rm_unit_cost' => 'decimal:4',
        'cutting_unit_cost' => 'decimal:4',
        'sewing_unit_cost' => 'decimal:4',
        'finishing_unit_cost' => 'decimal:4',
        'packaging_unit_cost' => 'decimal:4',
        'overhead_unit_cost' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    // Relations
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Snapshot ini terkait 1 ProductionCostPeriod
     * (hanya kalau reference_type = 'production_cost_period').
     */
    public function productionCostPeriod()
    {
        return $this->belongsTo(ProductionCostPeriod::class, 'reference_id')
            ->where('reference_type', 'production_cost_period');
    }

    /** Scope: hanya snapshot yang ditandai aktif */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: batasi ke periode costing yang sedang aktif (kalau ada).
     * Kalau belum ada periode aktif → scope ini tidak membatasi apa-apa.
     */
    public function scopeForCurrentCostPeriod($query)
    {
        $period = ProductionCostPeriod::query()
            ->where('is_active', true)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();

        if ($period) {
            $query->where('reference_type', 'production_cost_period')
                ->where('reference_id', $period->id);
        }

        return $query;
    }

    /**
     * Scope: filter per gudang, tapi tetap izinkan HPP global (warehouse_id = NULL).
     */
    public function scopeForWarehouseOrGlobal($query, ?int $warehouseId)
    {
        if (!$warehouseId) {
            return $query;
        }

        return $query->where(function ($q) use ($warehouseId) {
            $q->whereNull('warehouse_id')
                ->orWhere('warehouse_id', $warehouseId);
        });
    }

    public static function getActiveForItem(int $itemId, ?int $warehouseId = null): ?self
    {
        return static::query()
            ->where('item_id', $itemId)
            ->active()
            ->forWarehouseOrGlobal($warehouseId)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();
    }

}
