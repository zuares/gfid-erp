<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentDeletionRequest extends Model
{
    use HasFactory;

    public const TARGET_ORDER = 'order';
    public const TARGET_ITEM = 'item';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'shipment_id',
        'target_type',
        'shipment_order_scan_id',
        'shipment_line_id',
        'requested_by',
        'reviewed_by',
        'status',
        'target_label',
        'reason',
        'review_note',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function orderScan(): BelongsTo
    {
        return $this->belongsTo(ShipmentOrderScan::class, 'shipment_order_scan_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(ShipmentLine::class, 'shipment_line_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
