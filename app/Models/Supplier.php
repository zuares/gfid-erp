<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'address',
        'type',
        'po_types',
        'active',
    ];

    protected $casts = [
        'active'   => 'boolean',
        'po_types' => 'array',
    ];

    /* ==========================
     *  RELATIONSHIPS
     * ==========================
     */

    /**
     * Purchase order yang dibuat ke supplier ini.
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Harga historis per item di supplier ini.
     */
    public function prices()
    {
        return $this->hasMany(SupplierPrice::class);
    }

    /* ==========================
     *  HELPER
     * ==========================
     */

    public function isMaterialSupplier(): bool
    {
        return $this->type === 'supplier';
    }

    public function isCuttingVendor(): bool
    {
        return $this->type === 'cutting_vendor';
    }

    public function isSewingVendor(): bool
    {
        return $this->type === 'sewing_vendor';
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'supplier_items')
            ->withPivot(['last_price', 'is_primary', 'minimum_order_qty', 'lead_time_days', 'active'])
            ->withTimestamps();
    }

    public function bankAccounts()
    {
        return $this->hasMany(SupplierBankAccount::class);
    }

}
