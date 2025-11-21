<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    // Kalau pakai HasFactory, boleh tambahkan:
    // use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'active',
        'address',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /*
     * RELATIONSHIPS
     * (akan kepakai ketika kamu buat InventoryStock, InventoryMutation, GRN, dll)
     */

    /**
     * Stok item di gudang ini.
     */
    public function stocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /**
     * Mutasi inventory (in/out/adjustment) di gudang ini.
     */
    public function mutations()
    {
        return $this->hasMany(InventoryMutation::class);
    }

    /**
     * Purchase receipt (GRN) yang masuk ke gudang ini.
     */
    public function purchaseReceipts()
    {
        return $this->hasMany(PurchaseReceipt::class);
    }
}
