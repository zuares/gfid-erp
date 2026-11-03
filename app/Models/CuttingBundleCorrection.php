<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingBundleCorrection extends Model
{
    protected $fillable = [
        'cutting_job_id',
        'cutting_job_bundle_id',
        'original_item_id',
        'replacement_item_id',
        'lot_id',
        'warehouse_id',
        'return_fabric_qty',
        'unit_cost',
        'return_cost',
        'status',
        'reason',
        'corrected_by',
        'corrected_at',
        'qc_cancellation_id',
        'material_return_mutation_id',
        'material_return_journal_id',
        'metadata',
    ];

    protected $casts = [
        'return_fabric_qty' => 'float',
        'unit_cost' => 'float',
        'return_cost' => 'float',
        'corrected_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }

    public function cuttingJob(): BelongsTo
    {
        return $this->belongsTo(CuttingJob::class);
    }

    public function replacementItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'replacement_item_id');
    }
}
