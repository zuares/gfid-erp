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
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
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
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }
}
