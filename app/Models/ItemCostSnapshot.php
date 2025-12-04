<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
        'total_unit_cost',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'qty_basis' => 'float',
        'rm_unit_cost' => 'float',
        'cutting_unit_cost' => 'float',
        'sewing_unit_cost' => 'float',
        'finishing_unit_cost' => 'float',
        'packaging_unit_cost' => 'float',
        'overhead_unit_cost' => 'float',
        'total_unit_cost' => 'float',
    ];

    // RELATIONS
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

    // ACCESSOR: format total unit cost 2 desimal buat tampilan
    public function getTotalUnitCostFormattedAttribute(): string
    {
        return number_format($this->total_unit_cost, 2);
    }

    public function getRmUnitCostFormattedAttribute(): string
    {
        return number_format($this->rm_unit_cost, 2);
    }

    public function getCuttingUnitCostFormattedAttribute(): string
    {
        return number_format($this->cutting_unit_cost, 2);
    }

    public function getSewingUnitCostFormattedAttribute(): string
    {
        return number_format($this->sewing_unit_cost, 2);
    }

    public function getFinishingUnitCostFormattedAttribute(): string
    {
        return number_format($this->finishing_unit_cost, 2);
    }

    public function getPackagingUnitCostFormattedAttribute(): string
    {
        return number_format($this->packaging_unit_cost, 2);
    }

    public function getOverheadUnitCostFormattedAttribute(): string
    {
        return number_format($this->overhead_unit_cost, 2);
    }

    public static function findEffectiveFor(
        int $itemId,
        string | \DateTimeInterface $date,
        ?int $warehouseId = null,
    ): ?self {
        $dateString = $date instanceof \DateTimeInterface
        ? Carbon::instance($date)->toDateString()
        : Carbon::parse($date)->toDateString();

        $q = static::query()
            ->where('item_id', $itemId)
            ->whereDate('snapshot_date', '<=', $dateString);

        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }

        return $q
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();
    }
}
