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
        'purpose',
        'wip_stage',
        'reference_type',
        'reference_id',
        'operator_id',
        // ── WIP cleanup / normalization (aditif) ──
        'action',
        'process_date',
        'from_location_id',
        'to_location_id',
        'is_legacy',
    ];

    // ── Action WIP Cleanup ──
    public const ACTION_KEEP_OPEN   = 'keep_open';
    public const ACTION_MOVE        = 'move';
    public const ACTION_FINISH      = 'finish';       // → WH-PRD / FG
    public const ACTION_REPAIR      = 'repair';
    public const ACTION_REJECT      = 'reject';
    public const ACTION_WRITE_OFF   = 'write_off';    // fisik tidak ditemukan
    public const ACTION_LINK_BUNDLE = 'link_bundle';
    public const ACTION_CLOSE_LEGACY = 'close_legacy';
    public const ACTION_NORMALIZE   = 'normalize';    // hasil opname WIP
    public const ACTION_PICKUP_SETTLE = 'pickup_settle'; // tutup pickup: dianggap disetor → WIP-FIN
    public const ACTION_PICKUP_CANCEL = 'pickup_cancel'; // tutup pickup: batalkan → balik WIP-CUT

    /** Action "besar" yang wajib approval owner/admin. */
    public const ACTIONS_REQUIRE_APPROVAL = [
        self::ACTION_FINISH,
        self::ACTION_REJECT,
        self::ACTION_WRITE_OFF,
        self::ACTION_CLOSE_LEGACY,
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'process_date' => 'date',
        'is_legacy' => 'boolean',
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

    public function isWip(): bool
    {
        return ($this->purpose ?? null) === 'wip';
    }

    public function isManual(): bool
    {
        return $this->source_type === null;
    }

}
