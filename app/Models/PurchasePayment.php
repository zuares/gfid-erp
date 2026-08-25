<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'supplier_invoice_id',  // nullable — link ke faktur supplier (Tahap 4)
        'date',
        'payment_method_id',
        'cash_account_id',
        'type',
        'amount',
        'ref_no',
        'notes',
        'created_by',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    // ✅ akun kas/bank (1101 / 111x)
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function scopeActive($q)
    {
        return $q->whereNull('voided_at');
    }

    public function getIsVoidedAttribute(): bool
    {
        return !is_null($this->voided_at);
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

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->grand_total - $this->paid_amount);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

}
