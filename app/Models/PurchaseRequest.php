<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'code',
        'date',
        'supplier_id',
        'requested_by',
        'approved_by',
        'rejected_by',
        'converted_to_po_id', // PR-D
        'converted_at',       // PR-D
        'status',
        'notes',
    ];

    protected $casts = [
        'date'         => 'date',
        'converted_at' => 'datetime', // PR-D
    ];

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequestLine::class, 'purchase_request_id');
    }

    /** PR-D — PO hasil convert (nullable) */
    public function convertedToPo(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'converted_to_po_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'purchase_request_id');
    }

    // =========================================================
    // STATUS HELPERS
    // =========================================================

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * PR hanya bisa diedit selama masih draft.
     * Converted / rejected tidak boleh diedit.
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Apakah PR ini sudah pernah diconvert ke PO.
     */
    public function isConvertible(): bool
    {
        return $this->status === 'approved'
            && !$this->lines()->whereNotNull('purchase_order_id')->exists();
    }
}
