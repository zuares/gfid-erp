<?php

namespace App\Models;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $fillable = [
        'code', 'date', 'customer_id', 'warehouse_id',
        'status', 'subtotal', 'discount_total',
        'tax_percent', 'tax_amount', 'grand_total',
        'currency', 'remarks', 'created_by', 'journal_id', 'posted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'posted_at' => 'datetime',
        'status' => 'string',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function lines()
    {
        return $this->hasMany(SalesInvoiceLine::class, 'sales_invoice_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'sales_invoice_id');
    }

    public function journal()
    {
        return $this->belongsTo(\App\Models\Journal::class, 'journal_id');
    }

    public function marketplaceFinancialTransactions(): HasMany
    {
        return $this->hasMany(MarketplaceFinancialTransaction::class, 'sales_invoice_id');
    }

}
