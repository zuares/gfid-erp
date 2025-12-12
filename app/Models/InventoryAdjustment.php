<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryAdjustment extends Model
{
    // ==========================
    //  STATUS CONSTANTS
    // ==========================
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_VOID = 'void'; // / cancelled

    // ==============================
    // SOURCE TYPE (untuk inventory_mutations)
    // ==============================
    public const SOURCE_MANUAL = 'inventory_adjustment_manual';
    public const SOURCE_SO_OPENING = 'stock_opname_opening';
    public const SOURCE_SO_PERIODIC = 'stock_opname_periodic';

    protected $fillable = [
        'code',
        'date',
        'warehouse_id',
        'source_type',
        'source_id',
        'reason',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    // 🔗 Relasi

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryAdjustmentLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Kalau mau pakai morph ke sumber: StockOpname / modul lain
    public function source(): MorphTo
    {
        return $this->morphTo(null, 'source_type', 'source_id');
    }

    // 🔍 Scope helper

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeNotVoid(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_VOID);
    }

    // ==========================
    //  STATUS HELPERS
    // ==========================

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isVoid(): bool
    {
        return in_array($this->status, [self::STATUS_VOID, 'cancelled'], true);
    }
/**
 * Human readable source label
 */
    public static function sourceLabel(string | null $sourceType, ?string $fallback = null): string
    {
        return match ($sourceType) {
            self::SOURCE_MANUAL => 'Adjustment Manual',
            self::SOURCE_SO_OPENING => 'Stock Opname (Opening)',
            self::SOURCE_SO_PERIODIC => 'Stock Opname (Periodic)',
            default => $fallback ?? 'Lainnya',
        };
    }

    /**
     * Boleh di-approve?
     * - sekarang status PENDING
     * - belum ada approved_by
     */
    public function canApprove(): bool
    {
        return $this->isPending() && !$this->approved_by;
    }

    public function isOpening(): bool
    {
        return $this->source_type === StockOpname::class
        && $this->source?->type === StockOpname::TYPE_OPENING;
    }

    public function isPeriodic(): bool
    {
        return $this->source_type === StockOpname::class
        && $this->source?->type === StockOpname::TYPE_PERIODIC;
    }
}
