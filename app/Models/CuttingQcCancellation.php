<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingQcCancellation extends Model
{
    protected $fillable = [
        'cutting_job_id',
        'cutting_job_bundle_id',
        'qc_result_id',
        'item_id',
        'qty_ok',
        'qty_reject',
        'ok_cost',
        'reject_cost',
        'raw_cost_share',
        'labor_cost_share',
        'reason',
        'cancelled_by',
        'cancelled_at',
        'reversal_journal_id',
        'reversal_cutoff_mutation_id',
        'repost_journal_id',
        'reprocessed_qc_result_id',
        'reprocessed_at',
        'metadata',
    ];

    protected $casts = [
        'qty_ok' => 'float',
        'qty_reject' => 'float',
        'ok_cost' => 'float',
        'reject_cost' => 'float',
        'raw_cost_share' => 'float',
        'labor_cost_share' => 'float',
        'cancelled_at' => 'datetime',
        'reprocessed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function cuttingJob(): BelongsTo
    {
        return $this->belongsTo(CuttingJob::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
