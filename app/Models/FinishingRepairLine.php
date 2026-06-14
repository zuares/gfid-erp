<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinishingRepairLine extends Model
{
    protected $fillable = [
        'finishing_repair_id',
        'finishing_job_line_id',
        'item_id',
        'reject_item_id',
        'cutting_job_bundle_id',
        'qty_ok',
        'qty_reject',
        'notes',
    ];

    protected $casts = [
        'qty_ok' => 'decimal:3',
        'qty_reject' => 'decimal:3',
    ];

    public function repair(): BelongsTo
    {
        return $this->belongsTo(FinishingRepair::class, 'finishing_repair_id');
    }

    public function finishingJobLine(): BelongsTo
    {
        return $this->belongsTo(FinishingJobLine::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function rejectItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'reject_item_id');
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }
}
