<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingJobLot extends Model
{
    protected $table = 'cutting_job_lots';

    protected $fillable = [
        'cutting_job_id',
        'lot_id',
        'planned_fabric_qty',
        'used_fabric_qty',
    ];

    protected $casts = [
        'planned_fabric_qty' => 'float',
        'used_fabric_qty' => 'float',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(CuttingJob::class, 'cutting_job_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    /**
     * Qty yang dipakai beneran (fallback ke planned kalau used masih 0).
     */
    public function getEffectiveUsedQtyAttribute(): float
    {
        return $this->used_fabric_qty > 0
        ? $this->used_fabric_qty
        : $this->planned_fabric_qty;
    }
}
