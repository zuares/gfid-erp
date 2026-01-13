<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustmentLine extends Model
{
    protected $fillable = [
        'inventory_adjustment_id',
        'item_id',
        'qty_before',
        'qty_after',
        'qty_change',
        'direction',
        'notes',
        'lot_id',

        // ✅ WIP production refs
        'cutting_job_bundle_id',
        'sewing_pickup_line_id',
        'finishing_job_line_id',
    ];

    protected $casts = [
        'qty_before' => 'decimal:3',
        'qty_after' => 'decimal:3',
        'qty_change' => 'decimal:3',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // ✅ Relations produksi
    public function cuttingBundle(): BelongsTo
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }

    public function sewingPickupLine(): BelongsTo
    {
        return $this->belongsTo(SewingPickupLine::class, 'sewing_pickup_line_id');
    }

    public function finishingLine(): BelongsTo
    {
        return $this->belongsTo(FinishingJobLine::class, 'finishing_job_line_id');
    }
}
