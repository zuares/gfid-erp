<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcResult extends Model
{
    // Stage constant biar nggak typo
    public const STAGE_CUTTING = 'cutting';
    public const STAGE_SEWING = 'sewing';
    public const STAGE_FINISHING = 'finishing';

    protected $fillable = [
        'stage',
        'bundle_id',
        'cutting_job_id',
        'sewing_job_id',
        'finishing_job_id',
        'qc_date',
        'qty_ok',
        'qty_reject',
        'reject_reason',
        'operator_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'qc_date' => 'date',
        'qty_ok' => 'decimal:2',
        'qty_reject' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
     */

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(CuttingJobBundle::class, 'bundle_id');
    }

    public function cuttingJob(): BelongsTo
    {
        return $this->belongsTo(CuttingJob::class, 'cutting_job_id');
    }

    public function finishingJob(): BelongsTo
    {
        return $this->belongsTo(FinishingJob::class, 'finishing_job_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
     */

    public function scopeCutting($query)
    {
        return $query->where('stage', self::STAGE_CUTTING);
    }

    public function scopeSewing($query)
    {
        return $query->where('stage', self::STAGE_SEWING);
    }

    public function scopeFinishing($query)
    {
        return $query->where('stage', self::STAGE_FINISHING);
    }
}
