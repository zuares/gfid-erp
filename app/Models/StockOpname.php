<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class StockOpname extends Model
{
    public const TYPE_OPENING = 'opening';
    public const TYPE_PERIODIC = 'periodic';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_COUNTING = 'counting';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'code',
        'date',
        'warehouse_id',
        'type',
        'status',
        'notes',
        'created_by',
        'reviewed_by',
        'finalized_by',
        'reviewed_at',
        'finalized_at',
    ];

    protected $casts = [
        'date' => 'date',
        'reviewed_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    // 🔗 Relasi

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockOpnameLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * Adjustment yang dihasilkan dari opname ini (jika ada).
     * Menggunakan morphOne dari sisi sumber dokumen.
     */
    public function adjustment(): MorphOne
    {
        return $this->morphOne(InventoryAdjustment::class, 'source');
    }

    // 🔍 Scope helper

    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeFinalized(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FINALIZED);
    }

    public function scopeOpening(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_OPENING);
    }

    public function scopePeriodic(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PERIODIC);
    }

    // ==========================
    //  HELPER METHODS
    // ==========================

    /** Opening / Periodic */
    public function isOpening(): bool
    {
        return $this->type === self::TYPE_OPENING;
    }

    public function isPeriodic(): bool
    {
        return $this->type === self::TYPE_PERIODIC;
    }

    /** Status helpers */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCounting(): bool
    {
        return $this->status === self::STATUS_COUNTING;
    }

    public function isReviewed(): bool
    {
        return $this->status === self::STATUS_REVIEWED;
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    /**
     * Boleh modif lines (tambah/hapus/update)?
     * → hanya di draft / counting.
     */
    public function canModifyLines(): bool
    {
        return !in_array($this->status, [
            self::STATUS_REVIEWED,
            self::STATUS_FINALIZED,
        ]);
    }

    /**
     * Boleh finalize?
     * → status wajib reviewed, belum finalized.
     */
    public function canFinalize(): bool
    {
        return $this->status === self::STATUS_REVIEWED
        && !$this->isFinalized();
    }

    // ==========================
    //  ACCESSOR (property style)
    // ==========================

    public function getIsOpeningAttribute(): bool
    {
        return $this->isOpening();
    }

    public function getIsFinalizedAttribute(): bool
    {
        return $this->isFinalized();
    }

    public function canReopen(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // hanya owner
        if ($user->role !== 'owner') {
            return false;
        }

        return $this->status === self::STATUS_REVIEWED;
    }
}
