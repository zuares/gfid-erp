<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WipOpnamePeriod extends Model
{
    const STATUS_OPEN             = 'open';
    const STATUS_COUNTING         = 'counting';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED         = 'approved';
    const STATUS_CLOSED           = 'closed';

    protected $fillable = [
        'code',
        'scope',
        'date',
        'notes',
        'status',
        'opened_by',
        'opened_at',
        'approved_by',
        'approved_at',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'date'        => 'date',
        'opened_at'   => 'datetime',
        'approved_at' => 'datetime',
        'closed_at'   => 'datetime',
    ];

    // ── Relations ───────────────────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(WipOpnameLine::class, 'wip_opname_period_id');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_COUNTING]);
    }

    public function canEdit(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_COUNTING]);
    }

    public function canApprove(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function progressPercent(): int
    {
        $total   = $this->lines()->count();
        $counted = $this->lines()->where('is_counted', true)->count();
        return $total > 0 ? (int) round($counted / $total * 100) : 0;
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_COUNTING]);
    }

    public function scopeCutting($query)
    {
        return $query->where('scope', 'cutting');
    }
}
