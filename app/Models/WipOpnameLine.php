<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WipOpnameLine extends Model
{
    protected $fillable = [
        'wip_opname_period_id',
        'cutting_job_bundle_id',
        'bundle_code',
        'item_code',
        'item_name',
        'cutting_job_code',
        'qty_system',
        'qty_physical',
        'difference',
        'is_counted',
        'notes',
        'counted_by',
        'counted_at',
    ];

    protected $casts = [
        'qty_system'   => 'float',
        'qty_physical' => 'float',
        'difference'   => 'float',
        'is_counted'   => 'boolean',
        'counted_at'   => 'datetime',
    ];

    // ── Relations ───────────────────────────────────────────────────────────

    public function period(): BelongsTo
    {
        return $this->belongsTo(WipOpnamePeriod::class, 'wip_opname_period_id');
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function hasDifference(): bool
    {
        return $this->is_counted && abs($this->difference ?? 0) > 0.01;
    }

    public function differenceClass(): string
    {
        $diff = $this->difference ?? 0;
        if (!$this->is_counted) return 'text-muted';
        if ($diff > 0.01)  return 'text-success';
        if ($diff < -0.01) return 'text-danger';
        return 'text-muted';
    }
}
