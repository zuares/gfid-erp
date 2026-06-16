<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoice extends Model
{
    protected $fillable = [
        'invoice_no',
        'supplier_invoice_ref',
        'supplier_id',
        'purchase_order_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'return_deduction_amount',
        'total_amount',
        'paid_amount',
        'status',
        'notes',
        'posted_at',
        'posted_by',
        'voided_at',
        'voided_by',
        'created_by',
    ];

    protected $casts = [
        'invoice_date'            => 'date',
        'due_date'                => 'date',
        'subtotal'                => 'float',
        'discount_amount'         => 'float',
        'return_deduction_amount' => 'float',
        'total_amount'            => 'float',
        'paid_amount'             => 'float',
        'posted_at'               => 'datetime',
        'voided_at'               => 'datetime',
    ];

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    // =========================================================
    // HELPERS
    // =========================================================

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return in_array($this->status, ['posted', 'partial_paid', 'paid'], true);
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function outstanding(): float
    {
        return max(0, round((float) $this->total_amount - (float) $this->paid_amount, 2));
    }

    /**
     * Recalculate total_amount dari komponen (idempoten).
     */
    public function recalcTotal(): void
    {
        $total = max(0, round(
            (float) $this->subtotal
            - (float) $this->discount_amount
            - (float) $this->return_deduction_amount,
            2
        ));
        $this->total_amount = $total;
    }

    /**
     * Sinkronisasi status paid berdasarkan paid_amount vs total_amount.
     * Dipanggil manual ketika ada pembayaran yang direcord.
     * Jangan panggil jika status = void.
     */
    public function syncPaymentStatus(): void
    {
        if ($this->status === 'void' || $this->status === 'draft') {
            return;
        }

        $total   = (float) $this->total_amount;
        $paid    = (float) $this->paid_amount;
        $epsilon = 0.01;

        if ($total <= 0) {
            return;
        }

        if ($paid <= $epsilon) {
            $this->status = 'posted';
        } elseif ($paid >= $total - $epsilon) {
            $this->status = 'paid';
        } else {
            $this->status = 'partial_paid';
        }
    }
}
