<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'code',
        'date',
        'supplier_id',
        'payment_method_id', // ✅
        'subtotal',
        'discount',
        'tax_percent',
        'tax_amount',
        'shipping_cost',
        'grand_total',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
        'closed_at',            // Tahap 4 — additive, nullable
        'closed_by',            // Tahap 4 — nullable
        'order_type',
        'received_status',
        'purchase_request_id',  // PR-D — additive, nullable
        // Receiving lock (GRN dari PO draft) — additive, nullable
        'receiving_started_at',
        'locked_at',
        'locked_by',
        'lock_reason',
        'first_grn_id',
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'float',
        'discount' => 'float',
        'tax_percent' => 'float',
        'tax_amount' => 'float',
        'shipping_cost' => 'float',
        'grand_total' => 'float',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'closed_at' => 'datetime',  // Tahap 4
        'receiving_started_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
     */

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function purchaseReceipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class, 'purchase_order_id');
    }

    public function supplierInvoices(): HasMany
    {
        return $this->hasMany(\App\Models\SupplierInvoice::class, 'purchase_order_id');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(\App\Models\PurchaseReturn::class, 'purchase_order_id');
    }

    /** PR-D — relasi balik ke Purchase Request asal (nullable) */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PurchaseRequest::class, 'purchase_request_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
     */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /** Closed ditandai oleh closed_at (additive — status = closed) */
    public function isClosed(): bool
    {
        return $this->status === 'closed' || !is_null($this->closed_at);
    }

    /**
     * Otomatis meng-close atau meng-approved (reopen) PO berdasarkan kelengkapan status.
     * Dipanggil tiap kali ada update pada received_status atau payment_status.
     */
    public function evaluateAutoClose(): void
    {
        if (in_array($this->status, ['draft', 'cancelled'])) {
            return;
        }

        $isComplete = $this->received_status === 'fully_received' && $this->payment_status === 'paid';

        if ($isComplete && $this->status !== 'closed') {
            $this->status = 'closed';
            $this->closed_at = now();
            $this->save();
        } elseif (!$isComplete && $this->status === 'closed') {
            $this->status = 'approved';
            $this->closed_at = null;
            $this->closed_by = null;
            $this->save();
        }
    }

    /**
     * Receiving lock: PO dikunci karena sudah ada GRN yang merujuk ke line-nya.
     * locked_at adalah flag otoritatif (bukan status tampilan).
     */
    public function isLocked(): bool
    {
        return !is_null($this->locked_at);
    }

    /** Status yang boleh menjadi acuan GRN (draft/approved/closed, TIDAK cancelled). */
    public function isReceivableForGrn(): bool
    {
        return in_array($this->status, ['draft', 'approved'], true) || $this->isClosed();
    }

    /** Label turunan untuk UI: Draft / Receiving / Partially Received / Fully Received / Locked. */
    public function receivingStageLabel(): string
    {
        if ($this->status === 'cancelled') {
            return 'Cancelled';
        }
        if ($this->isFullyReceived()) {
            return 'Fully Received';
        }
        if ($this->isPartiallyReceived()) {
            return 'Partially Received';
        }
        if ($this->isLocked()) {
            return 'Receiving';
        }
        return ucfirst($this->status ?? 'draft');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function firstGrn(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PurchaseReceipt::class, 'first_grn_id');
    }

    public function isFullyReceived(): bool
    {
        return $this->received_status === 'fully_received';
    }

    public function isPartiallyReceived(): bool
    {
        return $this->received_status === 'partial';
    }

    public function isNotReceived(): bool
    {
        return ($this->received_status ?? 'not_received') === 'not_received';
    }

    /**
     * Toleransi pembulatan nominal purchasing dalam rupiah.
     */
    public static function paymentRoundingTolerance(): float
    {
        return max(0.0, (float) config('accounting.purchase_payment_tolerance', 1.00));
    }

    /**
     * Sisa nominal <= toleransi dianggap nol agar status dan UI konsisten.
     */
    public static function normalizePaymentRemainder(float $amount): float
    {
        $remainder = round(max(0.0, $amount), 2);

        return $remainder <= static::paymentRoundingTolerance() ? 0.0 : $remainder;
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\PurchasePayment::class, 'purchase_order_id')
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    public function activePayments()
    {
        return $this->hasMany(\App\Models\PurchasePayment::class, 'purchase_order_id')
            ->whereNull('voided_at');
    }

}
