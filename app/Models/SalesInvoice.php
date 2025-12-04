<?php

// app/Models/SalesInvoice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    protected $fillable = [
        'code',
        'date',
        'customer_id',
        'marketplace_order_id',
        'warehouse_id',
        'status',
        'subtotal',
        'discount_total',
        'tax_percent',
        'tax_amount',
        'grand_total',
        'currency',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    // Relations
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function marketplaceOrder()
    {
        return $this->belongsTo(MarketplaceOrder::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines()
    {
        return $this->hasMany(SalesInvoiceLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope status
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}
